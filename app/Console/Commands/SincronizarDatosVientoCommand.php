<?php

namespace App\Console\Commands;

use App\Jobs\SincronizarDatosViento;
use Illuminate\Console\Command;

class SincronizarDatosVientoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'viento:sincronizar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar datos de viento desde OpenWeather API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de datos de viento...');

        try {
            // Despachar el job
            SincronizarDatosViento::dispatch();

            $this->info('Job de sincronización de viento despachado exitosamente.');
            $this->info('Revisa los logs para ver el progreso de la sincronización.');
        } catch (\Exception $e) {
            $this->error('Error al despachar el job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
