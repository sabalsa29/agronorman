<?php

namespace App\Jobs;

use App\Models\ZonaManejos;
use App\Models\EstacionDato;
use App\Models\UnidadesFrio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DB;

class CalcularUnidadesFrioJob implements ShouldQueue
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
        Log::info("[CalcularUnidadesFrioJob] Procesando para fecha $fecha");

        // Obtener solo zonas que tienen estaciones con datos de temperatura para la fecha
        $zonasConDatos = ZonaManejos::whereHas('estaciones.estacionDatos', function ($query) use ($fecha) {
            $query->whereDate('created_at', $fecha);
        })->with(['estaciones' => function ($query) use ($fecha) {
            $query->whereHas('estacionDatos', function ($q) use ($fecha) {
                $q->whereDate('created_at', $fecha);
            });
        }])->get();

        Log::info("[CalcularUnidadesFrioJob] Total de zonas con datos a procesar: " . $zonasConDatos->count());

        foreach ($zonasConDatos as $zonaManejo) {
            try {
                Log::info("[CalcularUnidadesFrioJob] Procesando zona {$zonaManejo->id} - {$zonaManejo->nombre}");

                // Verificar que la zona tenga estaciones asociadas
                if ($zonaManejo->estaciones->isEmpty()) {
                    Log::warning("[CalcularUnidadesFrioJob] Zona {$zonaManejo->id} no tiene estaciones asociadas");
                    continue;
                }

                $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();
                Log::info("[CalcularUnidadesFrioJob] Zona {$zonaManejo->id} tiene " . count($estacionIds) . " estaciones: " . implode(',', $estacionIds));

                // Obtener solo las horas que tienen datos para esta zona
                $horasConDatos = EstacionDato::whereIn('estacion_id', $estacionIds)
                    ->whereDate('created_at', $fecha)
                    ->selectRaw('HOUR(created_at) as hora')
                    ->distinct()
                    ->pluck('hora')
                    ->sort()
                    ->values();

                Log::info("[CalcularUnidadesFrioJob] Zona {$zonaManejo->id} tiene datos en " . $horasConDatos->count() . " horas: " . $horasConDatos->implode(', '));

                // Procesar solo las horas que tienen datos
                foreach ($horasConDatos as $hora) {
                    Log::info("[CalcularUnidadesFrioJob] Procesando zona {$zonaManejo->id}, hora {$hora}:00");

                    // Calcular promedio de temperatura para esta hora
                    $temperaturaPromedio = EstacionDato::whereIn('estacion_id', $estacionIds)
                        ->whereDate('created_at', $fecha)
                        ->whereRaw('HOUR(created_at) = ?', [$hora])
                        ->avg('temperatura');

                    Log::info("[CalcularUnidadesFrioJob] Zona {$zonaManejo->id}, hora {$hora}:00 - Temperatura promedio: " . ($temperaturaPromedio ?? 'NULL'));

                    if ($temperaturaPromedio === null) {
                        Log::warning("[CalcularUnidadesFrioJob] Zona {$zonaManejo->id}, hora {$hora}:00 - No hay datos de temperatura");
                        continue;
                    }

                    // Calcular unidad de frío según la escala
                    $unidadFrio = $this->calcularUnidadFrio($temperaturaPromedio);
                    Log::info("[CalcularUnidadesFrioJob] Zona {$zonaManejo->id}, hora {$hora}:00 - Unidad de frío: {$unidadFrio}");

                    // Guardar en la base de datos
                    $fechaHora = $fecha . ' ' . sprintf('%02d', $hora) . ':00:00';

                    try {
                        UnidadesFrio::updateOrCreate(
                            [
                                'fecha' => $fechaHora,
                                'zona_manejo_id' => $zonaManejo->id,
                            ],
                            [
                                'unidades' => $unidadFrio,
                            ]
                        );
                        Log::info("[CalcularUnidadesFrioJob] ✅ Guardado exitoso - Zona {$zonaManejo->id}, hora {$hora}:00");
                    } catch (\Exception $e) {
                        Log::error("[CalcularUnidadesFrioJob] ❌ Error al guardar - Zona {$zonaManejo->id}, hora {$hora}:00 - " . $e->getMessage());
                    }
                }

                Log::info("[CalcularUnidadesFrioJob] ✅ Zona {$zonaManejo->id} completada");
            } catch (\Exception $e) {
                Log::error("[CalcularUnidadesFrioJob] Error procesando zona {$zonaManejo->id}: " . $e->getMessage());
            }
        }

        Log::info("[CalcularUnidadesFrioJob] Procesamiento completado para fecha: $fecha");
    }

    private function calcularUnidadFrio($temperaturaPromedio)
    {
        if ($temperaturaPromedio <= 1.4) return 0;
        if ($temperaturaPromedio > 1.4 && $temperaturaPromedio <= 2.4) return 0.5;
        if ($temperaturaPromedio > 2.4 && $temperaturaPromedio <= 9.1) return 1;
        if ($temperaturaPromedio > 9.1 && $temperaturaPromedio <= 12.4) return 0.5;
        if ($temperaturaPromedio > 12.4 && $temperaturaPromedio <= 15.9) return 0;
        if ($temperaturaPromedio > 15.9 && $temperaturaPromedio <= 18) return -0.5;
        return -1;
    }
}
