<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\ZonaManejos;
use App\Models\EstacionDato;
use App\Models\Forecast;
use App\Models\ResumenTemperaturas;
use App\Jobs\ResumenTemperaturasJob;

class ResumenTemperaturasCronjob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:resumen-temperaturas-cronjob {--fecha= : Fecha específica (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera resumen de temperaturas para las zonas de manejo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obtener fecha a procesar (ayer por defecto o la especificada)
        $fecha = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))->format('Y-m-d')
            : Carbon::now('America/Mexico_City')->format('Y-m-d');

        $this->info("Procesando resumen de temperaturas para: {$fecha}");

        // Obtener todas las zonas de manejo
        $zonasManejo = ZonaManejos::all();

        $bar = $this->output->createProgressBar($zonasManejo->count());
        $bar->start();

        foreach ($zonasManejo as $zonaManejo) {
            try {
                $desglose = $this->desgloseTemperaturas($zonaManejo, $fecha);

                // Verificar si hay datos válidos para procesar
                if (
                    !$desglose['dia'] || !isset($desglose['dia']['max']) || !isset($desglose['dia']['min']) ||
                    $desglose['dia']['max'] == 0 || $desglose['dia']['min'] == 0
                ) {
                    $this->warn("Sin datos válidos para zona de manejo {$zonaManejo->id} en {$fecha}");
                    $bar->advance();
                    continue;
                }

                // Obtener tipo de cultivo y temperatura base
                $tipoCultivo = $zonaManejo->tipoCultivos->first();
                $tempBaseCalor = $zonaManejo->temp_base_calor ??
                    ($tipoCultivo && $tipoCultivo->cultivo ? $tipoCultivo->cultivo->temp_base_calor : 10);

                // Calcular unidades de calor
                $u = (($desglose['dia']['max'] + $desglose['dia']['min']) / 2) - $tempBaseCalor;

                // Crear o actualizar resumen
                ResumenTemperaturas::updateOrCreate(
                    [
                        'fecha' => $fecha,
                        'zona_manejo_id' => $zonaManejo->id
                    ],
                    [
                        'max_nocturna' => $desglose['nocturnas']['max'] ?? 0,
                        'min_nocturna' => $desglose['nocturnas']['min'] ?? 0,
                        'amp_nocturna' => $desglose['nocturnas']['amplitud'] ?? 0,
                        'max_diurna' => $desglose['diurnas']['max'] ?? 0,
                        'min_diurna' => $desglose['diurnas']['min'] ?? 0,
                        'amp_diurna' => $desglose['diurnas']['amplitud'] ?? 0,
                        'max' => $desglose['dia']['max'] ?? 0,
                        'min' => $desglose['dia']['min'] ?? 0,
                        'amp' => $desglose['dia']['amplitud'] ?? 0,
                        'uc' => $u
                    ]
                );
            } catch (\Exception $e) {
                $this->error("Error procesando zona de manejo {$zonaManejo->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Procesamiento completado para {$fecha}");
    }

    /**
     * Obtiene el desglose de temperaturas para una zona de manejo y fecha
     */
    private function desgloseTemperaturas($zonaManejo, $fecha)
    {
        $desglose = [
            'nocturnas' => null,
            'diurnas' => null,
            'dia' => null
        ];

        // Obtener horas de amanecer y atardecer
        $horas = $this->horasDiaNoche($zonaManejo->parcela_id, $fecha);

        if (!$horas) {
            return $desglose;
        }

        $fechaInicio = Carbon::parse($fecha)->startOfDay();
        $fechaFin = Carbon::parse($fecha)->endOfDay();
        $sunriseTime = Carbon::parse($horas->sunriseTime);
        $sunsetTime = Carbon::parse($horas->sunsetTime);

        // Obtener IDs de estaciones asociadas a esta zona de manejo
        $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

        if (empty($estacionIds)) {
            return $desglose;
        }

        // Temperaturas nocturnas (antes del amanecer y después del atardecer)
        $nocturnas = EstacionDato::whereIn('estacion_id', $estacionIds)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where(function ($query) use ($sunriseTime, $sunsetTime, $fechaInicio, $fechaFin) {
                $query->where(function ($q) use ($sunriseTime, $fechaInicio) {
                    $q->where('created_at', '>=', $fechaInicio)
                        ->where('created_at', '<', $sunriseTime);
                })->orWhere(function ($q) use ($sunsetTime, $fechaFin) {
                    $q->where('created_at', '>', $sunsetTime)
                        ->where('created_at', '<=', $fechaFin);
                });
            })
            ->selectRaw('MAX(temperatura) as max, MIN(temperatura) as min, MAX(temperatura) - MIN(temperatura) as amplitud')
            ->first();

        // Temperaturas diurnas (entre amanecer y atardecer)
        $diurnas = EstacionDato::whereIn('estacion_id', $estacionIds)
            ->whereBetween('created_at', [$sunriseTime, $sunsetTime])
            ->selectRaw('MAX(temperatura) as max, MIN(temperatura) as min, MAX(temperatura) - MIN(temperatura) as amplitud')
            ->first();

        // Temperaturas del día completo
        $dia = EstacionDato::whereIn('estacion_id', $estacionIds)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->selectRaw('MAX(temperatura) as max, MIN(temperatura) as min, MAX(temperatura) - MIN(temperatura) as amplitud')
            ->first();

        $desglose['nocturnas'] = $nocturnas ? $nocturnas->toArray() : ['max' => 0, 'min' => 0, 'amplitud' => 0];
        $desglose['diurnas'] = $diurnas ? $diurnas->toArray() : ['max' => 0, 'min' => 0, 'amplitud' => 0];
        $desglose['dia'] = $dia ? $dia->toArray() : ['max' => 0, 'min' => 0, 'amplitud' => 0];

        return $desglose;
    }

    /**
     * Obtiene las horas de amanecer y atardecer para una parcela y fecha
     */
    private function horasDiaNoche($parcelaId, $fecha)
    {
        return Forecast::where('parcela_id', $parcelaId)
            ->where('fecha_prediccion', $fecha)
            ->where('fecha_solicita', $fecha)
            ->selectRaw('24-TIMESTAMPDIFF(HOUR, sunriseTime, sunsetTime) as horasNoche, 
                        TIMESTAMPDIFF(HOUR, sunriseTime, sunsetTime) as horas, 
                        sunriseTime, sunsetTime')
            ->first();
    }
}
