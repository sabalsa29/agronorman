<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\ZonaManejos;
use App\Models\EstacionDato;
use App\Models\Forecast;
use App\Models\ResumenTemperaturas;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ResumenTemperaturasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fecha;
    public $timeout = 600; // 10 minutos
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct($fecha = null)
    {
        $this->fecha = $fecha;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Obtener fecha a procesar (ayer por defecto o la especificada)
        $fecha = $this->fecha
            ? Carbon::parse($this->fecha)->format('Y-m-d')
            : Carbon::now()->setTimezone('America/Mexico_City')->subDay()->format('Y-m-d');

        Log::info("[ResumenTemperaturasJob] Iniciando procesamiento para: {$fecha}");

        // Verificar si ya existe un resumen para esta fecha
        $resumenExistente = ResumenTemperaturas::where('fecha', $fecha)->count();
        if ($resumenExistente > 0) {
            Log::info("[ResumenTemperaturasJob] Ya existen {$resumenExistente} resúmenes para {$fecha}");
        }

        // Obtener zonas de manejo con estaciones asociadas
        $zonasManejo = ZonaManejos::whereHas('estaciones')->get();

        Log::info("[ResumenTemperaturasJob] Procesando {$zonasManejo->count()} zonas de manejo con estaciones");

        $procesadas = 0;
        $exitosas = 0;
        $fallidas = 0;

        foreach ($zonasManejo as $zonaManejo) {
            $procesadas++;

            try {
                Log::info("[ResumenTemperaturasJob] Procesando zona {$zonaManejo->id} ({$procesadas}/{$zonasManejo->count()})");

                $desglose = $this->desgloseTemperaturas($zonaManejo, $fecha);

                // Verificar si hay datos válidos para procesar
                if (!$this->tieneDatosValidos($desglose)) {
                    Log::warning("[ResumenTemperaturasJob] Zona {$zonaManejo->id}: Sin datos válidos para {$fecha}");
                    $fallidas++;
                    continue;
                }

                // Obtener tipo de cultivo y temperatura base
                $tipoCultivo = $zonaManejo->tipoCultivos->first();
                $tempBaseCalor = $zonaManejo->temp_base_calor ??
                    ($tipoCultivo && $tipoCultivo->cultivo ? $tipoCultivo->cultivo->temp_base_calor : 10);

                // Calcular unidades de calor
                $u = (($desglose['dia']['max'] + $desglose['dia']['min']) / 2) - $tempBaseCalor;
                $uf = max(0, $tempBaseCalor - $desglose['dia']['min']);

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
                        'uc' => $u,
                        'uf' => $uf
                    ]
                );

                Log::info("[ResumenTemperaturasJob] Zona {$zonaManejo->id}: Resumen creado/actualizado exitosamente");
                $exitosas++;
            } catch (\Exception $e) {
                Log::error("[ResumenTemperaturasJob] Error procesando zona {$zonaManejo->id}: " . $e->getMessage());
                Log::error("[ResumenTemperaturasJob] Stack trace: " . $e->getTraceAsString());
                $fallidas++;
            }
        }

        Log::info("[ResumenTemperaturasJob] Procesamiento completado para {$fecha}: {$exitosas} exitosas, {$fallidas} fallidas de {$procesadas} totales");
    }

    /**
     * Verifica si el desglose tiene datos válidos
     */
    private function tieneDatosValidos($desglose)
    {
        // Verificar que existe el desglose del día
        if (!$desglose['dia'] || !isset($desglose['dia']['max']) || !isset($desglose['dia']['min'])) {
            return false;
        }

        // Verificar que las temperaturas no sean 0 o null
        if (
            $desglose['dia']['max'] == 0 || $desglose['dia']['min'] == 0 ||
            $desglose['dia']['max'] === null || $desglose['dia']['min'] === null
        ) {
            return false;
        }

        // Verificar que la temperatura máxima sea mayor que la mínima
        if ($desglose['dia']['max'] <= $desglose['dia']['min']) {
            return false;
        }

        return true;
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
            Log::warning("[ResumenTemperaturasJob] Zona {$zonaManejo->id}: No se encontraron datos de amanecer/atardecer para {$fecha}");
            return $desglose;
        }

        $fechaInicio = Carbon::parse($fecha)->setTimezone('America/Mexico_City')->startOfDay();
        $fechaFin = Carbon::parse($fecha)->setTimezone('America/Mexico_City')->endOfDay();
        $sunriseTime = Carbon::parse($horas->sunriseTime)->setTimezone('America/Mexico_City');
        $sunsetTime = Carbon::parse($horas->sunsetTime)->setTimezone('America/Mexico_City');

        // Obtener IDs de estaciones asociadas a esta zona de manejo
        $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

        if (empty($estacionIds)) {
            Log::warning("[ResumenTemperaturasJob] Zona {$zonaManejo->id}: No tiene estaciones asociadas");
            return $desglose;
        }

        // Verificar que hay datos de estación para la fecha
        $datosExistentes = EstacionDato::whereIn('estacion_id', $estacionIds)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->count();

        if ($datosExistentes == 0) {
            Log::warning("[ResumenTemperaturasJob] Zona {$zonaManejo->id}: No hay datos de estación para {$fecha}");
            return $desglose;
        }

        Log::info("[ResumenTemperaturasJob] Zona {$zonaManejo->id}: Procesando {$datosExistentes} registros de estación");

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

        Log::info("[ResumenTemperaturasJob] Zona {$zonaManejo->id}: Desglose calculado - Día: {$desglose['dia']['max']}°C/{$desglose['dia']['min']}°C");

        return $desglose;
    }

    /**
     * Obtiene las horas de amanecer y atardecer para una parcela y fecha
     */
    private function horasDiaNoche($parcelaId, $fecha)
    {
        $forecast = Forecast::where('parcela_id', $parcelaId)
            ->where('fecha_prediccion', $fecha)
            ->orderBy('fecha_solicita', 'desc')
            ->selectRaw('24-TIMESTAMPDIFF(HOUR, sunriseTime, sunsetTime) as horasNoche, 
                        TIMESTAMPDIFF(HOUR, sunriseTime, sunsetTime) as horas, 
                        sunriseTime, sunsetTime')
            ->first();

        if (!$forecast) {
            Log::warning("[ResumenTemperaturasJob] Parcela {$parcelaId}: No se encontró forecast para {$fecha}");
            return null;
        }

        return $forecast;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("[ResumenTemperaturasJob] Job falló completamente: " . $exception->getMessage());
        Log::error("[ResumenTemperaturasJob] Stack trace: " . $exception->getTraceAsString());
    }
}
