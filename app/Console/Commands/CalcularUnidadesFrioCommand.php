<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CalcularUnidadesFrioJob;
use Carbon\Carbon;

class CalcularUnidadesFrioCommand extends Command
{
    protected $signature = 'calcular:unidades-frio {--fecha=}';
    protected $description = 'Calcula las unidades de frío por hora para cada zona de manejo';

    public function handle()
    {
        $fecha = $this->option('fecha') ?? Carbon::now('America/Mexico_City')->format('Y-m-d');
        $this->info("Calculando unidades de frío para la fecha: $fecha");
        CalcularUnidadesFrioJob::dispatchSync($fecha);
        $this->info('✅ Cálculo completado exitosamente');
    }
}
