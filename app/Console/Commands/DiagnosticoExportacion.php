<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EstacionDato;
use App\Models\ZonaManejos;
use App\Models\TipoCultivos;
use Carbon\Carbon;

class DiagnosticoExportacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnostico:exportacion {zona_manejo_id} {periodo=1} {start_date?} {end_date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostica la consulta de exportación de datos de estación';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Usar la conexión AWS
        config(['database.default' => 'aws']);

        $zona_manejo_id = $this->argument('zona_manejo_id');
        $periodo = $this->argument('periodo');
        $start_date = $this->argument('start_date');
        $end_date = $this->argument('end_date');

        $this->info("=== DIAGNÓSTICO DE EXPORTACIÓN ===");
        $this->info("Zona de manejo ID: {$zona_manejo_id}");
        $this->info("Periodo: {$periodo}");
        $this->info("Fecha inicio: " . ($start_date ?: 'No especificada'));
        $this->info("Fecha fin: " . ($end_date ?: 'No especificada'));

        // 1. Verificar que la zona de manejo existe
        $this->info("\n1. Verificando zona de manejo...");
        $zona_manejo = ZonaManejos::find($zona_manejo_id);
        if (!$zona_manejo) {
            $this->error("❌ Zona de manejo no encontrada con ID: {$zona_manejo_id}");
            return 1;
        }
        $this->info("✅ Zona de manejo encontrada: {$zona_manejo->nombre}");

        // 2. Verificar estaciones asociadas
        $this->info("\n2. Verificando estaciones asociadas...");
        $estaciones = $zona_manejo->estaciones;
        if ($estaciones->isEmpty()) {
            $this->error("❌ No hay estaciones asociadas a esta zona de manejo");
            return 1;
        }
        $this->info("✅ Estaciones encontradas: " . $estaciones->count());
        $estacion_ids = $estaciones->pluck('id')->toArray();
        $this->info("   IDs de estaciones: " . implode(', ', $estacion_ids));

        // 3. Verificar tipos de cultivo
        $this->info("\n3. Verificando tipos de cultivo...");
        $tipos_cultivo = $zona_manejo->tipoCultivos;
        if ($tipos_cultivo->isEmpty()) {
            $this->warn("⚠️  No hay tipos de cultivo asociados a esta zona de manejo");
        } else {
            $this->info("✅ Tipos de cultivo encontrados: " . $tipos_cultivo->count());
            foreach ($tipos_cultivo as $tipo) {
                $this->info("   - {$tipo->nombre}");
            }
        }

        // 4. Calcular fechas según el periodo
        $this->info("\n4. Calculando fechas del periodo...");
        $fechas = $this->calcularPeriodo($periodo, $start_date, $end_date);
        $this->info("   Desde: {$fechas[0]}");
        $this->info("   Hasta: {$fechas[1]}");
        $this->info("   Grupo: {$fechas[2]}");

        // 5. Verificar datos en el rango de fechas
        $this->info("\n5. Verificando datos en el rango de fechas...");
        $total_datos = EstacionDato::whereIn('estacion_id', $estacion_ids)
            ->whereBetween('created_at', [$fechas[0], $fechas[1]])
            ->count();

        $this->info("   Total de registros en el rango: {$total_datos}");

        if ($total_datos == 0) {
            $this->warn("⚠️  No hay datos en el rango de fechas especificado");

            // Verificar si hay datos fuera del rango
            $datos_fuera_rango = EstacionDato::whereIn('estacion_id', $estacion_ids)->count();
            $this->info("   Total de registros en todas las fechas: {$datos_fuera_rango}");

            if ($datos_fuera_rango > 0) {
                $this->info("   Fechas disponibles:");
                $fechas_disponibles = EstacionDato::whereIn('estacion_id', $estacion_ids)
                    ->selectRaw('MIN(created_at) as min_fecha, MAX(created_at) as max_fecha')
                    ->first();
                $this->info("   Desde: {$fechas_disponibles->min_fecha}");
                $this->info("   Hasta: {$fechas_disponibles->max_fecha}");
            }
        } else {
            $this->info("✅ Datos encontrados en el rango");
        }

        // 6. Probar la consulta completa
        $this->info("\n6. Probando consulta completa...");
        $select = $this->getSelectClause($fechas[2]);

