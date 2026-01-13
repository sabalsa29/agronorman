<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CalcularUnidadesCalorJob;
use Carbon\Carbon;

class CalcularUnidadesCalorCommand extends Command
{
    protected $signature = 'calcular:unidades-calor {--fecha=}';
    protected $description = 'Calcula las unidades de calor por zona de manejo';

    public function handle()
    {
        $fecha = $this->option('fecha') ?? Carbon::now('America/Mexico_City')->format('Y-m-d');
        $this->info("Calculando unidades de calor para la fecha: $fecha");
        CalcularUnidadesCalorJob::dispatchSync($fecha);
        $this->info('✅ Cálculo completado exitosamente');
    }
}
