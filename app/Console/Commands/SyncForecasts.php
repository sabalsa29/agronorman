<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\ForeCastController;
use Illuminate\Console\Command;

class SyncForecasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forecasts:sync {--force : Forzar sincronización sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza pronósticos meteorológicos para todas las parcelas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌤️  Iniciando sincronización de pronósticos meteorológicos...');

        // Verificar configuración
        $apiKey = config('services.openweathermap.key');
        if (empty($apiKey)) {
            $this->error('❌ API key de OpenWeatherMap no configurada');
            $this->error('   Agrega OPENWEATHERMAP_KEY=tu_api_key en tu archivo .env');
            return 1;
        }

        $this->info('✅ API key configurada correctamente');

        if (!$this->option('force') && !$this->confirm('¿Deseas continuar con la sincronización?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $this->info('🔄 Procesando parcelas...');
        $this->newLine();

        try {
            // Crear instancia del controlador y ejecutar la sincronización
            $controller = new ForeCastController();
            $response = $controller->guardaPronostico();

            $data = $response->getData();

            $this->newLine();

            if ($response->getStatusCode() === 200) {
                $this->info('✅ Sincronización completada exitosamente');
                $this->info("   📊 Parcelas procesadas: {$data->parcelas_procesadas}");
                $this->info("   📈 Total de parcelas: {$data->total_parcelas}");

                if (isset($data->warnings) && !empty($data->warnings)) {
                    $this->warn('⚠️  Advertencias encontradas:');
                    foreach ($data->warnings as $warning) {
                        $this->line("   - {$warning}");
                    }
                }
            } else {
                $this->error('❌ Error durante la sincronización');
                if (isset($data->errors)) {
                    foreach ($data->errors as $error) {
                        $this->error("   - {$error}");
                    }
                }
                return 1;
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error inesperado: ' . $e->getMessage());
            return 1;
        }
    }
}
