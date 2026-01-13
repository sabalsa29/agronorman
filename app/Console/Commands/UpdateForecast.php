<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ForeCastController;
use Illuminate\Support\Facades\Log;

class UpdateForecast extends Command
{
    protected $signature = 'forecast:update';
    protected $description = 'Actualiza los pronósticos del clima para todas las parcelas';

    public function handle()
    {
        try {
            $controller = new ForeCastController();
            $response = $controller->guardaPronostico(1);

            // Mostrar la respuesta completa
            $this->info('Respuesta del controlador:');
            $this->line(json_encode($response, JSON_PRETTY_PRINT));

            // Verificar si se guardaron datos
            $this->info('Verificando datos guardados...');
            $forecastCount = \App\Models\Forecast::count();
            $forecastHourlyCount = \App\Models\ForecastHourly::count();

            $this->info("Total de registros en forecast: {$forecastCount}");
            $this->info("Total de registros en forecast_hourlies: {$forecastHourlyCount}");

            if ($forecastCount > 0 && $forecastHourlyCount > 0) {
                $this->info('Pronósticos actualizados exitosamente.');
            } else {
                $this->error('No se guardaron registros en la base de datos.');
            }
        } catch (\Exception $e) {
            $this->error('Error al actualizar pronósticos:');
            $this->error($e->getMessage());
            Log::error('Error en forecast:update: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
