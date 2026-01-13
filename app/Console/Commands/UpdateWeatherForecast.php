<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\ForeCastController;
use Illuminate\Support\Facades\Log;

class UpdateWeatherForecast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:update {--force : Forzar actualización incluso si ya existen datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los pronósticos del clima para todas las parcelas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌤️  Iniciando actualización de pronósticos del clima...');

        try {
            $controller = new ForeCastController();
            $result = $controller->guardaPronostico();
            $data = $result->getData();

            // Mostrar resultados
            $this->info("✅ Actualización completada exitosamente");
            $this->info("📊 Parcelas procesadas: {$data->parcelas_procesadas}/{$data->total_parcelas}");

            if (!empty($data->warnings)) {
                $this->warn("⚠️  Advertencias:");
                foreach ($data->warnings as $warning) {
                    $this->warn("   - {$warning}");
                }
            }

            // Log del resultado
            Log::info('Comando UpdateWeatherForecast ejecutado exitosamente', [
                'parcelas_procesadas' => $data->parcelas_procesadas,
                'total_parcelas' => $data->total_parcelas,
                'warnings' => $data->warnings ?? []
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error durante la actualización: " . $e->getMessage());
            Log::error('Error en comando UpdateWeatherForecast', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
