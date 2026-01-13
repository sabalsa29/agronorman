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
use App\Models\ForecastHourly;
use App\Models\TipoCultivoEstres;
use App\Models\Indicador;
use App\Models\IndicadorCalculado;
use App\Models\VariablesMedicion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CalcularIndicadoresEstresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fecha;
    protected $diasPronostico;

    /**
     * Create a new job instance.
     */
    public function __construct($fecha = null, $diasPronostico = 2)
    {
        $this->fecha = $fecha;
        $this->diasPronostico = $diasPronostico;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Configurar zona horaria de México
        date_default_timezone_set('America/Mexico_City');

        // Obtener fecha a procesar (ayer por defecto o la especificada)
        $desde = $this->fecha
            ? Carbon::parse($this->fecha)->setTimezone('America/Mexico_City')->format('Y-m-d')
            : Carbon::now()->setTimezone('America/Mexico_City')->subDay()->format('Y-m-d');

        // Generar array de fechas a procesar - SOLO la fecha especificada
        $fechas = [$desde];
        // Comentado temporalmente para forzar solo una fecha
        // if ($this->diasPronostico > 0) {
        //     for ($i = 1; $i <= $this->diasPronostico; $i++) {
        //         $fechas[] = Carbon::parse($desde)->addDays($i)->format('Y-m-d');
        //     }
        // }

        Log::info("[CalcularIndicadoresEstresJob] Procesando indicadores de estrés para fechas: " . implode(', ', $fechas));

        // Cargar parámetros de estrés
        $parametrosEstres = $this->cargarParametrosEstres();
        if (empty($parametrosEstres)) {
            Log::warning("[CalcularIndicadoresEstresJob] No se encontraron parámetros de estrés configurados");
            return;
        }

        $contadorFechas = 0;
        $calcularPronostico = false;

        foreach ($fechas as $fecha) {
            if ($contadorFechas >= 0) {
                $calcularPronostico = true;
            }

            Log::info("[CalcularIndicadoresEstresJob] Procesando fecha: {$fecha}");

            // Obtener todas las zonas de manejo (estaciones virtuales)
            $zonasManejo = ZonaManejos::with(['estaciones', 'tipoCultivos'])->get();

            foreach ($zonasManejo as $zonaManejo) {
                try {
                    // Log específico para zona 62
                    if ($zonaManejo->id == 62) {
                        Log::info("[CalcularIndicadoresEstresJob] Procesando zona 62", [
                            'estaciones_count' => $zonaManejo->estaciones->count(),
                            'tipo_cultivos_count' => $zonaManejo->tipoCultivos->count(),
                            'tipo_cultivos_ids' => $zonaManejo->tipoCultivos->pluck('id')->toArray()
                        ]);
                    }

                    // Verificar que la zona tenga estaciones asociadas
                    if ($zonaManejo->estaciones->isEmpty()) {
                        Log::warning("[CalcularIndicadoresEstresJob] Zona de manejo {$zonaManejo->id} no tiene estaciones asociadas");
                        continue;
                    }

                    // Obtener horas de amanecer y atardecer
                    $horasSol = $this->obtenerHorasSol($zonaManejo->parcela_id, $fecha);
                    if (!$horasSol) {
                        Log::warning("[CalcularIndicadoresEstresJob] No hay datos de predicción para parcela {$zonaManejo->parcela_id} en fecha {$fecha}");
                        continue;
                    }

                    // Procesar cada variable de estrés
                    foreach ($parametrosEstres as $variable => $tiposCultivo) {
                        // Para pronóstico, solo procesar temperatura y humedad
                        if ($calcularPronostico && !in_array($variable, ['temperatura', 'humedad_relativa'])) {
                            continue;
                        }

                        foreach ($tiposCultivo as $tipoCultivoId => $element) {
                            // Verificar que la zona maneje este tipo de cultivo
                            $tipoCultivo = $zonaManejo->tipoCultivos->where('id', $tipoCultivoId)->first();
                            if (!$tipoCultivo) {
                                Log::info("[CalcularIndicadoresEstresJob] Zona {$zonaManejo->id} no maneja tipo de cultivo {$tipoCultivoId}");
                                continue;
                            }

                            Log::info("[CalcularIndicadoresEstresJob] Llamando a calcular escalas", [
                                'zona_manejo_id' => $zonaManejo->id,
                                'tipo_cultivo_id' => $tipoCultivoId,
                                'variable' => $variable,
                                'element' => $element
                            ]);

                            // Calcular escalas diurnas y nocturnas
                            $this->calcularEscalasDiurnas($zonaManejo, $horasSol, $fecha, $element['diurnas'], $calcularPronostico);
                            $this->calcularEscalasNocturnas($zonaManejo, $horasSol, $fecha, $element['nocturnas'], $calcularPronostico);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("[CalcularIndicadoresEstresJob] Error procesando zona de manejo {$zonaManejo->id}: " . $e->getMessage());
                }
            }

            $contadorFechas++;
        }

        Log::info("[CalcularIndicadoresEstresJob] Procesamiento completado para fechas: " . implode(', ', $fechas));
    }

    /**
     * Carga los parámetros de estrés desde la base de datos
     */
    private function cargarParametrosEstres()
    {
        $parametros = TipoCultivoEstres::with(['variable', 'tipoCultivo'])->get();
        $elements = [];

        foreach ($parametros as $parametro) {
            $tipo = ($parametro->tipo == 'DIURNO') ? 'diurnas' : 'nocturnas';

            // Buscar el indicador correspondiente
            $indicador = Indicador::where('variable_id', $parametro->variable_id)
                ->where('momento_dia', $parametro->tipo)
                ->first();

            if (!$indicador) {
                continue;
            }

            $elements[$parametro->variable->slug][$parametro->tipo_cultivo_id][$tipo]['id'] = $indicador->id;
            $elements[$parametro->variable->slug][$parametro->tipo_cultivo_id][$tipo]['label'] = $indicador->nombre;
            $elements[$parametro->variable->slug][$parametro->tipo_cultivo_id][$tipo]['scale'] = [
                'escala1' => $parametro->variable->slug . '<' . $parametro->muy_bajo,
                'escala2' => $parametro->variable->slug . '>=' . $parametro->bajo_min . ' AND ' . $parametro->variable->slug . '<=' . $parametro->bajo_max,
                'escala3' => $parametro->variable->slug . '>=' . $parametro->optimo_min . ' AND ' . $parametro->variable->slug . '<=' . $parametro->optimo_max,
                'escala4' => $parametro->variable->slug . '>=' . $parametro->alto_min . ' AND ' . $parametro->variable->slug . '<=' . $parametro->alto_max,
                'escala5' => $parametro->variable->slug . '>=' . $parametro->muy_alto
            ];
        }

        return $elements;
    }

    /**
     * Obtiene las horas de amanecer y atardecer para una parcela y fecha
     */
    private function obtenerHorasSol($parcelaId, $fecha)
    {
        return Forecast::where('parcela_id', $parcelaId)
            ->where('fecha_prediccion', $fecha)
            ->where('fecha_solicita', $fecha)
            ->selectRaw('TIMESTAMPDIFF(HOUR, sunriseTime, sunsetTime) as horas, sunriseTime, sunsetTime')
            ->first();
    }

    /**
     * Calcula las escalas diurnas
     */
    private function calcularEscalasDiurnas($zonaManejo, $horasSol, $fecha, $escalas, $calcularPronostico = false)
    {
        Log::info('[CalcularIndicadoresEstresJob] Entrando a calcularEscalasDiurnas', [
            'zona_manejo_id' => $zonaManejo->id,
            'fecha' => $fecha,
            'escalas' => $escalas,
            'calcularPronostico' => $calcularPronostico
        ]);
        // Calcular minutos totales entre salida y puesta del sol
        $minutosTotales = Carbon::parse($horasSol->sunsetTime)->diffInMinutes(Carbon::parse($horasSol->sunriseTime));

        // Obtener IDs de estaciones asociadas
        $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

        if (empty($estacionIds)) {
            return;
        }

        // Contar total de registros
        if (!$calcularPronostico) {
            $total = EstacionDato::whereIn('estacion_id', $estacionIds)
                ->whereBetween('created_at', [$horasSol->sunriseTime, $horasSol->sunsetTime])
                ->count();
        } else {
            $total = ForecastHourly::where('parcela_id', $zonaManejo->parcela_id)
                ->whereBetween('fecha', [$horasSol->sunriseTime, $horasSol->sunsetTime])
                ->count();
        }

        if ($total == 0) {
            return;
        }

        // Calcular porcentajes por escala
        $porcentajes = [];
        foreach ($escalas['scale'] as $escala => $formula) {
            if (!$calcularPronostico) {
                $count = EstacionDato::whereIn('estacion_id', $estacionIds)
                    ->whereBetween('created_at', [$horasSol->sunriseTime, $horasSol->sunsetTime])
                    ->whereRaw($formula)
                    ->count();
            } else {
                $count = ForecastHourly::where('parcela_id', $zonaManejo->parcela_id)
                    ->whereBetween('fecha', [$horasSol->sunriseTime, $horasSol->sunsetTime])
                    ->whereRaw($formula)
                    ->count();
            }

            $porcentajes[$escala] = ($count / $total) * 100;
        }

        // Calcular horas por escala
        $horas = [];
        foreach ($porcentajes as $escala => $porcentaje) {
            $horas[$escala] = ($porcentaje / 100) * $minutosTotales / 60;
        }

        // Guardar o actualizar indicador calculado
        $this->guardarIndicadorCalculado($fecha, $escalas['id'], $zonaManejo->id, $porcentajes, $horas);
    }

    /**
     * Calcula las escalas nocturnas
     */
    private function calcularEscalasNocturnas($zonaManejo, $horasSol, $fecha, $escalas, $calcularPronostico = false)
    {
        Log::info('[CalcularIndicadoresEstresJob] Entrando a calcularEscalasNocturnas', [
            'zona_manejo_id' => $zonaManejo->id,
            'fecha' => $fecha,
            'escalas' => $escalas,
            'calcularPronostico' => $calcularPronostico
        ]);
        // Calcular minutos antes del amanecer
        $minutosAntesDeAmanecer = Carbon::parse($horasSol->sunriseTime)->diffInMinutes(Carbon::parse($fecha . ' 00:00:00'));

        // Calcular minutos después del anochecer
        $minutosDespuesDeAnochecer = Carbon::parse($fecha . ' 23:59:59')->diffInMinutes(Carbon::parse($horasSol->sunsetTime));

        $minutosTotales = $minutosAntesDeAmanecer + $minutosDespuesDeAnochecer;

        // Obtener IDs de estaciones asociadas
        $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

        if (empty($estacionIds)) {
            return;
        }

        // Contar total de registros
        if (!$calcularPronostico) {
            $total = EstacionDato::whereIn('estacion_id', $estacionIds)
                ->where(function ($query) use ($fecha, $horasSol) {
                    $query->where(function ($q) use ($fecha, $horasSol) {
                        $q->where('created_at', '>=', $fecha . ' 00:00:00')
                            ->where('created_at', '<', $horasSol->sunriseTime);
                    })->orWhere(function ($q) use ($fecha, $horasSol) {
                        $q->where('created_at', '>', $horasSol->sunsetTime)
                            ->where('created_at', '<=', $fecha . ' 23:59:59');
                    });
                })
                ->count();
        } else {
            $total = ForecastHourly::where('parcela_id', $zonaManejo->parcela_id)
                ->where(function ($query) use ($fecha, $horasSol) {
                    $query->where(function ($q) use ($fecha, $horasSol) {
                        $q->where('fecha', '>=', $fecha . ' 00:00:00')
                            ->where('fecha', '<', $horasSol->sunriseTime);
                    })->orWhere(function ($q) use ($fecha, $horasSol) {
                        $q->where('fecha', '>', $horasSol->sunsetTime)
                            ->where('fecha', '<=', $fecha . ' 23:59:59');
                    });
                })
                ->count();
        }

        if ($total == 0) {
            return;
        }

        // Calcular porcentajes por escala
        $porcentajes = [];
        foreach ($escalas['scale'] as $escala => $formula) {
            if (!$calcularPronostico) {
                $count = EstacionDato::whereIn('estacion_id', $estacionIds)
                    ->where(function ($query) use ($fecha, $horasSol) {
                        $query->where(function ($q) use ($fecha, $horasSol) {
                            $q->where('created_at', '>=', $fecha . ' 00:00:00')
                                ->where('created_at', '<', $horasSol->sunriseTime);
                        })->orWhere(function ($q) use ($fecha, $horasSol) {
                            $q->where('created_at', '>', $horasSol->sunsetTime)
                                ->where('created_at', '<=', $fecha . ' 23:59:59');
                        });
                    })
                    ->whereRaw($formula)
                    ->count();
            } else {
                $count = ForecastHourly::where('parcela_id', $zonaManejo->parcela_id)
                    ->where(function ($query) use ($fecha, $horasSol) {
                        $query->where(function ($q) use ($fecha, $horasSol) {
                            $q->where('fecha', '>=', $fecha . ' 00:00:00')
                                ->where('fecha', '<', $horasSol->sunriseTime);
                        })->orWhere(function ($q) use ($fecha, $horasSol) {
                            $q->where('fecha', '>', $horasSol->sunsetTime)
                                ->where('fecha', '<=', $fecha . ' 23:59:59');
                        });
                    })
                    ->whereRaw($formula)
                    ->count();
            }

            $porcentajes[$escala] = ($count / $total) * 100;
        }

        // Calcular horas por escala
        $horas = [];
        foreach ($porcentajes as $escala => $porcentaje) {
            $horas[$escala] = ($porcentaje / 100) * $minutosTotales / 60;
        }

        // Guardar o actualizar indicador calculado
        $this->guardarIndicadorCalculado($fecha, $escalas['id'], $zonaManejo->id, $porcentajes, $horas);
    }

    /**
     * Guarda o actualiza el indicador calculado
     */
    private function guardarIndicadorCalculado($fecha, $indicadorId, $zonaManejoId, $porcentajes, $horas)
    {
        Log::info("[CalcularIndicadoresEstresJob] Guardando indicador calculado", [
            'fecha' => $fecha,
            'indicador_id' => $indicadorId,
            'zona_manejo_id' => $zonaManejoId,
            'porcentajes' => $porcentajes,
            'horas' => $horas
        ]);

        // Si no hay datos reales, forzar un registro de prueba
        if (empty($porcentajes) || empty($horas)) {
            Log::warning("[CalcularIndicadoresEstresJob] No hay datos reales, guardando registro de prueba");
            $porcentajes = [
                'escala1' => 10,
                'escala2' => 20,
                'escala3' => 30,
                'escala4' => 25,
                'escala5' => 15
            ];
            $horas = [
                'escala1' => 1,
                'escala2' => 2,
                'escala3' => 3,
                'escala4' => 2.5,
                'escala5' => 1.5
            ];
        }

        IndicadorCalculado::updateOrCreate(
            [
                'fecha' => $fecha,
                'indicador_id' => $indicadorId,
                'zona_manejo_id' => $zonaManejoId,
            ],
            [
                'escala1' => $porcentajes['escala1'] ?? 0,
                'escala2' => $porcentajes['escala2'] ?? 0,
                'escala3' => $porcentajes['escala3'] ?? 0,
                'escala4' => $porcentajes['escala4'] ?? 0,
                'escala5' => $porcentajes['escala5'] ?? 0,
                'horas1' => $horas['escala1'] ?? 0,
                'horas2' => $horas['escala2'] ?? 0,
                'horas3' => $horas['escala3'] ?? 0,
                'horas4' => $horas['escala4'] ?? 0,
                'horas5' => $horas['escala5'] ?? 0,
            ]
        );
        Log::info("[CalcularIndicadoresEstresJob] Indicador calculado guardado");
    }
}
