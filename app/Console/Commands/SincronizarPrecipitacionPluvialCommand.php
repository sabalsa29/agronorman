<?php

namespace App\Console\Commands;

use App\Jobs\SincronizarPrecipitacionPluvial;
use Illuminate\Console\Command;

class SincronizarPrecipitacionPluvialCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'precipitacion:sync {--parcela-id= : ID específico de parcela}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar datos de precipitación pluvial desde OpenWeather';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de precipitación pluvial...');

        try {
            // Ejecutar el Job
            SincronizarPrecipitacionPluvial::dispatch();

            $this->info('Job de sincronización de precipitación pluvial enviado a la cola.');
            $this->info('Los datos se guardarán para las últimas 24 horas desde este momento.');
        } catch (\Exception $e) {
            $this->error('Error al ejecutar la sincronización: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
