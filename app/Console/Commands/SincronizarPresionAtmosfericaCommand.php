<?php

namespace App\Console\Commands;

use App\Jobs\SincronizarPresionAtmosferica;
use Illuminate\Console\Command;

class SincronizarPresionAtmosfericaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presion:sync {--parcela-id= : ID específico de parcela}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar datos de presión atmosférica desde OpenWeather';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de presión atmosférica...');

        try {
            // Ejecutar el Job
            SincronizarPresionAtmosferica::dispatch();

            $this->info('Job de sincronización de presión atmosférica enviado a la cola.');
            $this->info('Los datos se guardarán para las últimas 24 horas desde este momento.');
        } catch (\Exception $e) {
            $this->error('Error al ejecutar la sincronización: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
