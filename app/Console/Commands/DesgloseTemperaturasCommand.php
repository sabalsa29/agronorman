<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ResumenTemperaturasJob;

class DesgloseTemperaturasCommand extends Command
{
    protected $signature = 'calcular:desglose-temperaturas {--fecha=}';
    protected $description = 'Calcula el desglose de temperaturas para una fecha específica';

    public function handle()
    {
        $fecha = $this->option('fecha');

        $this->info("Calculando desglose de temperaturas para la fecha: " . ($fecha ?: 'hoy'));

        ResumenTemperaturasJob::dispatchSync($fecha);

        $this->info('✅ Cálculo completado exitosamente');
    }
}
