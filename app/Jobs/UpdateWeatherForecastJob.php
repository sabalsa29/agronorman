<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\Api\ForeCastController;
use Illuminate\Support\Facades\Log;
use App\Jobs\ResumenTemperaturasJob;

class UpdateWeatherForecastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('🌤️  Iniciando actualización de pronósticos del clima (Job)...');

        try {
            $controller = new ForeCastController();
            $result = $controller->guardaPronostico();
            $data = $result->getData();

            Log::info('✅ Actualización completada exitosamente (Job)', [
                'parcelas_procesadas' => $data->parcelas_procesadas,
                'total_parcelas' => $data->total_parcelas,
                'warnings' => $data->warnings ?? []
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error durante la actualización de pronósticos (Job)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
