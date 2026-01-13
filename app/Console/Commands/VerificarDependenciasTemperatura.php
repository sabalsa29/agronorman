<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VerificarDependenciasTemperatura extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verificar-dependencias-temperatura {--fecha= : Fecha específica (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica todas las dependencias necesarias para el comando de resumen de temperaturas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fecha = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))->format('Y-m-d')
            : Carbon::yesterday()->format('Y-m-d');

        $this->info("Verificando dependencias para: {$fecha}");
        $this->newLine();

        $this->verificarTablasPrincipales();
        $this->verificarTablasRelacion();
        $this->verificarDatosFecha($fecha);
    }

    private function verificarTablasPrincipales()
    {
        $this->info("📋 TABLAS PRINCIPALES:");

        $tablas = [
            'zona_manejos' => 'Zonas de manejo',
            'estacion_dato' => 'Datos de estaciones',
            'forecast' => 'Pronósticos de clima',
            'resumen_temperaturas' => 'Resúmenes de temperatura'
        ];

        foreach ($tablas as $tabla => $descripcion) {
            try {
                $count = DB::table($tabla)->count();
                $this->line("✅ {$descripcion} ({$tabla}): {$count} registros");
            } catch (\Exception $e) {
                $this->error("❌ {$descripcion} ({$tabla}): Error - " . $e->getMessage());
            }
        }
        $this->newLine();
    }

    private function verificarTablasRelacion()
    {
        $this->info("🔗 TABLAS DE RELACIÓN:");

        $tablas = [
            'zona_manejos_estaciones' => 'Relación zonas-estaciones',
            'zona_manejos_tipo_cultivos' => 'Relación zonas-cultivos',
            'estaciones' => 'Estaciones meteorológicas',
            'tipo_cultivos' => 'Tipos de cultivo',
            'cultivos' => 'Cultivos base',
            'parcelas' => 'Parcelas agrícolas'
        ];

        foreach ($tablas as $tabla => $descripcion) {
            try {
                $count = DB::table($tabla)->count();
                $this->line("✅ {$descripcion} ({$tabla}): {$count} registros");
            } catch (\Exception $e) {
                $this->error("❌ {$descripcion} ({$tabla}): Error - " . $e->getMessage());
            }
        }
        $this->newLine();
    }

    private function verificarDatosFecha($fecha)
    {
        $this->info("📅 DATOS PARA LA FECHA {$fecha}:");

        // Verificar datos de estación
        try {
            $datosEstacion = DB::table('estacion_dato')
                ->whereDate('created_at', $fecha)
                ->count();
            $this->line("✅ Datos de estación: {$datosEstacion} registros");
        } catch (\Exception $e) {
            $this->error("❌ Datos de estación: Error - " . $e->getMessage());
        }

        // Verificar forecast
        try {
            $forecast = DB::table('forecast')
                ->where('fecha_prediccion', $fecha)
                ->where('fecha_solicita', $fecha)
                ->count();
            $this->line("✅ Pronósticos de clima: {$forecast} registros");
        } catch (\Exception $e) {
            $this->error("❌ Pronósticos de clima: Error - " . $e->getMessage());
        }

        // Verificar resúmenes existentes
        try {
            $resumenes = DB::table('resumen_temperaturas')
                ->where('fecha', $fecha)
                ->count();
            $this->line("✅ Resúmenes existentes: {$resumenes} registros");
        } catch (\Exception $e) {
            $this->error("❌ Resúmenes existentes: Error - " . $e->getMessage());
        }

        $this->newLine();
    }
}
