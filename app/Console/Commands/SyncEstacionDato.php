<?php

namespace App\Console\Commands;

ini_set('memory_limit', '4G');

use App\Models\EstacionDato;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncEstacionDato extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-estacion-dato {--estacion= : ID de la estación a sincronizar} {--fecha= : Fecha única a sincronizar (formato: YYYY-MM-DD)} {--fecha-inicio= : Fecha inicial del período (formato: YYYY-MM-DD)} {--fecha-fin= : Fecha final del período (formato: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracción de datos de la tabla estacion_dato desde norman_old y sincronización con la tabla estacion_dato_pruebas en la base de datos AWS. Opcionalmente puede filtrar por estación y/o fecha (única o período).';

    /**
     * Execute the console command.
     * This command is scheduled to run hourly in the Kernel.php file
     */
    public function handle()
    {
        $estacionId = $this->option('estacion');
        $fecha = $this->option('fecha');
        $fechaInicio = $this->option('fecha-inicio');
        $fechaFin = $this->option('fecha-fin');

        // Siempre usar estacion_dato_pruebas en AWS
        $tablaDestino = 'estacion_dato_pruebas';

        $this->info('Iniciando sincronización de datos de estación...');
        $this->info("Origen: norman_old -> Destino: aws");
        $this->info("Tabla de destino: {$tablaDestino}");

        if ($estacionId) {
            $this->info("Filtro aplicado: Estación ID = {$estacionId}");
        }

        // Validar que no se usen fecha y período al mismo tiempo
        if ($fecha && ($fechaInicio || $fechaFin)) {
            $this->error('No puede usar --fecha junto con --fecha-inicio o --fecha-fin. Use solo una opción.');
            return 1;
        }

        // Validar que si se usa período, ambas fechas estén presentes
        if (($fechaInicio && !$fechaFin) || (!$fechaInicio && $fechaFin)) {
            $this->error('Si usa un período, debe proporcionar tanto --fecha-inicio como --fecha-fin.');
            return 1;
        }

        // Construir la consulta base - leer desde norman_old
        $query = DB::connection('norman_old')->table('estacion_dato');

        // Aplicar filtro por estación si se proporciona
        if ($estacionId) {
            $query->where('estacion_id', $estacionId);
        }

        // Aplicar filtro por fecha única o período
        if ($fecha) {
            // Validar formato de fecha
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $this->error('Formato de fecha inválido. Use YYYY-MM-DD (ejemplo: 2025-01-15)');
                return 1;
            }
            $this->info("Filtro aplicado: Fecha = {$fecha}");
            // Filtrar por el día completo (desde 00:00:00 hasta 23:59:59)
            $query->whereDate('created_at', $fecha);
        } elseif ($fechaInicio && $fechaFin) {
            // Validar formato de fechas
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
                $this->error('Formato de fecha inválido. Use YYYY-MM-DD (ejemplo: 2025-01-15)');
                return 1;
            }
            // Validar que fecha inicio sea menor o igual a fecha fin
            if ($fechaInicio > $fechaFin) {
                $this->error('La fecha de inicio debe ser menor o igual a la fecha de fin.');
                return 1;
            }
            $this->info("Filtro aplicado: Período = {$fechaInicio} a {$fechaFin}");
            // Filtrar por rango de fechas (desde inicio 00:00:00 hasta fin 23:59:59)
            $query->whereBetween('created_at', [
                $fechaInicio . ' 00:00:00',
                $fechaFin . ' 23:59:59'
            ]);
        }

        // Obtener el total de registros para la barra de progreso
        $total = $query->count();

        if ($total === 0) {
            $this->warn('No se encontraron registros con los filtros aplicados.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $created = 0;
        $batch = [];
        $batchSize = 1000;

        // Reconstruir la consulta para el chunk (ya que se usó para count) - leer desde norman_old
        $chunkQuery = DB::connection('norman_old')->table('estacion_dato');

        if ($estacionId) {
            $chunkQuery->where('estacion_id', $estacionId);
        }

        if ($fecha) {
            $chunkQuery->whereDate('created_at', $fecha);
        } elseif ($fechaInicio && $fechaFin) {
            $chunkQuery->whereBetween('created_at', [
                $fechaInicio . ' 00:00:00',
                $fechaFin . ' 23:59:59'
            ]);
        }

        $chunkQuery
            ->orderBy('id')
            ->chunk(1000, function ($registros) use (&$created, &$batch, $batchSize, $bar, $tablaDestino) {
                foreach ($registros as $registro) {
                    $batch[] = [
                        'id_origen' => $registro->id_origen ?? 0,
                        'radiacion_solar' => $registro->radiacion_solar ?? null,
                        'viento' => $registro->viento ?? null,
                        'precipitacion_acumulada' => $registro->precipitacion_acumulada ?? null,
                        'humedad_relativa' => $registro->humedad_relativa ?? null,
                        'potencial_de_hidrogeno' => $registro->potencial_de_hidrogeno ?? null,
                        'conductividad_electrica' => $registro->conductividad_electrica ?? null,
                        'temperatura' => $registro->temperatura ?? null,
                        'temperatura_lvl1' => $registro->temperatura_lvl1 ?? null,
                        'humedad_15' => $registro->humedad_15 ?? null,
                        'direccion_viento' => $registro->direccion_viento ?? null,
                        'velocidad_viento' => $registro->velocidad_viento ?? null,
                        'co2' => $registro->co2 ?? null,
                        'ph' => $registro->ph ?? null,
                        'phos' => $registro->phos ?? null,
                        'nit' => $registro->nit ?? null,
                        'pot' => $registro->pot ?? null,
                        'estacion_id' => $registro->estacion_id ?? null,
                        'temperatura_suelo' => $registro->temperatura_suelo ?? null,
                        'alertas' => $registro->alertas ?? null,
                        'capacidad_productiva' => $registro->capacidad_productiva ?? null,
                        'bateria' => $registro->bateria ?? null,
                        'created_at' => $registro->created_at,
                        'updated_at' => $registro->updated_at,
                    ];
                    $bar->advance();
                    // Bulk insert cada $batchSize - escribir en aws
                    if (count($batch) >= $batchSize) {
                        // insertOrIgnore retorna el número de filas insertadas
                        $inserted = DB::connection('aws')->table($tablaDestino)->insertOrIgnore($batch);
                        $created += $inserted;
                        $batch = [];
                    }
                }
            });

        // Inserta cualquier remanente - escribir en aws
        if (count($batch) > 0) {
            $inserted = DB::connection('aws')->table($tablaDestino)->insertOrIgnore($batch);
            $created += $inserted;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Sincronización completada.');
        $this->info("Registros creados: {$created}");
        $this->info("Total de registros procesados: {$total}");

        Log::info('SyncEstacionDato ejecutado', [
            'origen' => 'norman_old',
            'destino' => 'aws',
            'creados' => $created,
            'total' => $total,
            'estacion_id' => $estacionId,
            'fecha' => $fecha,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'tabla_destino' => $tablaDestino,
            'timestamp' => now()
        ]);
    }
}
