<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanDiseaseDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diseases:clean 
                            {--estacion_id= : ID de la estación específica (opcional)}
                            {--start_date= : Fecha de inicio (YYYY-MM-DD) (opcional)}
                            {--end_date= : Fecha de fin (YYYY-MM-DD) (opcional)}
                            {--enfermedad_id= : ID de enfermedad específica (opcional)}
                            {--tipo_cultivo_id= : ID de tipo de cultivo específico (opcional)}
                            {--dry-run : Solo mostrar qué se eliminaría sin ejecutar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia datos de las tablas de enfermedades (para pruebas)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de datos de enfermedades...');

        // Obtener parámetros
        $estacionId = $this->option('estacion_id');
        $startDate = $this->option('start_date');
        $endDate = $this->option('end_date');
        $enfermedadId = $this->option('enfermedad_id');
        $tipoCultivoId = $this->option('tipo_cultivo_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn("🔍 MODO DRY-RUN: No se realizarán cambios en la base de datos");
        }

        try {
            // Contar registros antes de eliminar
            $registrosAcumulados = $this->contarRegistros('enfermedad_horas_acumuladas_condiciones', $estacionId, $startDate, $endDate, $enfermedadId, $tipoCultivoId);
            $registrosCondiciones = $this->contarRegistros('enfermedad_horas_condiciones', $estacionId, $startDate, $endDate, $enfermedadId, $tipoCultivoId);

            $this->info("📊 Registros a eliminar:");
            $this->info("  - enfermedad_horas_acumuladas_condiciones: {$registrosAcumulados}");
            $this->info("  - enfermedad_horas_condiciones: {$registrosCondiciones}");

            if (!$dryRun) {
                // Eliminar registros
                $eliminadosAcumulados = $this->eliminarRegistros('enfermedad_horas_acumuladas_condiciones', $estacionId, $startDate, $endDate, $enfermedadId, $tipoCultivoId);
                $eliminadosCondiciones = $this->eliminarRegistros('enfermedad_horas_condiciones', $estacionId, $startDate, $endDate, $enfermedadId, $tipoCultivoId);

                $this->info("✅ Eliminados:");
                $this->info("  - enfermedad_horas_acumuladas_condiciones: {$eliminadosAcumulados}");
                $this->info("  - enfermedad_horas_condiciones: {$eliminadosCondiciones}");
            } else {
                $this->warn("💡 Para ejecutar realmente, elimina la opción --dry-run");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error('Error en CleanDiseaseDataCommand', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Cuenta registros en una tabla
     */
    private function contarRegistros($tabla, $estacionId = null, $startDate = null, $endDate = null, $enfermedadId = null, $tipoCultivoId = null)
    {
        $query = DB::table($tabla);

        if ($estacionId) {
            $query->where('estacion_id', $estacionId);
        }

        if ($startDate) {
            $query->where('fecha', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('fecha', '<=', $endDate . ' 23:59:59');
        }

        if ($enfermedadId) {
            $query->where('enfermedad_id', $enfermedadId);
        }

        if ($tipoCultivoId) {
            $query->where('tipo_cultivo_id', $tipoCultivoId);
        }

        return $query->count();
    }

    /**
     * Elimina registros de una tabla
     */
    private function eliminarRegistros($tabla, $estacionId = null, $startDate = null, $endDate = null, $enfermedadId = null, $tipoCultivoId = null)
    {
        $query = DB::table($tabla);

        if ($estacionId) {
            $query->where('estacion_id', $estacionId);
        }

        if ($startDate) {
            $query->where('fecha', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('fecha', '<=', $endDate . ' 23:59:59');
        }

        if ($enfermedadId) {
            $query->where('enfermedad_id', $enfermedadId);
        }

        if ($tipoCultivoId) {
            $query->where('tipo_cultivo_id', $tipoCultivoId);
        }

        return $query->delete();
    }
}
