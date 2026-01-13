<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Forecast;
use App\Models\ForecastHourly;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanOldWeatherData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:clean {--days=7 : Número de días a mantener} {--force : Confirmar sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia datos antiguos de pronósticos del clima';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $force = $this->option('force');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("🧹 Iniciando limpieza de datos de pronósticos...");
        $this->info("📅 Eliminando datos anteriores a: {$cutoffDate->format('Y-m-d H:i:s')}");

        // Contar registros a eliminar
        $forecastsToDelete = Forecast::where('fecha_solicita', '<', $cutoffDate)->count();
        $hourliesToDelete = ForecastHourly::where('created_at', '<', $cutoffDate)->count();

        $this->info("📊 Registros a eliminar:");
        $this->info("   - Forecasts: {$forecastsToDelete}");
        $this->info("   - Forecast Hourlies: {$hourliesToDelete}");

        if (!$force) {
            if (!$this->confirm('¿Estás seguro de que quieres eliminar estos registros?')) {
                $this->info('❌ Operación cancelada');
                return Command::SUCCESS;
            }
        }

        try {
            // Eliminar registros relacionados primero
            $forecastIds = Forecast::where('fecha_solicita', '<', $cutoffDate)->pluck('id');
            $deletedHourlies = ForecastHourly::whereIn('forecast_id', $forecastIds)->delete();

            // Eliminar registros de forecast
            $deletedForecasts = Forecast::where('fecha_solicita', '<', $cutoffDate)->delete();

            $this->info("✅ Limpieza completada exitosamente");
            $this->info("🗑️  Registros eliminados:");
            $this->info("   - Forecasts: {$deletedForecasts}");
            $this->info("   - Forecast Hourlies: {$deletedHourlies}");

            // Log del resultado
            Log::info('Comando CleanOldWeatherData ejecutado exitosamente', [
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                'deleted_forecasts' => $deletedForecasts,
                'deleted_hourlies' => $deletedHourlies
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error durante la limpieza: " . $e->getMessage());
            Log::error('Error en comando CleanOldWeatherData', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