        $query = EstacionDato::whereIn('estacion_id', $estacion_ids)
            ->whereBetween('created_at', [$fechas[0], $fechas[1]])
            ->selectRaw($select . '
                MAX(temperatura) as max_temperatura,
                MIN(temperatura) as min_temperatura,
                AVG(temperatura) as avg_temperatura,            
                MAX(co2) as max_co2,
                MIN(co2) as min_co2,
                AVG(co2) as avg_co2,            
                MAX(temperatura_suelo) as max_temperatura_suelo,
                MIN(temperatura_suelo) as min_temperatura_suelo,
                AVG(temperatura_suelo) as avg_temperatura_suelo,
                MAX(conductividad_electrica) as max_conductividad_electrica,
                MIN(conductividad_electrica) as min_conductividad_electrica,
                AVG(conductividad_electrica) as avg_conductividad_electrica,
                MAX(ph) as max_ph,
                MIN(ph) as min_ph,
                AVG(ph) as avg_ph,
                MAX(nit) as max_nit,
                MIN(nit) as min_nit,
                AVG(nit) as avg_nit,
                MAX(phos) as max_phos,
                MIN(phos) as min_phos,
                AVG(phos) as avg_phos,
                MAX(pot) as max_pot,
                MIN(pot) as min_pot,
                AVG(pot) as avg_pot
            ')
            ->groupBy('fecha');

        $this->info("   SQL generado: " . $query->toSql());
        $this->info("   Bindings: " . json_encode($query->getBindings()));

        $resultados = $query->get();
        $this->info("   Resultados obtenidos: " . $resultados->count());

        if ($resultados->count() > 0) {
            $this->info("   Primer resultado:");
            $primer_resultado = $resultados->first()->toArray();
            foreach ($primer_resultado as $key => $value) {
                $this->info("     {$key}: {$value}");
            }
        }

        $this->info("\n=== FIN DEL DIAGNÓSTICO ===");
        return 0;
    }

    private function calcularPeriodo($periodo, $desdeR = null, $hastaR = null)
    {
        $desde = Carbon::now()->format('Y-m-d G:i:s');
        $grupo = '4_horas';

        switch ($periodo) {
            case 1:
                $hasta = Carbon::now()->subHours(24)->format('Y-m-d G:i:s');
                $grupo = '4_horas';
                break;
            case 2:
                $hasta = Carbon::now()->subHours(48)->format('Y-m-d G:i:s');
                $grupo = '4_horas';
                break;
            case 3:
                $hasta = Carbon::now()->subDays(7)->format('Y-m-d G:i:s');
                $grupo = 'd';
                break;
            case 4:
                $hasta = Carbon::now()->subDays(14)->format('Y-m-d G:i:s');
                $grupo = 'd';
                break;
            case 5:
                $hasta = Carbon::now()->subDays(30)->format('Y-m-d G:i:s');
                $grupo = 'd';
                break;
            case 6:
                $hasta = Carbon::now()->subDays(60)->format('Y-m-d G:i:s');
                $grupo = 's';
                break;
            case 7:
                $hasta = Carbon::now()->subDays(180)->format('Y-m-d G:i:s');
                $grupo = 's';
                break;
            case 8:
                $hasta = Carbon::now()->subDays(365)->format('Y-m-d G:i:s');
                $grupo = 'm';
                break;
            case 9:
                $desde = $desdeR . " 00:00:00";
                $hasta = $hastaR . " 23:59:59";
                $grupo = '4_horas';
                break;
            case 10:
                $hasta = Carbon::now()->subHours(24)->format('Y-m-d G:i:s');
                $grupo = '4_horas';
                break;
            case 11:
                $hasta = Carbon::now()->subHours(48)->format('Y-m-d G:i:s');
                $grupo = '4_horas';
                break;
            case 12:
                $hasta = Carbon::now()->subDays(7)->format('Y-m-d G:i:s');
                $grupo = 'd';
                break;
            case 13:
                $hasta = Carbon::now()->subDays(7)->format('Y-m-d G:i:s');
                $grupo = 'd';
                break;
            case 14:
                $hasta = Carbon::now()->subDays(7)->format('Y-m-d G:i:s');
                $grupo = 'd';
                break;
        }
        // Asegurar que $desde sea menor o igual que $hasta
        if (strtotime($desde) > strtotime($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        return array($desde, $hasta, $grupo);
    }

    private function getSelectClause($grupo)
    {
        switch ($grupo) {
            case 'd':
                return 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ';
            case 's':
                return 'DATE_FORMAT(estacion_dato.created_at, "%V") as fecha, ';
            case 'm':
                return 'DATE_FORMAT(estacion_dato.created_at, "%m-%Y") as fecha, ';
            case '4_horas':
                return "CASE
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 0 AND 3  THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' ', 4)
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 4 AND 7  THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' ', 8)
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 8 AND 11 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' ', 12)
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 12 AND 15 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' ', 16)
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 16 AND 19 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' ', 20)
                    ELSE CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' ', 24) END as fecha,";
            case '8_horas':
                return '
                case
                when DATE_FORMAT(estacion_dato.created_at, "%H") between 0 and 7 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," ",8)
                when DATE_FORMAT(estacion_dato.created_at, "%H") between 8 and 15 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," ",16)
                when DATE_FORMAT(estacion_dato.created_at, "%H") between 16 and 23 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," ",24)
                end as fecha,';
            case '12_horas':
                return '
                case
                when DATE_FORMAT(estacion_dato.created_at, "%H") between 0 and 11 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," ",12)
                when DATE_FORMAT(estacion_dato.created_at, "%H") between 12 and 23 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," ",24)
                end as fecha,';
            case 'crudos':
                return 'estacion_dato.created_at as fecha, ';
            default:
                return 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y %H") as fecha, ';
        }
    }
}
