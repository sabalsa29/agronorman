<?php

namespace App\Console\Commands;

use App\Jobs\ProcesarTemperaturaDia;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcesarTemperaturaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temperatura:procesar {fecha?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa la temperatura máxima de un día completo de estación dato';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fecha = $this->argument('fecha');

        if ($fecha) {
            try {
                $fechaCarbon = Carbon::parse($fecha);
            } catch (\Exception $e) {
                $this->error("Fecha inválida: {$fecha}");
                return 1;
            }
        } else {
            $fechaCarbon = Carbon::now('America/Mexico_City')->subDay();
        }

        $this->info("Procesando temperatura para el día: {$fechaCarbon->format('Y-m-d')}");

        // Ejecutar el Job directamente
        $job = new ProcesarTemperaturaDia($fechaCarbon);
        $job->handle();

        $this->info("Procesamiento completado para el día {$fechaCarbon->format('Y-m-d')}");

        return 0;
    }
}
