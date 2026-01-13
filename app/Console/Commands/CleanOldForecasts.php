<?php

namespace App\Console\Commands;

use App\Models\Forecast;
use App\Models\ForecastHourly;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOldForecasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forecasts:clean {--days=30 : Número de días para mantener los pronósticos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia pronósticos antiguos de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Limpiando pronósticos más antiguos que {$days} días...");
        $this->info("Fecha de corte: {$cutoffDate->format('Y-m-d')}");

        // Contar registros antes de eliminar
        $forecastsToDelete = Forecast::where('fecha_solicita', '<', $cutoffDate)->count();
        $hourlyToDelete = ForecastHourly::where('fecha', '<', $cutoffDate)->count();

        if ($forecastsToDelete === 0 && $hourlyToDelete === 0) {
            $this->info('No hay pronósticos antiguos para eliminar.');
            return 0;
        }

        $this->info("Se eliminarán {$forecastsToDelete} pronósticos y {$hourlyToDelete} datos horarios.");

        if (!$this->confirm('¿Deseas continuar?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $bar = $this->output->createProgressBar(2);
        $bar->start();

        try {
            DB::beginTransaction();

            // Eliminar datos horarios primero (por la foreign key)
            $deletedHourly = ForecastHourly::where('fecha', '<', $cutoffDate)->delete();
            $bar->advance();

            // Eliminar pronósticos
            $deletedForecasts = Forecast::where('fecha_solicita', '<', $cutoffDate)->delete();
            $bar->advance();

            DB::commit();

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Limpieza completada exitosamente:");
            $this->info("   - Pronósticos eliminados: {$deletedForecasts}");
            $this->info("   - Datos horarios eliminados: {$deletedHourly}");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error durante la limpieza: " . $e->getMessage());
            return 1;
        }
    }
}
