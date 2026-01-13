<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TipoCultivosEnfermedad extends Model
{
    protected $table = 'tipo_cultivos_enfermedades';

    protected $fillable = [
        'id',
        'tipo_cultivo_id',
        'enfermedad_id',
        'riesgo_humedad',
        'riesgo_humedad_max',
        'riesgo_temperatura',
        'riesgo_temperatura_max',
        'riesgo_medio',
        'riesgo_mediciones',
    ];

    // Relación con TipoCultivo
    public function tipoCultivo()
    {
        return $this->belongsTo(TipoCultivos::class);
    }

    // Relación con Enfermedad
    public function enfermedad()
    {
        return $this->belongsTo(Enfermedades::class);
    }

    /**
     * Obtiene el estado completo de la enfermedad
     */
    public function obtenerEstado($enfermedad, $tipo_cultivo, $zonaId, $periodo, $fechaInicial, $fechaFinal)
    {
        $zonaManejo = ZonaManejos::find($zonaId);
        $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

        $fechas = $this->calcularPeriodoExacto($periodo);
        // Calcular desglose SIEMPRE por día, usando el rango de fechas determinado
        $desde = $fechas[1] ?: $fechaInicial;
        $hasta = $fechas[0] ?: $fechaFinal;

        $estado = DB::table('enfermedad_horas_condiciones')
            ->whereIn('estacion_id', $estacionIds)
            ->where('enfermedad_id', $enfermedad)
            ->where('tipo_cultivo_id', $tipo_cultivo)
            ->whereBetween('fecha_ultima_transmision', [$desde, $hasta])
            ->first();

        return $estado;
    }

    /**
     * Obtiene el historial de períodos de condiciones y reinicios
     * Trabaja con enfermedad_horas_acumuladas_condiciones (histórico)
     * Retorna formato de tabla con Inicio, Fin, Acumulado, Estatus
     */
    public function obtenerHistorico($enfermedad, $tipo_cultivo, $zonaId, $periodo, $fechaInicial, $fechaFinal)
    {
        $zonaManejo = ZonaManejos::find($zonaId);
        $estacionIds = $zonaManejo ? $zonaManejo->estaciones->pluck('id')->toArray() : [];

        // Config de enfermedad para determinar estatus
        $enfermedadConfig = DB::table('tipo_cultivos_enfermedades')
            ->where('enfermedad_id', $enfermedad)
            ->where('tipo_cultivo_id', $tipo_cultivo)
            ->first();
        if (!$enfermedadConfig) {
            return collect();
        }

        // Fechas a usar
        $fechas = $this->calcularPeriodoExacto($periodo);
        $desde = $fechas[1] ?: $fechaInicial;
        $hasta = $fechas[0] ?: $fechaFinal;

        // Traer registros tal cual (incluyendo ceros) y listarlos uno a uno, más antiguo primero
        $registros = DB::table('enfermedad_horas_acumuladas_condiciones')
            ->whereIn('estacion_id', $estacionIds)
            ->where('enfermedad_id', $enfermedad)
            ->where('tipo_cultivo_id', $tipo_cultivo)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha', 'asc')
            ->get();

        $historico = collect();

        // Agregar medición actual al inicio
        $medicionActual = $this->obtenerMedicionActual($enfermedad, $tipo_cultivo, $zonaId);
        if ($medicionActual) {
            $historico->push($medicionActual);
        }

        // FASE 1: Agrupar registros consecutivos con minutos = 0
        $registrosAgrupados = collect();
        $grupoReinicio = null;

        foreach ($registros as $r) {
            $min = (int) $r->minutos;
            $fechaActual = Carbon::parse($r->fecha, 'America/Mexico_City');

            if ($min == 0) {
                // Es un reinicio
                if ($grupoReinicio === null) {
                    // Iniciar nuevo grupo de reinicio
                    $grupoReinicio = [
                        'fecha_inicio' => $fechaActual,
                        'fecha_fin' => $fechaActual,
                        'registros' => [$r]
                    ];
                } else {
                    // Agregar al grupo existente si es consecutivo
                    $fechaAnterior = Carbon::parse($grupoReinicio['registros'][count($grupoReinicio['registros']) - 1]->fecha, 'America/Mexico_City');

                    // Verificar si es consecutivo (misma hora o siguiente hora)
                    if ($fechaActual->diffInHours($fechaAnterior) <= 1) {
                        $grupoReinicio['fecha_fin'] = $fechaActual;
                        $grupoReinicio['registros'][] = $r;
                    } else {
                        // No es consecutivo, guardar grupo anterior y crear nuevo
                        $registrosAgrupados->push($grupoReinicio);
                        $grupoReinicio = [
                            'fecha_inicio' => $fechaActual,
                            'fecha_fin' => $fechaActual,
                            'registros' => [$r]
                        ];
                    }
                }
            } else {
                // Es una medición con minutos > 0
                if ($grupoReinicio !== null) {
                    // Guardar grupo de reinicio antes de procesar la medición
                    $registrosAgrupados->push($grupoReinicio);
                    $grupoReinicio = null;
                }

                // Agregar la medición individual
                $registrosAgrupados->push([
                    'fecha_inicio' => $fechaActual->copy()->subMinutes($min),
                    'fecha_fin' => $fechaActual,
                    'registros' => [$r],
                    'es_medicion' => true
                ]);
            }
        }

        // Guardar último grupo de reinicio si quedó abierto
        if ($grupoReinicio !== null) {
            $registrosAgrupados->push($grupoReinicio);
        }

        // FASE 2: Procesar registros agrupados
        foreach ($registrosAgrupados as $grupo) {
            if (isset($grupo['es_medicion'])) {
                // Es una medición individual
                $r = $grupo['registros'][0];
                $fechaFin = Carbon::parse($r->fecha, 'America/Mexico_City');
                $min = (int) $r->minutos;

                // Usar las fechas ya calculadas en la fase 1
                $fechaInicio = $grupo['fecha_inicio'];
                $fechaFin = $grupo['fecha_fin'];

                $historico->push([
                    'tipo' => 'Histórico',
                    'inicio' => $fechaInicio->format('d/m/Y H:i'),
                    'fin' => $fechaFin->format('d/m/Y H:i'),
                    'acumulado' => $min < 60 ? $min . ' min' : round($min / 60, 1) . 'h',
                    'estatus' => $this->determinarEstatusPeriodo($min, $enfermedadConfig),
                    'minutos' => $min,
                    'fecha' => $r->fecha,
                ]);
            } else {
                // Es un grupo de reinicios agrupados
                $fechaInicio = $grupo['fecha_inicio'];
                $fechaFin = $grupo['fecha_fin'];

                $historico->push([
                    'tipo' => 'Reinicio',
                    'inicio' => $fechaInicio->format('d/m/Y H:i'),
                    'fin' => $fechaFin->format('d/m/Y H:i'),
                    'acumulado' => '0 min',
                    'estatus' => 'Bajo',
                    'minutos' => 0,
                    'fecha' => $fechaFin->format('Y-m-d H:i:s'),
                ]);
            }
        }

        return $historico->values();
    }

    public function obtenerMedicionActual($enfermedad, $tipo_cultivo, $zonaId)
    {
        $zonaManejo = ZonaManejos::find($zonaId);
        $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

        // Config de enfermedad para determinar estatus
        $enfermedadConfig = DB::table('tipo_cultivos_enfermedades')
            ->where('enfermedad_id', $enfermedad)
            ->where('tipo_cultivo_id', $tipo_cultivo)
            ->first();
        if (!$enfermedadConfig) {
            return collect();
        }

        $medicion = DB::table('enfermedad_horas_condiciones')
            ->whereIn('estacion_id', $estacionIds)
            ->where('enfermedad_id', $enfermedad)
            ->where('tipo_cultivo_id', $tipo_cultivo)
            ->orderBy('fecha_ultima_transmision', 'desc')
            ->first();

        if (!$medicion) {
            return null;
        }

        $fechaFin = Carbon::parse($medicion->fecha_ultima_transmision, 'America/Mexico_City');
        $min = (int) $medicion->minutos;
        $fechaInicio = $fechaFin->copy()->subMinutes($min);

        $resultado = [
            'tipo' => $min > 0 ? 'Histórico' : 'Reinicio',
            'inicio' => $fechaInicio->format('d/m/Y H:i'),
            'fin' => $fechaFin->format('d/m/Y H:i'),
            'acumulado' => $min < 60 ? $min . ' min' : round($min / 60, 1) . 'h',
            'estatus' => $this->determinarEstatusPeriodo($min, $enfermedadConfig),
            'minutos' => $min,
            'fecha' => $medicion->fecha_ultima_transmision,
        ];

        return $resultado;
    }

    /**
     * Determina el estatus de un período basado en los minutos acumulados
     * Usa los umbrales específicos de la enfermedad desde tipo_cultivos_enfermedades
     */
    private function determinarEstatusPeriodo($minutos, $enfermedad)
    {
        $horas = $minutos / 60;
        $riesgoMedio = $enfermedad->riesgo_medio ?? 4;
        $riesgoMediciones = $enfermedad->riesgo_mediciones ?? 8;

        // Evitar división por cero
        if ($riesgoMediciones <= 0) {
            $riesgoMediciones = 8;
        }

        if ($horas >= $riesgoMediciones) {
            return 'Riesgo';
        } elseif ($horas >= $riesgoMedio) {
            return 'Alerta';
        } else {
            return 'Bajo';
        }
    }

    // Nueva función para periodos exactos de horas
    public function calcularPeriodoExacto($periodo)
    {
        // Obtener la hora actual redondeada hacia abajo (ej: 08:28:00 -> 08:00:00)
        $fin = Carbon::now('America/Mexico_City')->startOfHour();

        switch ($periodo) {
            case 1: // Últimas 24 horas
                $inicio = $fin->copy()->subHours(24);
                break;
            case 2: // Últimas 48 horas
                $inicio = $fin->copy()->subHours(48);
                break;
            case 3: // Última semana (168 horas)
                $inicio = $fin->copy()->subHours(168);
                break;
            case 4: // Últimas 2 semanas (336 horas)
                $inicio = $fin->copy()->subHours(336);
                break;
            case 5: // Último mes (720 horas - 30 días)
                $inicio = $fin->copy()->subHours(720);
                break;
            case 6: // Último bimestre (1440 horas - 60 días)
                $inicio = $fin->copy()->subHours(1440);
                break;
            case 7: // Último semestre (4320 horas - 180 días)
                $inicio = $fin->copy()->subHours(4320);
                break;
            case 8: // Último año (8760 horas - 365 días)
                $inicio = $fin->copy()->subHours(8760);
                break;
            case 9: // Personalizado - usar startDate y endDate
                // Obtener startDate y endDate de la request
                $startDate = request()->get('startDate');
                $endDate = request()->get('endDate');

                if ($startDate && $endDate) {
                    $inicio = Carbon::parse($startDate)->startOfHour();
                    $fin = Carbon::parse($endDate)->startOfHour();
                } else {
                    // Si no hay fechas personalizadas, usar últimas 24 horas
                    $inicio = $fin->copy()->subHours(24);
                }
                break;
            default:
                // Por defecto, últimas 24 horas
                $inicio = $fin->copy()->subHours(24);
                break;
        }

        // Retornar fechas en formato exacto de hora
        return [
            $inicio->format('Y-m-d H:00:00'),  // Hora exacta de inicio
            $fin->format('Y-m-d H:00:00')      // Hora exacta de fin
        ];
    }

    /**
     * Calcula el semáforo de riesgo basado en las horas acumuladas y los umbrales de la enfermedad
     * Usa riesgo_medio como umbral mínimo y riesgo_mediciones como umbral máximo
     */
    public function calcularSemaforoRiesgo($horas, $enfermedad)
    {
        // Validar que las horas sean numéricas
        $horas = is_numeric($horas) ? (float)$horas : 0;

        $riesgoMedio = is_numeric($enfermedad->riesgo_medio) ? (float)$enfermedad->riesgo_medio : 4;
        $riesgoMediciones = is_numeric($enfermedad->riesgo_mediciones) ? (float)$enfermedad->riesgo_mediciones : 8;

        // Evitar división por cero
        if ($riesgoMediciones <= 0) {
            $riesgoMediciones = 8; // Valor por defecto
        }

        // Calcular porcentaje basado en el umbral máximo
        $porcentaje = min(100, ($horas / $riesgoMediciones) * 100);

        // Determinar etapa y color basado en los umbrales
        if ($horas < $riesgoMedio) {
            // Bajo riesgo: verde
            $etapa = 'Bajo Riesgo';
            $color = 'success';
            $colorHex = '#00A14B';
        } elseif ($horas >= $riesgoMedio && $horas < ($riesgoMediciones * 0.9)) {
            // Riesgo medio: amarillo (hasta 90% del umbral máximo)
            $etapa = 'Riesgo Medio';
            $color = 'warning';
            $colorHex = '#FFA500';
        } else {
            // Alto riesgo: rojo (90% o más del umbral máximo)
            $etapa = 'Alto Riesgo';
            $color = 'danger';
            $colorHex = '#FF0000';
        }

        return [
            'porcentaje' => round($porcentaje, 1),
            'etapa' => $etapa,
            'color' => $color,
            'color_hex' => $colorHex,
            'texto' => $etapa,
            'umbral' => $riesgoMedio,
            'umbral_maximo' => $riesgoMediciones,
            'horas_acumuladas' => round($horas, 2)
        ];
    }

    /**
     * Obtiene pronóstico de condiciones favorables para los próximos 2 días
     * Origen: OpenWeather (5-day / 3-hour forecast)
     * Retorna misma estructura que obtenerHistorico():
     * [ tipo => 'Pronóstico', inicio, fin, acumulado (hrs), estatus, minutos, fecha ]
     */
    public function obtenerPronostico($enfermedad, $tipo_cultivo, $zonaId)
    {
        try {
            $zonaManejo = ZonaManejos::find($zonaId);
            if (!$zonaManejo) {
                return collect();
            }

            $estacionId = optional($zonaManejo->estaciones)->pluck('id')->first();
            if (!$estacionId) {
                return collect();
            }

            // Intentar obtener coordenadas a partir de la parcela del cliente de la estación
            $estacion = DB::table('estaciones')->where('id', $estacionId)->first();
            if (!$estacion) {
                return collect();
            }

            $parcela = DB::table('parcelas')->where('cliente_id', $estacion->cliente_id)->first();
            if (!$parcela || !$parcela->lat || !$parcela->lon) {
                return collect();
            }

            // Configuración de enfermedad (umbrales)
            $enfermedadConfig = DB::table('tipo_cultivos_enfermedades')
                ->where('enfermedad_id', $enfermedad)
                ->where('tipo_cultivo_id', $tipo_cultivo)
                ->first();
            if (!$enfermedadConfig) {
                return collect();
            }

            // Config desde config/services.php
            $apiKey = config('services.openweathermap.key');
            if (!$apiKey) {
                Log::warning('OPENWEATHER_API_KEY no configurada');
                return collect();
            }

            $baseUrl = rtrim(config('services.openweathermap.base_url', 'https://api.openweathermap.org/data/2.5'), '/');
            // Asegurar que usemos el endpoint de forecast de 2.5 (el 3.0 no ofrece lo mismo)
            if (str_contains($baseUrl, '/data/3.0')) {
                $baseUrl = 'https://api.openweathermap.org/data/2.5';
            }
            $url = $baseUrl . '/forecast';
            $response = Http::timeout(15)->get($url, [
                'lat' => $parcela->lat,
                'lon' => $parcela->lon,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'es'
            ]);

            if ($response->failed()) {
                Log::warning('OpenWeather forecast request failed', ['status' => $response->status()]);
                return collect();
            }

            $data = $response->json();
            if (!isset($data['list']) || !is_array($data['list'])) {
                return collect();
            }

            // Ventana: exactamente los próximos 2 días calendario (mañana y pasado mañana)
            $ahora = Carbon::now('America/Mexico_City');
            $inicioVentana = $ahora->copy()->startOfDay()->addDay(); // mañana 00:00
            $finVentana = $inicioVentana->copy()->addDay()->endOfDay(); // fin del SEGUNDO día (mañana + 1)

            // Agrupar periodos consecutivos que cumplen condiciones (cada bloque es de 3 horas)
            $grupoInicio = null;
            $grupoFin = null;
            $minutosAcumulados = 0;
            $pronosticos = collect();

            foreach ($data['list'] as $item) {
                $dtTxt = $item['dt_txt'] ?? null;
                if (!$dtTxt) {
                    continue;
                }

                $inicioBloque = Carbon::parse($dtTxt, 'UTC')->setTimezone('America/Mexico_City');
                $finBloque = $inicioBloque->copy()->addHours(3);

                // Solo considerar bloques dentro de mañana y pasado mañana
                if ($inicioBloque->lt($inicioVentana) || $inicioBloque->gt($finVentana)) {
                    continue; // fuera de la ventana de 2 días
                }

                $humedad = (float)($item['main']['humidity'] ?? 0);
                $temperatura = (float)($item['main']['temp'] ?? 0);

                $cumple = $this->cumpleCondicionesPronostico(
                    $humedad,
                    $temperatura,
                    $enfermedadConfig
                );

                if ($cumple) {
                    if ($grupoInicio === null) {
                        $grupoInicio = $inicioBloque->copy();
                        $grupoFin = $finBloque->copy();
                        $minutosAcumulados = 180; // 3h
                    } else {
                        // Consecutivo si el inicio del bloque es igual al fin actual
                        if ($grupoFin !== null && $inicioBloque->equalTo($grupoFin)) {
                            $grupoFin = $finBloque->copy();
                            $minutosAcumulados += 180;
                        } else {
                            // Guardar grupo anterior y reiniciar
                            if ($grupoInicio !== null && $grupoFin !== null) {
                                $pronosticos->push($this->construirEntradaPronostico($grupoInicio, $grupoFin, $minutosAcumulados, $enfermedadConfig));
                            }
                            $grupoInicio = $inicioBloque->copy();
                            $grupoFin = $finBloque->copy();
                            $minutosAcumulados = 180;
                        }
                    }
                } else {
                    if ($grupoInicio !== null && $grupoFin !== null && $minutosAcumulados > 0) {
                        $pronosticos->push($this->construirEntradaPronostico($grupoInicio, $grupoFin, $minutosAcumulados, $enfermedadConfig));
                    }
                    $grupoInicio = null;
                    $grupoFin = null;
                    $minutosAcumulados = 0;
                }
            }

            // Empujar último grupo si quedó abierto
            if ($grupoInicio !== null && $grupoFin !== null && $minutosAcumulados > 0) {
                $pronosticos->push($this->construirEntradaPronostico($grupoInicio, $grupoFin, $minutosAcumulados, $enfermedadConfig));
            }

            // Ordenar de más reciente a más antiguo por fecha fin
            return $pronosticos->sortByDesc('fecha')->values();
        } catch (\Throwable $e) {
            Log::error('Error obtenerPronostico enfermedades: ' . $e->getMessage());
            return collect();
        }
    }

    private function cumpleCondicionesPronostico(float $humedad, float $temperatura, $cfg): bool
    {
        $humedadOk = $humedad >= (float)($cfg->riesgo_humedad ?? 0) && $humedad <= (float)($cfg->riesgo_humedad_max ?? 100);
        $tempOk = $temperatura >= (float)($cfg->riesgo_temperatura ?? -100) && $temperatura <= (float)($cfg->riesgo_temperatura_max ?? 100);
        return $humedadOk && $tempOk;
    }

    private function construirEntradaPronostico(Carbon $inicio, Carbon $fin, int $minutos, $cfg): array
    {
        return [
            'tipo' => 'Pronóstico',
            'inicio' => $inicio->format('d/m/Y H:i'),
            'fin' => $fin->format('d/m/Y H:i'),
            'acumulado' => round($minutos / 60, 1),
            'estatus' => $this->determinarEstatusPeriodo($minutos, $cfg),
            'minutos' => $minutos,
            // Para mantener compatibilidad con el resto del flujo usamos 'fecha' como fin
            'fecha' => $fin->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Suma total de horas pronosticadas (próximas 48h) que cumplen condiciones
     * Retorna en horas decimales con 1 decimal, ej: 7.5
     */
    public function horasPronostico($enfermedad, $tipo_cultivo, $zonaId)
    {
        $periodos = $this->obtenerPronostico($enfermedad, $tipo_cultivo, $zonaId);
        if (!$periodos || $periodos->count() === 0) {
            return '0.0';
        }
        $totalMin = $periodos->sum(function ($p) {
            return (int)($p['minutos'] ?? 0);
        });
        return (string) round($totalMin / 60, 1);
    }
}
