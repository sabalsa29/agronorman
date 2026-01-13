<?php

namespace App\Jobs;

use App\Models\ZonaManejos;
use App\Models\EstacionDato;
use App\Models\UnidadesCalorZona;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DB;

class CalcularUnidadesCalorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fecha;

    public function __construct($fecha = null)
    {
        $this->fecha = $fecha ?? Carbon::now('America/Mexico_City')->format('Y-m-d');
    }

    public function handle()
    {
        $fecha = $this->fecha;
        Log::info("[CalcularUnidadesCalorJob] Procesando para fecha $fecha");

        // Obtener solo zonas que tienen estaciones con datos de temperatura para la fecha
        $zonasConDatos = ZonaManejos::whereHas('estaciones.estacionDatos', function ($query) use ($fecha) {
            $query->whereDate('created_at', $fecha);
        })->with(['estaciones' => function ($query) use ($fecha) {
            $query->whereHas('estacionDatos', function ($q) use ($fecha) {
                $q->whereDate('created_at', $fecha);
            });
        }, 'tipoCultivos'])->get();

        Log::info("[CalcularUnidadesCalorJob] Total de zonas con datos a procesar: " . $zonasConDatos->count());

        foreach ($zonasConDatos as $zonaManejo) {
            try {
                Log::info("[CalcularUnidadesCalorJob] Procesando zona {$zonaManejo->id} - {$zonaManejo->nombre}");

                // Verificar que la zona tenga estaciones asociadas
                if ($zonaManejo->estaciones->isEmpty()) {
                    Log::warning("[CalcularUnidadesCalorJob] Zona {$zonaManejo->id} no tiene estaciones asociadas");
                    continue;
                }

                $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();
                Log::info("[CalcularUnidadesCalorJob] Zona {$zonaManejo->id} tiene " . count($estacionIds) . " estaciones: " . implode(',', $estacionIds));

                // Calcular MAX y MIN de temperatura para el día
                $temperaturas = EstacionDato::whereIn('estacion_id', $estacionIds)
                    ->whereDate('created_at', $fecha)
                    ->selectRaw('MAX(temperatura) as max_temp, MIN(temperatura) as min_temp')
                    ->first();

                Log::info("[CalcularUnidadesCalorJob] Zona {$zonaManejo->id} - Temperatura MAX: " . ($temperaturas->max_temp ?? 'NULL') . ", MIN: " . ($temperaturas->min_temp ?? 'NULL'));

                if ($temperaturas->max_temp === null || $temperaturas->min_temp === null) {
                    Log::warning("[CalcularUnidadesCalorJob] Zona {$zonaManejo->id} - No hay datos de temperatura suficientes");
                    continue;
                }

                // Obtener la temperatura base de calor del tipo de cultivo
                $tipoCultivo = $zonaManejo->tipoCultivos->first();
                if (!$tipoCultivo) {
                    Log::warning("[CalcularUnidadesCalorJob] Zona {$zonaManejo->id} - No tiene tipo de cultivo asociado");
                    continue;
                }

                $tempBaseCalor = $tipoCultivo->temp_base_calor ?? 0;
                Log::info("[CalcularUnidadesCalorJob] Zona {$zonaManejo->id} - Temperatura base de calor: {$tempBaseCalor}");

                // Calcular unidades de calor: ((max + min) / 2) - temp_base_calor
                $unidadesCalor = (($temperaturas->max_temp + $temperaturas->min_temp) / 2) - $tempBaseCalor;
                Log::info("[CalcularUnidadesCalorJob] Zona {$zonaManejo->id} - Unidades de calor calculadas: {$unidadesCalor}");

                // Guardar en la base de datos
                try {
                    UnidadesCalorZona::updateOrCreate(
                        [
                            'fecha' => $fecha,
                            'zona_manejo_id' => $zonaManejo->id,
                        ],
                        [
                            'unidades' => $unidadesCalor,
                        ]
                    );
                    Log::info("[CalcularUnidadesCalorJob] ✅ Guardado exitoso - Zona {$zonaManejo->id}");
                } catch (\Exception $e) {
                    Log::error("[CalcularUnidadesCalorJob] ❌ Error al guardar - Zona {$zonaManejo->id} - " . $e->getMessage());
                }

                Log::info("[CalcularUnidadesCalorJob] ✅ Zona {$zonaManejo->id} completada");
            } catch (\Exception $e) {
                Log::error("[CalcularUnidadesCalorJob] Error procesando zona {$zonaManejo->id}: " . $e->getMessage());
            }
        }

        Log::info("[CalcularUnidadesCalorJob] Procesamiento completado para fecha: $fecha");
    }
}
