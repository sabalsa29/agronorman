<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DatosViento;
use App\Models\EstacionDato;
use App\Models\EtapaFenologica;
use App\Models\EtapaFenologicaTipoCultivo;
use App\Models\ZonaManejos;
use App\Models\NutricionEtapaFenologicaTipoCultivo;
use App\Models\Parcelas;
use App\Models\PresionAtmosferica;
use App\Models\ResumenTemperaturas;
use App\Models\TipoCultivos;
use App\Models\TipoCultivosEnfermedad;
use App\Models\UnidadesFrio;
use App\Models\VariablesMedicion;
use App\Models\ZonaManejosTipoCultivos;
use App\View\Components\Plagas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Calcula bloques dinámicos de 4 horas basados en la hora actual
     * @return array Array con horaInicio y bloques calculados
     */
    private function calcularBloques4Horas()
    {
        $horaActual = Carbon::now('America/Mexico_City')->hour;
        $horaInicio = floor($horaActual / 4) * 4; // Redondear hacia abajo a la hora múltiplo de 4

        return [
            'horaInicio' => $horaInicio,
            'bloques' => [
                $horaInicio,
                $horaInicio - 4,
                $horaInicio - 8,
                $horaInicio - 12,
                $horaInicio - 16,
                $horaInicio - 20
            ]
        ];
    }

    /**
     * Genera el SQL SELECT para agrupar datos por período
     * @param string $periodo El período de agrupación (d, s, m, 4_horas, 8_horas, 12_horas, crudos)
     * @return array Array con 'select' (SQL) y 'tipo' (descripción)
     */
    private function generarSelectPorPeriodo($periodo)
    {
        switch ($periodo) {
            case 'd':
                return [
                    'select' => 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ',
                    'tipo' => 'Día'
                ];
            case 's':
                return [
                    'select' => 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ',
                    'tipo' => 'Semana'
                ];
            case 'm':
                return [
                    'select' => 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ',
                    'tipo' => 'Mes'
                ];
            case '4_horas':
                $bloques = $this->calcularBloques4Horas();
                $horaInicio = $bloques['horaInicio'];

                return [
                    'select' => "CASE
                        WHEN HOUR(estacion_dato.created_at) BETWEEN " . $horaInicio . " AND " . ($horaInicio + 3) . " THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' " . str_pad($horaInicio, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN " . (($horaInicio - 4 + 24) % 24) . " AND " . (($horaInicio - 1 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 4 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN " . (($horaInicio - 8 + 24) % 24) . " AND " . (($horaInicio - 5 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 8 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN " . (($horaInicio - 12 + 24) % 24) . " AND " . (($horaInicio - 9 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 12 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN " . (($horaInicio - 16 + 24) % 24) . " AND " . (($horaInicio - 13 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 16 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN " . (($horaInicio - 20 + 24) % 24) . " AND " . (($horaInicio - 17 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 20 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        ELSE CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 24 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00') END as fecha,",
                    'tipo' => 'Cada 4 horas'
                ];
            case '8_horas':
                return [
                    'select' => '
                    case
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 0 and 7 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 08:00")
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 8 and 15 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 16:00")
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 16 and 23 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 00:00")
                    end as fecha,',
                    'tipo' => 'Cada 8 horas'
                ];
            case '12_horas':
                return [
                    'select' => '
                    case
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 0 and 11 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 12:00")
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 12 and 23 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 00:00")
                    end as fecha,',
                    'tipo' => 'Cada 12 horas'
                ];
            case 'crudos':
                return [
                    'select' => 'estacion_dato.created_at as fecha, ',
                    'tipo' => 'Crudos'
                ];
            default:
                return [
                    'select' => 'estacion_dato.created_at as fecha, ',
                    'tipo' => 'Crudos'
                ];
        }
    }

    /**
     * Generar SELECT para períodos específico para datos de viento
     */
    private function generarSelectPorPeriodoViento($periodo)
    {
        switch ($periodo) {
            case 'd':
                return [
                    'select' => 'DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y") as fecha, ',
                    'tipo' => 'Día'
                ];
            case 's':
                return [
                    'select' => 'DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y") as fecha, ',
                    'tipo' => 'Semana'
                ];
            case 'm':
                return [
                    'select' => 'DATE_FORMAT(fecha_hora_dato, "%m-%Y") as fecha, ',
                    'tipo' => 'Mes'
                ];
            case '4_horas':
                $bloques = $this->calcularBloques4Horas();
                $horaInicio = $bloques['horaInicio'];

                return [
                    'select' => "CASE
                        WHEN HOUR(fecha_hora_dato) BETWEEN " . $horaInicio . " AND " . ($horaInicio + 3) . " THEN CONCAT(DATE_FORMAT(fecha_hora_dato, '%d-%m-%Y'), ' " . str_pad($horaInicio, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(fecha_hora_dato) BETWEEN " . (($horaInicio - 4 + 24) % 24) . " AND " . (($horaInicio - 1 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(fecha_hora_dato, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 4 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(fecha_hora_dato) BETWEEN " . (($horaInicio - 8 + 24) % 24) . " AND " . (($horaInicio - 5 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(fecha_hora_dato, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 8 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(fecha_hora_dato) BETWEEN " . (($horaInicio - 12 + 24) % 24) . " AND " . (($horaInicio - 9 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(fecha_hora_dato, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 12 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(fecha_hora_dato) BETWEEN " . (($horaInicio - 16 + 24) % 24) . " AND " . (($horaInicio - 13 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(fecha_hora_dato, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 16 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        WHEN HOUR(fecha_hora_dato) BETWEEN " . (($horaInicio - 20 + 24) % 24) . " AND " . (($horaInicio - 17 + 24) % 24) . " THEN CONCAT(DATE_FORMAT(fecha_hora_dato, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 20 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00')
                        ELSE CONCAT(DATE_FORMAT(fecha_hora_dato, '%d-%m-%Y'), ' " . str_pad(($horaInicio - 24 + 24) % 24, 2, '0', STR_PAD_LEFT) . ":00') END as fecha,",
                    'tipo' => 'Cada 4 horas'
                ];
            case '8_horas':
                return [
                    'select' => '
                    case
                    when DATE_FORMAT(fecha_hora_dato, "%H") between 0 and 7 then concat(DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y")," 08:00")
                    when DATE_FORMAT(fecha_hora_dato, "%H") between 8 and 15 then concat(DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y")," 16:00")
                    when DATE_FORMAT(fecha_hora_dato, "%H") between 16 and 23 then concat(DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y")," 00:00")
                    end as fecha,',
                    'tipo' => 'Cada 8 horas'
                ];
            case '12_horas':
                return [
                    'select' => '
                    case
                    when DATE_FORMAT(fecha_hora_dato, "%H") between 0 and 11 then concat(DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y")," 12:00")
                    when DATE_FORMAT(fecha_hora_dato, "%H") between 12 and 23 then concat(DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y")," 00:00")
                    end as fecha,',
                    'tipo' => 'Cada 12 horas'
                ];
            case 'crudos':
                return [
                    'select' => 'fecha_hora_dato as fecha, ',
                    'tipo' => 'Crudos'
                ];
            default:
                return [
                    'select' => 'fecha_hora_dato as fecha, ',
                    'tipo' => 'Crudos'
                ];
        }
    }

    public function index(Request $request)
    {
        // Filtrar clientes según el usuario autenticado
        /** @var \App\Models\User|null $user */
        $user = Auth::check() ? Auth::user() : null;

        // Si el usuario no tiene acceso pia, no puede iniciar sesion y se le cierra la secion
        // if ($user && !($user->acceso_app['pia'] ?? false)) {
        //     Auth::logout();
        //     return redirect()->route('login')->withErrors([
        //         'email' => __('auth.no_access'),
        //     ]);
        // }

        if ($user && $user->role_id === 1 && $user->cliente_id === null) {
            // Super admin ve todos los clientes
            $clientes = Cliente::where('status', 1)->orderBy('nombre')->get();
        } elseif ($user && $user->cliente_id) {
            // Usuario normal solo ve su cliente
            $clientes = Cliente::where('status', 1)
                ->where('id', $user->cliente_id)
                ->orderBy('nombre')
                ->get();
        } else {
            // Usuario no autenticado o sin cliente asignado
            $clientes = collect();
        }

        // Obtener zonaManejo y tipoCultivo si están en la request
        $zonaManejo = null;
        $tipoCultivo = null;
        $parcelas = collect();
        $zonaManejoList = collect();
        $tipoCultivoList = collect();
        $etapaFenologicaList = collect();
        $bloqueUno = null;
        $periodo = $request->filled('periodo') ? $request->periodo : null;
        $fechaInicial = $request->filled('startDate') ? $request->startDate : Carbon::now('America/Mexico_City')->format('Y-m-d');
        $fechaFinal = $request->filled('endDate') ? $request->endDate : null;

        if ($request->filled('cliente_id')) {
            $parcelas = Parcelas::where('cliente_id', $request->cliente_id)->orderBy('nombre')->get();
        }

        if ($request->filled('parcela_id')) {
            // Filtrar zonas de manejo según el usuario autenticado
            $user = Auth::check() ? Auth::user() : null;
            $zonaManejoList = ZonaManejos::where('parcela_id', $request->parcela_id)
                ->forUser($user)
                ->get();
        }

        if ($request->filled('zona_manejo_id')) {
            $zonaManejo = ZonaManejos::find($request->zona_manejo_id);
            // Validar que el usuario tenga acceso a esta zona de manejo
            $user = Auth::check() ? Auth::user() : null;
            if ($zonaManejo && $zonaManejo->userHasAccess($user)) {
                // Obtener tipos de cultivo asociados a esta zona de manejo
                $tipoCultivoIds = ZonaManejosTipoCultivos::where('zona_manejo_id', $zonaManejo->id)->pluck('tipo_cultivo_id');
                $tipoCultivoList = TipoCultivos::whereIn('id', $tipoCultivoIds)->get();
            } else {
                $zonaManejo = null; // No tiene acceso, no mostrar datos
            }
        }

        if ($request->filled('tipo_cultivo_id')) {
            $etapaFenologicaIds = EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $request->tipo_cultivo_id)->pluck('etapa_fenologica_id');
            $etapaFenologicaList = EtapaFenologica::whereIn('id', $etapaFenologicaIds)->get();
        }

        // Solo cargar datos si tenemos todos los parámetros necesarios
        if ($zonaManejo && $request->filled('tipo_cultivo_id') && $request->filled('etapa_fenologica_id')) {
            $bloqueUno = $this->cargaDatosEtapafenologica($zonaManejo->id, $request->tipo_cultivo_id, $request->etapa_fenologica_id);
        }

        // Solo cargar datos de unidades si tenemos zona de manejo
        $unidadesChart = null;
        if ($zonaManejo) {
            $unidadesChart = $this->unidadesChart($zonaManejo->id, $periodo, $fechaInicial, $fechaFinal);
        }

        $data = [ 'section_name' => 'Plataforma de inteligencia agronómica',
            'clientes' => $clientes,
            'zonaManejo' => $zonaManejoList,
            'tipoCultivo' => $tipoCultivoList, 
            'parcelas' => $parcelas,
            'zonaManejoModel' => $zonaManejo,
            'tipoCultivoModel' => $tipoCultivo,
            'etapaFenologica' => $etapaFenologicaList,
            'bloqueUno' => $bloqueUno,
            'unidadesChart' => $unidadesChart];

        return view('home.motor', $data);
    }

    // Carga datos de etapas fenológicas
    public function cargaDatosEtapafenologica($zonaId, $tipoCultivoId, $IDsEtapaFenologica)
    {
        $zona_manejo = ZonaManejos::find($zonaId);
        $tipoCultivos = TipoCultivos::find($tipoCultivoId);

        if (!$zona_manejo || !$tipoCultivos) {
            return null;
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $period = EstacionDato::whereIn('estacion_id', $ids)
            ->where('created_at', '>', '1981-01-01 00:00:00')
            ->selectRaw('MIN(created_at) as minDate, MAX(created_at) as maxDate')
            ->first();

        $estadoActual = new ZonaManejos();
        $resultado = $estadoActual->obtenerEstadoActual($zonaId);

        // Validar que $resultado['data'] sea un objeto, no un string
        if (!is_object($resultado['data'])) {
            return [
                'zona_manejo' => [
                    'tipo_suelo'    => $zona_manejo->tipo_suelo->tipo_suelo ?? 'N/A',
                    'cultivo'       => $tipoCultivos->cultivo->nombre ?? 'N/A',
                    'tipo_cultivo'  => $tipoCultivos->nombre ?? 'N/A',
                    'edad_cultivo' => $zona_manejo->edad_cultivo ?? 'S/E',
                ],
                'estacion' => [
                    'ultimaTransmision' => [],
                    'minDate'           => $period->minDate ?? null,
                    'maxDate'           => $period->maxDate ?? null,
                    'semaforo'          => [],
                ],
                'ultima_transmision' => null
            ];
        }

        $semaforo = [];
        $variablesMedicion = VariablesMedicion::all();
        foreach ($variablesMedicion as $variable) {
            $valor = $resultado['data']->{$variable->slug} ?? null;
            if ($variable->slug === 'temperatura') {
                $semaforo[$variable->slug] = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionTemperatura(
                    $tipoCultivoId,
                    $IDsEtapaFenologica,
                    $variable->slug,
                    $valor
                );
            } else {
                $semaforo[$variable->slug] = NutricionEtapaFenologicaTipoCultivo::semaforoNutricion(
                    $tipoCultivoId,
                    $IDsEtapaFenologica,
                    $variable->slug,
                    $valor
                );
            }
        }

        $start = strtotime($resultado['data']->created_at ?? date('Y-m-d H:i:s'));
        $end = strtotime(date('Y-m-d H:i:s'));
        $diasUltimaTransmision = ceil(abs($start - $end) /  (60 * 60 * 24));

        $result = [
            'zona_manejo' => [
                'tipo_suelo'    => $zona_manejo->tipo_suelo->tipo_suelo ?? 'N/A',
                'cultivo'       => $tipoCultivos->cultivo->nombre ?? 'N/A',
                'tipo_cultivo'  => $tipoCultivos->nombre ?? 'N/A',
                'edad_cultivo' => $zona_manejo->edad_cultivo ?? 'S/E',
            ],
            'estacion' => [
                'ultimaTransmision' => (array) $resultado['data'],
                'minDate'           => $period->minDate ?? null,
                'maxDate'           => $period->maxDate ?? null,
                'semaforo'          => $semaforo,
            ],
            'ultima_transmision' => $diasUltimaTransmision
        ];

        return $result; // Retorna array en lugar de JSON
    }

    public function unidadesChart($zonaId, $periodo, $fechaInicial, $fechaFinal)
    {
        $fechas = $this->calcularPeriodoFechas($periodo, $fechaInicial, $fechaFinal);

        // Obtener zona de manejo
        $zonaManejo = ZonaManejos::find($zonaId);
        if (!$zonaManejo) {
            return null;
        }

        // Calcular desglose SIEMPRE por día, usando el rango de fechas determinado
        $desde = $fechas[1] ?: $fechaInicial;
        $hasta = $fechas[0] ?: $fechaFinal;

        $desglose = $this->calcularDesgloseTemperaturas($zonaManejo, $desde, $hasta);

        // Calcular resumen de temperaturas del período completo
        $resumenTemperaturas = ResumenTemperaturas::where('zona_manejo_id', $zonaId)
            ->whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('
                MAX(`max`) as temp_max, 
                MIN(`min`) as temp_min, 
                MAX(`max`) - MIN(`min`) as amplitud, 
                SUM(uc) as uc, 
                SUM(uf) as uf
            ')
            ->first();

        // Si no hay datos en ResumenTemperaturas, calcular desde EstacionDato
        if (!$resumenTemperaturas || !$resumenTemperaturas->temp_max) {
            $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

            if (!empty($estacionIds)) {
                $datosTemperatura = EstacionDato::whereIn('estacion_id', $estacionIds)
                    ->whereBetween('created_at', [$desde, $hasta])
                    ->selectRaw('
                        MAX(temperatura) as temp_max, 
                        MIN(temperatura) as temp_min, 
                        MAX(temperatura) - MIN(temperatura) as amplitud
                    ')
                    ->first();

                if ($datosTemperatura) {
                    $resumenTemperaturas = (object) [
                        'temp_max' => $datosTemperatura->temp_max,
                        'temp_min' => $datosTemperatura->temp_min,
                        'amplitud' => $datosTemperatura->amplitud,
                        'uc' => 0,
                        'uf' => 0
                    ];
                }
            }
        }

        // Calcular unidades de calor acumuladas del período usando los mismos datos que la tabla
        $unidadesCalor = collect($desglose)->sum('uc');

        // Calcular unidades de frío acumuladas del período
        $unidadesFrio = UnidadesFrio::where('zona_manejo_id', $zonaId)
            ->whereBetween('fecha', [$desde, $hasta])
            ->sum('unidades');

        // Si no hay unidades de frío calculadas, usar las del resumen
        if ($unidadesFrio == 0 && $resumenTemperaturas) {
            $unidadesFrio = $resumenTemperaturas->uf ?? 0;
        }

        // Si no hay unidades de calor calculadas, usar las del resumen
        if ($unidadesCalor == 0 && $resumenTemperaturas) {
            $unidadesCalor = $resumenTemperaturas->uc ?? 0;
        }

        // Crear objeto resumen con todos los datos requeridos
        $resumen = (object) [
            'temp_max' => $resumenTemperaturas->temp_max ?? 0,
            'temp_min' => $resumenTemperaturas->temp_min ?? 0,
            'amplitud' => $resumenTemperaturas->amplitud ?? 0,
            'uc' => $unidadesCalor,
            'uf' => $unidadesFrio
        ];

        return [
            'unidadesFrio'  => $unidadesFrio,
            'unidadesCalor' => $unidadesCalor,
            'desglose'      => $desglose,
            'resumen'       => $resumen,
            'desde'         => $desde,
            'hasta'         => $hasta,
            'fechas'        => $fechas,
        ];
    }

    /**
     * Calcula el desglose de temperaturas usando horarios fijos
     * Diurna: 7am a 7pm, Nocturna: 7pm a 7am
     */
    private function calcularDesgloseTemperaturas($zonaManejo, $fechaInicial, $fechaFinal)
    {
        $desglose = [];

        // Obtener IDs de estaciones asociadas a esta zona de manejo
        $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

        if (empty($estacionIds)) {
            return $desglose;
        }

        // Convertir fechas a Carbon - asumir que ya están en zona horaria de México
        $fechaInicio = Carbon::parse($fechaInicial, 'America/Mexico_City');
        $fechaFin = Carbon::parse($fechaFinal, 'America/Mexico_City');

        // Generar array de fechas - trabajar con días completos
        $fechas = [];
        $fechaActual = $fechaInicio->copy();
        $fechaFinComparacion = $fechaFin->copy()->startOfDay();
        while ($fechaActual->lte($fechaFinComparacion)) {
            $fechas[] = $fechaActual->format('Y-m-d');
            $fechaActual->addDay();
        }

        // Revertir el array para orden descendente (más reciente primero)
        $fechas = array_reverse($fechas);

        // Debug temporal para verificar fechas
        \Illuminate\Support\Facades\Log::info('Fechas procesadas en calcularDesgloseTemperaturas:', [
            'fechaInicial' => $fechaInicial,
            'fechaFinal' => $fechaFinal,
            'fechaInicio' => $fechaInicio->format('Y-m-d H:i:s'),
            'fechaFin' => $fechaFin->format('Y-m-d H:i:s'),
            'fechaFinComparacion' => $fechaFinComparacion->format('Y-m-d H:i:s'),
            'fechas_array' => $fechas
        ]);

        foreach ($fechas as $fecha) {
            $fechaCarbon = Carbon::parse($fecha)->setTimezone('America/Mexico_City');

            // Horarios fijos: Diurna 7am-7pm, Nocturna 7pm-7am
            $diurnaInicio = $fechaCarbon->copy()->setTime(7, 0, 0);
            $diurnaFin = $fechaCarbon->copy()->setTime(19, 0, 0);
            $nocturnaInicio1 = $fechaCarbon->copy()->setTime(19, 0, 0);
            $nocturnaFin1 = $fechaCarbon->copy()->addDay()->setTime(7, 0, 0);
            $nocturnaInicio2 = $fechaCarbon->copy()->subDay()->setTime(19, 0, 0);
            $nocturnaFin2 = $fechaCarbon->copy()->setTime(7, 0, 0);

            // Temperaturas diurnas (7am-7pm)
            $diurnas = EstacionDato::whereIn('estacion_id', $estacionIds)
                ->whereBetween('created_at', [$diurnaInicio, $diurnaFin])
                ->selectRaw('MAX(temperatura) as max, MIN(temperatura) as min, MAX(temperatura) - MIN(temperatura) as amplitud')
                ->first();

            // Temperaturas nocturnas (7pm-7am del día siguiente)
            $nocturnas = EstacionDato::whereIn('estacion_id', $estacionIds)
                ->where(function ($query) use ($nocturnaInicio1, $nocturnaFin1, $nocturnaInicio2, $nocturnaFin2) {
                    $query->whereBetween('created_at', [$nocturnaInicio1, $nocturnaFin1])
                        ->orWhereBetween('created_at', [$nocturnaInicio2, $nocturnaFin2]);
                })
                ->selectRaw('MAX(temperatura) as max, MIN(temperatura) as min, MAX(temperatura) - MIN(temperatura) as amplitud')
                ->first();

            // Temperaturas del día completoisa el isai
            $dia = EstacionDato::whereIn('estacion_id', $estacionIds)
                ->whereBetween('created_at', [$fechaCarbon->startOfDay(), $fechaCarbon->endOfDay()])
                ->selectRaw('MAX(temperatura) as max, MIN(temperatura) as min, MAX(temperatura) - MIN(temperatura) as amplitud')
                ->first();

            // Si no hay datos del día completo, usar los datos de diurnas y nocturnas
            if (!$dia || !$dia->max || !$dia->min) {
                $maxDiurna = $diurnas ? $diurnas->max : 0;
                $minDiurna = $diurnas ? $diurnas->min : 0;
                $maxNocturna = $nocturnas ? $nocturnas->max : 0;
                $minNocturna = $nocturnas ? $nocturnas->min : 0;

                $dia = (object) [
                    'max' => max($maxDiurna, $maxNocturna),
                    'min' => min($minDiurna, $minNocturna) ?: min($maxDiurna, $maxNocturna),
                    'amplitud' => max($maxDiurna, $maxNocturna) - min($minDiurna, $minNocturna)
                ];
            }

            // Calcular unidades de calor
            $tipoCultivo = $zonaManejo->tipoCultivos->first();
            $tempBaseCalor = $zonaManejo->temp_base_calor ??
                ($tipoCultivo && $tipoCultivo->cultivo ? $tipoCultivo->cultivo->temp_base_calor : 10);

            $uc = 0;
            if ($dia && $dia->max && $dia->min) {
                $uc = (($dia->max + $dia->min) / 2) - $tempBaseCalor;
                $uc = max(0, $uc); // No puede ser negativo
            }

            $desglose[] = [
                'fecha' => $fecha,
                'max_nocturna' => $nocturnas ? $nocturnas->max : 0,
                'min_nocturna' => $nocturnas ? $nocturnas->min : 0,
                'amp_nocturna' => $nocturnas ? $nocturnas->amplitud : 0,
                'max_diurna' => $diurnas ? $diurnas->max : 0,
                'min_diurna' => $diurnas ? $diurnas->min : 0,
                'amp_diurna' => $diurnas ? $diurnas->amplitud : 0,
                'max' => $dia ? $dia->max : 0,
                'min' => $dia ? $dia->min : 0,
                'amp' => $dia ? $dia->amplitud : 0,
                'uc' => $uc,
                'uf' => 0, // Se calcula por separado en unidades_frio
            ];
        }

        return $desglose;
    }

    // Función para rutas AJAX que necesitan JSON
    public function unidadesChartAjax(Request $request)
    {
        $zonaId = $request->estacion_id;
        $periodo = $request->periodo;
        $fechaInicial = $request->startDate;
        $fechaFinal = $request->endDate;

        $datos = $this->unidadesChart($zonaId, $periodo, $fechaInicial, $fechaFinal);

        if ($datos === null) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        return response()->json($datos);
    }

    public function plagasParcial(Request $request)
    {
        $zonaManejoId = $request->get('zonaManejoId');
        $tipoCultivoId = $request->get('tipoCultivoId');
        $periodo = $request->get('periodo');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $zonaManejo = ZonaManejos::find($zonaManejoId);
        $tipoCultivo = TipoCultivos::find($tipoCultivoId);

        if (!$zonaManejo || !$tipoCultivo) {
            return response()->json(['error' => 'Zona de manejo o tipo de cultivo no encontrado'], 404);
        }

        // Crear una instancia del componente y obtener su HTML
        $component = new Plagas($zonaManejo, $tipoCultivo, $periodo, $startDate, $endDate);
        $html = $component->render();

        return response($html);
    }

    // ========================================
    // SECCIÓN: ENFERMEDADES
    // ========================================

    /**
     * Componente principal para el análisis de enfermedades
     * Obtiene enfermedades asociadas al tipo de cultivo y calcula períodos de riesgo
     */
    public function componentEnfermedades(Request $request)
    {
        $tipoCultivoId = $request->get('tipo_cultivo_id');
        $zonaId = $request->get('zona_manejo_id');
        $periodo = $request->get('periodo');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $enfermedades = collect();
        $pronosticosEnfermedades = collect();
        $datosRealesEnfermedades = collect();

        if ($tipoCultivoId) {
            // Obtener enfermedades asociadas al tipo de cultivo desde la tabla pivote
            $enfermedades = TipoCultivosEnfermedad::with('enfermedad')
                ->where('tipo_cultivo_id', $tipoCultivoId)
                ->whereHas('enfermedad', function ($query) {
                    $query->where('status', 1);
                })
                ->get();

            // Generar pronósticos y datos reales para cada enfermedad
            foreach ($enfermedades as $enfermedad) {
                $enfermedadId = $enfermedad->enfermedad->id;

                // Generar pronóstico (OpenWeather)
                $pronostico = $this->generarPronosticoOpenWeatherEnfermedades($enfermedadId);
                $pronosticosEnfermedades->put($enfermedadId, $pronostico);

                // Generar datos reales (históricos) si hay zona de manejo
                if ($zonaId) {
                    $datosReales = $this->jsonEnfermedades($enfermedadId, $tipoCultivoId, $zonaId, $periodo, $startDate, $endDate);
                    $datosRealesEnfermedades->put($enfermedadId, $datosReales);
                }
            }
        }

        // Aplicar lógica de conteo acumulativo y semáforo a los datos
        $datosRealesEnfermedades = $this->aplicarLogicaSemaforoAcumulativo($datosRealesEnfermedades, $tipoCultivoId);
        $pronosticosEnfermedades = $this->aplicarLogicaSemaforoAcumulativo($pronosticosEnfermedades, $tipoCultivoId);

        return view('components.enfermedades', [
            'enfermedades' => $enfermedades,
            'pronosticosEnfermedades' => $pronosticosEnfermedades,
            'datosRealesEnfermedades' => $datosRealesEnfermedades,
            'tipoCultivoId' => $tipoCultivoId,
            'zonaId' => $zonaId,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Aplica la lógica de semáforo acumulativo a los datos de enfermedades
     * Cuenta horas consecutivas que pasan por el semáforo y asigna niveles de riesgo
     */
    private function aplicarLogicaSemaforoAcumulativo($datosEnfermedades, $tipoCultivoId = null)
    {
        if ($datosEnfermedades->isEmpty()) {
            return $datosEnfermedades;
        }

        $datosProcesados = collect();

        foreach ($datosEnfermedades as $enfermedadId => $datosEnfermedad) {
            // Obtener umbrales de la enfermedad desde la base de datos
            $umbrales = null;
            if ($tipoCultivoId) {
                $umbrales = DB::table('tipo_cultivos_enfermedades')
                    ->where('enfermedad_id', $enfermedadId)
                    ->where('tipo_cultivo_id', $tipoCultivoId)
                    ->first();
            }

            // Valores por defecto si no se encuentran umbrales
            $riesgoMedio = $umbrales ? ($umbrales->riesgo_medio ?? 3) : 3;
            $riesgoMediciones = $umbrales ? ($umbrales->riesgo_mediciones ?? 5) : 5;

            if (isset($datosEnfermedad['resultado'])) {
                $resultado = $datosEnfermedad['resultado'];
                $resultadoProcesado = $this->procesarDatosConConteoAcumulativo($resultado, $riesgoMedio, $riesgoMediciones);

                $datosProcesados->put($enfermedadId, [
                    'resultado' => $resultadoProcesado,
                    'fechasReales' => $datosEnfermedad['fechasReales'] ?? null
                ]);
            } else {
                // Para pronósticos que vienen directamente como collection
                $resultadoProcesado = $this->procesarDatosConConteoAcumulativo($datosEnfermedad, $riesgoMedio, $riesgoMediciones);
                $datosProcesados->put($enfermedadId, $resultadoProcesado);
            }
        }

        return $datosProcesados;
    }

    /**
     * Procesa los datos aplicando la lógica de conteo acumulativo
     */
    private function procesarDatosConConteoAcumulativo($datos, $riesgoMedio = 3, $riesgoMediciones = 5)
    {
        if (!$datos || $datos->isEmpty()) {
            return $datos;
        }

        $datosProcesados = collect();
        $conteoAcumulativo = 0; // Este conteo se mantendrá continuo entre días

        // Invertir el orden para procesar del más antiguo al más reciente
        $datosInvertidos = $datos->reverse();

        foreach ($datosInvertidos as $item) {
            if (isset($item['detalle_horas'])) {
                // El conteo se mantiene continuo entre días
                $detalleHorasProcesado = $this->procesarDetalleHorasConConteo($item['detalle_horas'], $conteoAcumulativo, $riesgoMedio, $riesgoMediciones);

                // Recalcular totales basados en el nuevo estatus
                $sinRiesgo = 0;
                $bajo = 0;
                $alto = 0;

                foreach ($detalleHorasProcesado as $hora) {
                    switch ($hora['estatus']) {
                        case 'Sin riesgo':
                            $sinRiesgo++;
                            break;
                        case 'Bajo':
                            $bajo++;
                            break;
                        case 'Alto':
                            $alto++;
                            break;
                    }
                }

                $datosProcesados->push([
                    'tipo' => $item['tipo'],
                    'fecha' => $item['fecha'],
                    'fecha_formateada' => $item['fecha_formateada'],
                    'sin_riesgo' => $sinRiesgo,
                    'bajo' => $bajo,
                    'alto' => $alto,
                    'total' => $sinRiesgo + $bajo + $alto,
                    'detalle_horas' => $detalleHorasProcesado
                ]);
            } else {
                $datosProcesados->push($item);
            }
        }

        // Invertir el orden final para mantener el orden cronológico original
        return $datosProcesados->reverse();
    }

    /**
     * Procesa el detalle de horas aplicando la lógica de conteo acumulativo
     * Usa umbrales dinámicos de la base de datos: riesgo_medio y riesgo_mediciones
     */
    private function procesarDetalleHorasConConteo($detalleHoras, &$conteoAcumulativo, $riesgoMedio, $riesgoMediciones)
    {
        $horasProcesadas = collect();

        foreach ($detalleHoras as $hora) {
            $condicionesFavorables = $hora['condiciones_favorables'] ?? false;

            if ($condicionesFavorables) {
                // La hora pasa por el semáforo, incrementar conteo
                $conteoAcumulativo++;

                // Determinar nivel de riesgo basado en los umbrales dinámicos
                if ($conteoAcumulativo <= $riesgoMedio) {
                    $estatus = 'Sin riesgo';
                } elseif ($conteoAcumulativo <= $riesgoMediciones) {
                    $estatus = 'Bajo';
                } else {
                    // Mayor a riesgo_mediciones: Mantener en Alto hasta que se reinicie
                    $estatus = 'Alto';
                }
            } else {
                // La hora no pasa por el semáforo, reiniciar conteo
                $conteoAcumulativo = 0;
                $estatus = 'Sin riesgo'; // Por defecto
            }

            $horasProcesadas->push([
                'hora' => $hora['hora'],
                'temperatura' => $hora['temperatura'],
                'humedad' => $hora['humedad'],
                'estatus' => $estatus,
                'condiciones_favorables' => $condicionesFavorables,
                'conteo_acumulativo' => $conteoAcumulativo
            ]);
        }

        return $horasProcesadas;
    }

    /**
     * API para obtener datos de todas las enfermedades de un tipo de cultivo en formato JSON
     * GET /api/enfermedades/tipo-cultivo/{tipoCultivoId}/datos
     */
    public function apiEnfermedadesTipoCultivoDatos(Request $request, $tipoCultivoId)
    {
        try {
            // Validar parámetros requeridos
            $request->validate([
                'zona_manejo_id' => 'required|integer|exists:zona_manejos,id',
                'periodo' => 'nullable|integer|min:1|max:14',
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date'
            ]);

            $zonaId = $request->get('zona_manejo_id');
            $periodo = $request->get('periodo');
            $startDate = $request->get('startDate');
            $endDate = $request->get('endDate');

            // Generar rango de horas entre startDate y endDate
            if ($periodo && $zonaId) {
                // Si se proporciona período y zona, calcular fechas exactas por hora
                $fechasCalculadas = $this->calcularPeriodoExacto($periodo);
                $fechaInicio = $fechasCalculadas[0]; // Fecha de inicio exacta
                $fechaFin = $fechasCalculadas[1];    // Fecha de fin exacta

                // Usar horas exactas sin modificar
                $fechasReales = [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ];
            } else {
                // Si no se proporciona período, usar startDate y endDate con redondeo a hora
                $fechaInicioDefault = Carbon::now('America/Mexico_City')->startOfHour()->subHours(24);
                $fechaFinDefault = Carbon::now('America/Mexico_City')->startOfHour();

                $fechasReales = [
                    'inicio' => $startDate ? Carbon::parse($startDate)->startOfHour()->format('Y-m-d H:i:s') : $fechaInicioDefault->format('Y-m-d H:i:s'),
                    'fin' => $endDate ? Carbon::parse($endDate)->startOfHour()->format('Y-m-d H:i:s') : $fechaFinDefault->format('Y-m-d H:i:s')
                ];
            }

            $enfermedades = collect();
            $pronosticosEnfermedades = collect();
            $datosRealesEnfermedades = collect();

            if ($tipoCultivoId) {
                // Obtener enfermedades asociadas al tipo de cultivo desde la tabla pivote
                $enfermedades = TipoCultivosEnfermedad::with('enfermedad')
                    ->where('tipo_cultivo_id', $tipoCultivoId)
                    ->whereHas('enfermedad', function ($query) {
                        $query->where('status', 1);
                    })
                    ->get();

                // Generar pronósticos y datos reales para cada enfermedad
                foreach ($enfermedades as $enfermedad) {
                    $enfermedadId = $enfermedad->enfermedad->id;

                    // Generar pronóstico (OpenWeather)
                    $pronostico = $this->generarPronosticoOpenWeatherEnfermedades($enfermedadId);
                    $pronosticosEnfermedades->put($enfermedadId, $pronostico);

                    // Generar datos reales (históricos) si hay zona de manejo
                    if ($zonaId) {
                        $datosReales = $this->jsonEnfermedades($enfermedadId, $tipoCultivoId, $zonaId, $periodo, $startDate, $endDate);
                        $datosRealesEnfermedades->put($enfermedadId, $datosReales);
                    }
                }
            }

            // Procesar datos por enfermedad
            $enfermedadesData = collect();
            foreach ($enfermedades as $enfermedad) {
                $enfermedadId = $enfermedad->enfermedad->id;

                // Combinar datos para esta enfermedad específica
                $todosLosDatos = collect();

                // Agregar pronósticos para esta enfermedad
                $pronosticoEnfermedad = $pronosticosEnfermedades->get($enfermedadId, collect());
                foreach ($pronosticoEnfermedad as $item) {
                    $todosLosDatos->push([
                        'tipo' => 'Pronóstico',
                        'fecha_formateada' => $item['fecha_formateada'],
                        'sin_riesgo' => $item['sin_riesgo'],
                        'bajo' => $item['bajo'],
                        'alto' => $item['alto'],
                        'total' => $item['total'],
                    ]);
                }

                // Agregar datos reales para esta enfermedad
                $datosRealesEnfermedad = $datosRealesEnfermedades->get($enfermedadId, collect());
                $resultadoReales = $datosRealesEnfermedad['resultado'] ?? collect();
                foreach ($resultadoReales as $item) {
                    $todosLosDatos->push([
                        'tipo' => 'Actual',
                        'fecha_formateada' => $item['fecha_formateada'],
                        'sin_riesgo' => $item['sin_riesgo'],
                        'bajo' => $item['bajo'],
                        'alto' => $item['alto'],
                        'total' => $item['total'],
                        'detalle_horas' => $item['detalle_horas'] ?? [],
                    ]);
                }

                // Ordenar por fecha (más reciente primero)
                $todosLosDatos = $todosLosDatos->sortByDesc(function ($item) {
                    return Carbon::createFromFormat('d-m-y', $item['fecha_formateada']);
                })->values();

                // Calcular totales para esta enfermedad
                $totalSinRiesgo = $todosLosDatos->sum('sin_riesgo');
                $totalBajo = $todosLosDatos->sum('bajo');
                $totalAlto = $todosLosDatos->sum('alto');
                $totalGeneral = $todosLosDatos->sum('total');

                // Calcular porcentajes para esta enfermedad
                $porcentajeSinRiesgo = $totalGeneral > 0 ? round(($totalSinRiesgo / $totalGeneral) * 100) : 0;
                $porcentajeBajo = $totalGeneral > 0 ? round(($totalBajo / $totalGeneral) * 100) : 0;
                $porcentajeAlto = $totalGeneral > 0 ? round(($totalAlto / $totalGeneral) * 100) : 0;

                $enfermedadesData->push([
                    'enfermedad_id' => $enfermedadId,
                    'porcentajeSinRiesgo' => $porcentajeSinRiesgo,
                    'porcentajeBajo' => $porcentajeBajo,
                    'porcentajeAlto' => $porcentajeAlto,
                    'totalGeneral' => $totalGeneral,
                    'fechas' => 'De: ' . $fechasReales['inicio'] . ' a: ' . $fechasReales['fin']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Datos de las enfermedades obtenidos correctamente',
                'data' => $enfermedadesData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de las enfermedades: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    /**
     * Genera pronósticos reales de OpenWeather para enfermedades
     * Retorna datos agrupados por fecha con conteos de riesgo
     */
    private function generarPronosticoOpenWeatherEnfermedades($enfermedadId)
    {
        try {
            // Obtener los parámetros de la enfermedad desde la base de datos
            $enfermedad = DB::table('enfermedades')->where('id', $enfermedadId)->first();
            if (!$enfermedad) {
                return collect();
            }

            // Obtener la estación para las coordenadas
            $estacion = DB::table('estaciones')->where('id', 65)->first(); // Por defecto estación 65
            if (!$estacion) {
                return collect();
            }

            // Obtener la primera parcela del cliente de la estación
            $parcela = DB::table('parcelas')->where('cliente_id', $estacion->cliente_id)->first();
            if (!$parcela || !$parcela->lat || !$parcela->lon) {
                return collect();
            }

            // Llamada a la API de OpenWeatherMap
            $response = Http::get('https://api.openweathermap.org/data/3.0/onecall', [
                'lat' => $parcela->lat,
                'lon' => $parcela->lon,
                'appid' => config('services.openweathermap.key'),
                'units' => 'metric',
                'tz' => '+06:00',
                'exclude' => 'current,minutely,alerts'
            ]);

            if (!$response->successful()) {
                return collect();
            }

            $openWeatherData = $response->json();
            $datosPorHora = collect();

            // Obtener la hora actual
            $horaActual = Carbon::now('America/Mexico_City');
            $fechaActual = $horaActual->copy()->startOfDay();

            // Calcular las horas restantes del día actual
            // Ejemplo: Si son las 09:00:00, procesamos desde 09:00 hasta 23:00 (15 horas restantes)
            $horaActualRedondeada = $horaActual->copy()->minute(0)->second(0); // Redondear a la hora exacta
            $horasRestantesHoy = 24 - $horaActualRedondeada->hour;

            // Procesar las horas restantes del día actual
            for ($h = 0; $h < $horasRestantesHoy; $h++) {
                $hora = $horaActualRedondeada->copy()->addHours($h);
                $timestamp = $hora->timestamp;

                // Buscar datos de OpenWeather para esta hora
                $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($timestamp) {
                    return $hData['dt'] == $timestamp;
                });

                if ($datoHora) {
                    $temperatura = $datoHora['temp'] ?? 0;
                    $humedad = $datoHora['humidity'] ?? 0;

                    // Verificar si las condiciones son favorables para enfermedades
                    $condicionesFavorables = $this->verificarCondicionesRiesgo(
                        $humedad,
                        $temperatura,
                        $enfermedad->riesgo_humedad ?? 75,
                        $enfermedad->riesgo_humedad_max ?? 100,
                        $enfermedad->riesgo_temperatura ?? 20,
                        $enfermedad->riesgo_temperatura_max ?? 30
                    );

                    // Determinar estatus basado en condiciones
                    $estatus = 'Sin riesgo'; // Por defecto
                    if ($condicionesFavorables) {
                        // Simular diferentes niveles de riesgo basado en la intensidad de las condiciones
                        if ($humedad >= 90 && $temperatura >= 25) {
                            $estatus = 'Alto';
                        } elseif ($humedad >= 80 || $temperatura >= 22) {
                            $estatus = 'Bajo';
                        } else {
                            $estatus = 'Sin riesgo';
                        }
                    }

                    $datosPorHora->push([
                        'fecha' => $hora->format('Y-m-d'),
                        'hora' => $hora->format('H:i'),
                        'temperatura' => $temperatura,
                        'humedad' => $humedad,
                        'estatus' => $estatus,
                        'condiciones_favorables' => $condicionesFavorables
                    ]);
                }
            }

            // Procesar los próximos 2 días completos (48 horas)
            // Esto nos da un total de ~63 horas de pronóstico (15 horas residuales + 48 horas de 2 días futuros)
            for ($dia = 1; $dia <= 2; $dia++) {
                $fechaFutura = $fechaActual->copy()->addDays($dia);

                for ($h = 0; $h < 24; $h++) {
                    $hora = $fechaFutura->copy()->addHours($h);
                    $timestamp = $hora->timestamp;

                    // Buscar datos de OpenWeather para esta hora
                    $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($timestamp) {
                        return $hData['dt'] == $timestamp;
                    });

                    if ($datoHora) {
                        $temperatura = $datoHora['temp'] ?? 0;
                        $humedad = $datoHora['humidity'] ?? 0;

                        // Verificar si las condiciones son favorables para enfermedades
                        $condicionesFavorables = $this->verificarCondicionesRiesgo(
                            $humedad,
                            $temperatura,
                            $enfermedad->riesgo_humedad ?? 75,
                            $enfermedad->riesgo_humedad_max ?? 100,
                            $enfermedad->riesgo_temperatura ?? 20,
                            $enfermedad->riesgo_temperatura_max ?? 30
                        );

                        // Determinar estatus basado en condiciones
                        $estatus = 'Sin riesgo'; // Por defecto
                        if ($condicionesFavorables) {
                            // Simular diferentes niveles de riesgo basado en la intensidad de las condiciones
                            if ($humedad >= 90 && $temperatura >= 25) {
                                $estatus = 'Alto';
                            } elseif ($humedad >= 80 || $temperatura >= 22) {
                                $estatus = 'Bajo';
                            } else {
                                $estatus = 'Sin riesgo';
                            }
                        }

                        $datosPorHora->push([
                            'fecha' => $hora->format('Y-m-d'),
                            'hora' => $hora->format('H:i'),
                            'temperatura' => $temperatura,
                            'humedad' => $humedad,
                            'estatus' => $estatus,
                            'condiciones_favorables' => $condicionesFavorables
                        ]);
                    }
                }
            }

            // Agrupar por fecha y contar por categorías
            $datosAgrupados = $datosPorHora->groupBy('fecha');
            $resultado = collect();

            foreach ($datosAgrupados as $fecha => $horas) {
                $sinRiesgo = $horas->where('estatus', 'Sin riesgo')->count();
                $bajo = $horas->where('estatus', 'Bajo')->count();
                $alto = $horas->where('estatus', 'Alto')->count();
                $total = $horas->count();

                $resultado->push([
                    'tipo' => 'Pronóstico',
                    'fecha' => $fecha,
                    'fecha_formateada' => Carbon::parse($fecha)->format('d-m-y'),
                    'sin_riesgo' => $sinRiesgo,
                    'bajo' => $bajo,
                    'alto' => $alto,
                    'total' => $total,
                    'detalle_horas' => $horas->map(function ($hora) {
                        return [
                            'hora' => $hora['hora'],
                            'temperatura' => $hora['temperatura'],
                            'humedad' => $hora['humedad'],
                            'estatus' => $hora['estatus'],
                            'condiciones_favorables' => $hora['condiciones_favorables'] ?? false
                        ];
                    })
                ]);
            }

            // Ordenar por fecha en orden descendente (más reciente primero)
            return $resultado->sortByDesc(function ($item) {
                return $item['fecha'];
            })->values();
        } catch (\Exception $e) {
            Log::error('Error obteniendo pronósticos de OpenWeather para enfermedades: ' . $e->getMessage());
            return collect();
        }
    }

    public function graficaTemperaturaAtmosferica($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_temperatura_admosferica', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaCO2($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_co2', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaVelocidadViento($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_velocidad_viento', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaPresionAtmosferica($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_presion_atmosferica', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }



    public function graficaPH($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_ph', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaNitrogeno($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_nitrogeno', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaFosforo($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_fosforo', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaPotasio($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_potasio', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaConductividadElectrica($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        // Renderizar la vista del componente con las variables
        return view('components.grafica_conductividad_electrica', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaHumedadRelativaComponente($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', null);
        $endDate = $request->get('endDate', null);
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        return view('components.grafica_humedad_relativa', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function graficaHumedadSueloComponente($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', null);
        $endDate = $request->get('endDate', null);
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        return view('components.grafica_humedad_suelo', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function parcelasPorCliente(Request $request)
    {
        $clienteId = $request->cliente_id;

        $parcelas = Cache::remember('parcelas_cliente_' . $clienteId, 3600, function () use ($clienteId) {
            return Parcelas::where('cliente_id', $clienteId)->orderBy('nombre')->get(['id', 'nombre']);
        });

        return response()->json($parcelas);
    }

    public function zonasPorParcela(Request $request)
    {
        $parcelaId = $request->parcela_id;
        // Filtrar zonas de manejo según el usuario autenticado
        $user = Auth::check() ? Auth::user() : null;
        $zonas = ZonaManejos::where('parcela_id', $parcelaId)
            ->forUser($user)
            ->orderBy('nombre')
            ->get();
        return response()->json($zonas);
    }

    public function ZonaManejosGet(Request $request)
    {
        $zonaId = $request->input('zona');
        $tipoCultivoIds = ZonaManejosTipoCultivos::where('zona_manejo_id', $zonaId)->pluck('tipo_cultivo_id');
        $tiposCultivo = TipoCultivos::whereIn('id', $tipoCultivoIds)->get();

        return response()->json($tiposCultivo);
    }

    public function etapasFenologicasPorTipoDeCultivo(Request $request)
    {
        $tipoCultivoIds = $request->input('tipo_cultivo_id');
        $IDsEtapaFenologica = EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $tipoCultivoIds)->pluck('etapa_fenologica_id');
        $etapasFenologicas = EtapaFenologica::whereIn('id', $IDsEtapaFenologica)->get();
        return response()->json($etapasFenologicas);
    }

    public function view_grafica_temperatura_admosferica(Request $request)
    {
        $zona_manejo = ZonaManejos::find($request->zona_manejo);
        $periodo = $request->periodo;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $tipo_cultivo_id = $request->tipo_cultivo_id;
        $etapa_fenologica_id = $request->etapa_fenologica_id;

        return view('components.grafica_temperatura_admosferica', [
            'zonaManejo' => $zona_manejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function grafica_temperatura(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(temperatura) AS max_temperatura,
                MIN(temperatura) AS min_temperatura,
                AVG(temperatura) AS avg_temperatura
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . '
                MAX(temperatura) as max_temperatura,
                MIN(temperatura) as min_temperatura,
                AVG(temperatura) as avg_temperatura,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }


        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosTemperatura   = array_column($rows, 'max_temperatura');
        $minimosTemperatura   = array_column($rows, 'min_temperatura');
        $promediosTemperatura = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_temperatura'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosTemperatura'   => $maximosTemperatura,
            'minimosTemperatura'   => $minimosTemperatura,
            'promediosTemperatura' => $promediosTemperatura,
            'desde' => $fechas[1],
            'hasta' => $fechas[0],
            'tipo' => $fechas[2],
        ]);
    }

    public function grafica_co2(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(co2) AS max_co2,
                MIN(co2) AS min_co2,
                AVG(co2) AS avg_co2
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . '
                MAX(co2) as max_co2,
                MIN(co2) as min_co2,
                AVG(co2) as avg_co2,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosCo2   = array_column($rows, 'max_co2');
        $minimosCo2   = array_column($rows, 'min_co2');
        $promediosCo2 = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_co2'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosCo2'   => $maximosCo2,
            'minimosCo2'   => $minimosCo2,
            'promediosCo2' => $promediosCo2,
        ]);
    }

    public function grafica_ph(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(ph) AS max_ph,
                MIN(ph) AS min_ph,
                AVG(ph) AS avg_ph
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . '
                MAX(ph) as max_ph,
                MIN(ph) as min_ph,
                AVG(ph) as avg_ph,
                DATE(created_at) as fecha_real
                ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosPh   = array_column($rows, 'max_ph');
        $minimosPh   = array_column($rows, 'min_ph');
        $promediosPh = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_ph'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosPh'   => $maximosPh,
            'minimosPh'   => $minimosPh,
            'promediosPh' => $promediosPh,
        ]);
    }

    public function grafica_nitrogeno(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(nit) AS max_nit,
                MIN(nit) AS min_nit,
                AVG(nit) AS avg_nit
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . ' 
                MAX(nit) as max_nit,  
                MIN(nit) as min_nit,
                AVG(nit) as avg_nit,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosNitrogeno   = array_column($rows, 'max_nit');
        $minimosNitrogeno   = array_column($rows, 'min_nit');
        $promediosNitrogeno = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_nit'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosNitrogeno'   => $maximosNitrogeno,
            'minimosNitrogeno'   => $minimosNitrogeno,
            'promediosNitrogeno' => $promediosNitrogeno,
        ]);
    }

    public function grafica_fosforo(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(phos) AS max_phos,
                MIN(phos) AS min_phos,
                AVG(phos) AS avg_phos
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {

            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . ' 
                MAX(phos) as max_phos,  
                MIN(phos) as min_phos,
                AVG(phos) as avg_phos,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosFosforo   = array_column($rows, 'max_phos');
        $minimosFosforo   = array_column($rows, 'min_phos');
        $promediosFosforo = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_phos'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosFosforo'   => $maximosFosforo,
            'minimosFosforo'   => $minimosFosforo,
            'promediosFosforo' => $promediosFosforo,
        ]);
    }

    public function grafica_potasio(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];
        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(pot) AS max_pot,
                MIN(pot) AS min_pot,
                AVG(pot) AS avg_pot
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . ' 
                MAX(pot) as max_pot,  
                MIN(pot) as min_pot,
                AVG(pot) as avg_pot,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosPotasio   = array_column($rows, 'max_pot');
        $minimosPotasio   = array_column($rows, 'min_pot');
        $promediosPotasio = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_pot'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosPotasio'   => $maximosPotasio,
            'minimosPotasio'   => $minimosPotasio,
            'promediosPotasio' => $promediosPotasio,
        ]);
    }

    public function grafica_conductividad_electrica(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];
        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(conductividad_electrica) AS max_conductividad_electrica,
                MIN(conductividad_electrica) AS min_conductividad_electrica,
                AVG(conductividad_electrica) AS avg_conductividad_electrica
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . ' 
                MAX(conductividad_electrica) as max_conductividad_electrica,  
                MIN(conductividad_electrica) as min_conductividad_electrica,
                AVG(conductividad_electrica) as avg_conductividad_electrica,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosConductividadElectrica   = array_column($rows, 'max_conductividad_electrica');
        $minimosConductividadElectrica   = array_column($rows, 'min_conductividad_electrica');
        $promediosConductividadElectrica = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_conductividad_electrica'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosConductividadElectrica'   => $maximosConductividadElectrica,
            'minimosConductividadElectrica'   => $minimosConductividadElectrica,
            'promediosConductividadElectrica' => $promediosConductividadElectrica,
        ]);
    }

    public function grafica_humedad_relativa(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];
        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(humedad_relativa) AS max_humedad_relativa,
                MIN(humedad_relativa) AS min_humedad_relativa,
                AVG(humedad_relativa) AS avg_humedad_relativa
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . '
                MAX(humedad_relativa) as max_humedad_relativa,
                MIN(humedad_relativa) as min_humedad_relativa,
                AVG(humedad_relativa) as avg_humedad_relativa,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosHumedad   = array_column($rows, 'max_humedad_relativa');
        $minimosHumedad   = array_column($rows, 'min_humedad_relativa');
        $promediosHumedad = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_humedad_relativa'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosHumedad'   => $maximosHumedad,
            'minimosHumedad'   => $minimosHumedad,
            'promediosHumedad' => $promediosHumedad,
        ]);
    }

    public function grafica_velocidad_viento(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $select = '';
        $selectData = $this->generarSelectPorPeriodoViento($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = DatosViento::where('zona_manejo_id', $zona_manejo->id)
                ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(fecha_hora_dato, "%Y-%m") AS fecha,
                MAX(wind_speed) AS max_velocidad_viento,
                MIN(wind_speed) AS min_velocidad_viento,
                AVG(wind_speed) AS avg_velocidad_viento
            ')
                ->groupByRaw('DATE_FORMAT(fecha_hora_dato, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(fecha_hora_dato, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = DatosViento::where('zona_manejo_id', $zona_manejo->id)
                ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
                ->selectRaw($select . '
                MAX(wind_speed) as max_velocidad_viento,
                MIN(wind_speed) as min_velocidad_viento,
                AVG(wind_speed) as avg_velocidad_viento,
                DATE(fecha_hora_dato) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels          = array_column($rows, 'fecha');
        $maximosViento   = array_column($rows, 'max_velocidad_viento');
        $minimosViento   = array_column($rows, 'min_velocidad_viento');
        $promediosViento = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_velocidad_viento'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'            => $labels,
            'maximosViento'     => $maximosViento,
            'minimosViento'     => $minimosViento,
            'promediosViento'   => $promediosViento,
        ]);
    }

    public function grafica_presion_atmosferica(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $select = '';
        $selectData = $this->generarSelectPorPeriodoViento($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = PresionAtmosferica::where('zona_manejo_id', $zona_manejo->id)
                ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(fecha_hora_dato, "%Y-%m") AS fecha,
                MAX(pressure) AS max_presion_atmosferica,
                MIN(pressure) AS min_presion_atmosferica,
                AVG(pressure) AS avg_presion_atmosferica
            ')
                ->groupByRaw('DATE_FORMAT(fecha_hora_dato, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(fecha_hora_dato, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = PresionAtmosferica::where('zona_manejo_id', $zona_manejo->id)
                ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
                ->selectRaw($select . '
                MAX(pressure) as max_presion_atmosferica,
                MIN(pressure) as min_presion_atmosferica,
                AVG(pressure) as avg_presion_atmosferica,
                DATE(fecha_hora_dato) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels          = array_column($rows, 'fecha');
        $maximosPresionAtmosferica   = array_column($rows, 'max_presion_atmosferica');
        $minimosPresionAtmosferica   = array_column($rows, 'min_presion_atmosferica');
        $promediosPresionAtmosferica = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_presion_atmosferica'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'            => $labels,
            'maximosPresionAtmosferica'     => $maximosPresionAtmosferica,
            'minimosPresionAtmosferica'     => $minimosPresionAtmosferica,
            'promediosPresionAtmosferica'   => $promediosPresionAtmosferica,
        ]);
    }

    public function grafica_humedad_suelo(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(humedad_15) AS max_humedad_suelo,
                MIN(humedad_15) AS min_humedad_suelo,
                AVG(humedad_15) AS avg_humedad_suelo
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . '
                MAX(humedad_15) as max_humedad_suelo,
                MIN(humedad_15) as min_humedad_suelo,
                AVG(humedad_15) as avg_humedad_suelo,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosHumedadSuelo   = array_column($rows, 'max_humedad_suelo');
        $minimosHumedadSuelo   = array_column($rows, 'min_humedad_suelo');
        $promediosHumedadSuelo = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_humedad_suelo'));

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosHumedadSuelo'   => $maximosHumedadSuelo,
            'minimosHumedadSuelo'   => $minimosHumedadSuelo,
            'promediosHumedadSuelo' => $promediosHumedadSuelo,
        ]);
    }

    public function grafica_temperatura_suelo(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        $selectData = $this->generarSelectPorPeriodo($fechas[2]);
        $select = $selectData['select'];
        $tipo = $selectData['tipo'];

        if ($tipo === 'Mes') {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw('
                DATE_FORMAT(estacion_dato.created_at, "%Y-%m") AS fecha,
                MAX(temperatura_suelo) AS max_temperatura_suelo,
                MIN(temperatura_suelo) AS min_temperatura_suelo,
                AVG(temperatura_suelo) AS avg_temperatura_suelo
            ')
                ->groupByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->orderByRaw('DATE_FORMAT(estacion_dato.created_at, "%Y-%m")')
                ->get()
                ->toArray();
        } else {
            $rows = EstacionDato::whereIn('estacion_id', $ids)
                ->whereBetween('created_at', [$fechas[1], $fechas[0]])
                ->selectRaw($select . '
                MAX(temperatura_suelo) as max_temperatura_suelo,
                MIN(temperatura_suelo) as min_temperatura_suelo,
                AVG(temperatura_suelo) as avg_temperatura_suelo,
                DATE(created_at) as fecha_real
            ')
                ->groupBy('fecha', 'fecha_real')
                ->orderBy('fecha_real', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels               = array_column($rows, 'fecha');
        $maximosTemperaturaSuelo   = array_column($rows, 'max_temperatura_suelo');
        $minimosTemperaturaSuelo   = array_column($rows, 'min_temperatura_suelo');
        $promediosTemperaturaSuelo = array_map(fn($v) => round($v, 2), array_column($rows, 'avg_temperatura_suelo'));

        // Transformar las fechas al formato deseado
        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels'               => $labels,
            'maximosTemperaturaSuelo'   => $maximosTemperaturaSuelo,
            'minimosTemperaturaSuelo'   => $minimosTemperaturaSuelo,
            'promediosTemperaturaSuelo' => $promediosTemperaturaSuelo,
        ]);
    }

    public function calcularPeriodoFechas($periodo, $desdeR = null, $hastaR = null)
    {
        // Forzar zona horaria de México
        $now = Carbon::now('America/Mexico_City');

        // Por defecto: ayer completo
        $desde = $now->copy()->subDay()->startOfDay()->format('Y-m-d H:i:s');
        $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');

        switch ($periodo) {
            case 1: // Ayer completo
                $desde = $now->copy()->subDay()->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 2: // Antier + Ayer (últimos 2 días completos)
                $desde = $now->copy()->subDays(2)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 3: // Últimos 7 días completos
                $desde = $now->copy()->subDays(7)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 4: // Últimos 14 días completos
                $desde = $now->copy()->subDays(14)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 5: // Últimos 30 días completos
                $desde = $now->copy()->subDays(30)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 6: // Últimos 60 días completos
                $desde = $now->copy()->subDays(60)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 7: // Últimos 180 días completos
                $desde = $now->copy()->subDays(180)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 8: // Últimos 365 días completos
                $desde = $now->copy()->subDays(365)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 9: // Fechas personalizadas
                // Limpiar fechas si vienen con tiempo duplicado
                $desdeRLimpia = preg_replace('/\s+\d{2}:\d{2}:\d{2}$/', '', $desdeR);
                $hastaRLimpia = preg_replace('/\s+\d{2}:\d{2}:\d{2}$/', '', $hastaR);
                $desde = $desdeRLimpia . " 00:00:00";
                $hasta = $hastaRLimpia . " 23:59:59";
                break;
            case 10: // Próximas 24 horas
                $desde = $now->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(24)->format('Y-m-d H:i:s');
                break;
            case 11: // Próximas 48 horas
                $desde = $now->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(48)->format('Y-m-d H:i:s');
                break;
            case 12: // 24h antes + 48h después
                $desde = $now->copy()->subHours(24)->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(48)->format('Y-m-d H:i:s');
                break;
            case 13: // 48h antes + 48h después
                $desde = $now->copy()->subHours(48)->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(48)->format('Y-m-d H:i:s');
                break;
            case 14: // 7 días antes + 48h después
                $desde = $now->copy()->subDays(7)->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(48)->format('Y-m-d H:i:s');
                break;
            default:
                // Caso por defecto: ayer completo
                $desde = $now->copy()->subDay()->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
        }

        return array($hasta, $desde); // Retorna [hasta, desde] para mantener compatibilidad
    }

    public function calcularPeriodo($periodo, $desdeR = null, $hastaR = null)
    {
        // Forzar zona horaria de México
        $desde = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');
        $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s'); // Valor por defecto
        $grupo = '4_horas';

        switch ($periodo) {
            case 1:
                $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
            case 2:
                $hasta = Carbon::now('America/Mexico_City')->subHours(48)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
            case 3:
                $hasta = Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 4:
                $hasta = Carbon::now('America/Mexico_City')->subDays(14)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 5:
                $hasta = Carbon::now('America/Mexico_City')->subDays(30)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 6:
                $hasta = Carbon::now('America/Mexico_City')->subDays(60)->format('Y-m-d H:i:s');
                $grupo = 's';
                break;
            case 7:
                $hasta = Carbon::now('America/Mexico_City')->subDays(180)->format('Y-m-d H:i:s');
                $grupo = 's';
                break;
            case 8:
                $hasta = Carbon::now('America/Mexico_City')->subDays(365)->format('Y-m-d H:i:s');
                $grupo = 'm';
                break;
            case 9:
                if ($desdeR && $hastaR) {
                    $desde = $hastaR . " 23:59:59";
                    $hasta = $desdeR . " 00:00:00";
                } else {
                    $desde = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');
                    $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s');
                }
                $grupo = '4_horas';
                break;
            case 10:
                $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
            case 11:
                $hasta = Carbon::now('America/Mexico_City')->subHours(48)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
            case 12:
                $hasta = Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 13:
                $hasta = Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 14:
                $hasta = Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            default:
                // Caso por defecto: últimas 24 horas
                $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
        }

        return array($desde, $hasta, $grupo);
    }

    // -------------------------------------------------------------------------------------------- PLAGAS ----------------------------------------------------------------------------------------------------
    public function getPlagasGraficas(Request $request)
    {
        $zonaManejo = ZonaManejos::find($request->zona_manejo_id);
        $tipoCultivo = TipoCultivos::find($request->tipo_cultivo_id);

        return view('charts.plagas', [
            'zona_manejo' => $zonaManejo,
            'tipo_cultivo' => $tipoCultivo,
        ]);
    }

    // -------------------------------------------------------------------------------------------- GRAFICAS DE NUTRICION ----------------------------------------------------------------------------------------------------

    public function grafica_estres(Request $request)
    {
        // Usar la nueva función para periodos exactos
        list($fechaInicio, $fechaFin) = $this->calcularPeriodoExacto($request->periodo);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

        // Usar la variable solicitada (por defecto temperatura)
        $variable = $request->variable ?? 'temperatura';

        $rows = EstacionDato::whereIn('estacion_id', $ids)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->select('created_at', $variable)
            ->orderBy('created_at')
            ->get()
            ->toArray();

        // Agrupar registros por hora real y calcular el promedio por hora
        $agrupadosPorHora = [];
        foreach ($rows as $row) {
            if (!isset($row['created_at'])) continue;
            $fechaHora = date('Y-m-d H:00:00', strtotime($row['created_at']));
            if (!isset($agrupadosPorHora[$fechaHora])) {
                $agrupadosPorHora[$fechaHora] = [];
            }
            $agrupadosPorHora[$fechaHora][] = $row[$variable];
        }
        $valoresPorHora = [];
        foreach ($agrupadosPorHora as $hora => $valores) {
            $valoresPorHora[] = round(array_sum($valores) / count($valores), 1); // promedio por hora
        }

        // Calcular el total de horas reales (sin restar 1)
        $total_horas = count($valoresPorHora);

        // Log temporal para depuración
        Log::info('grafica_estres valoresPorHora', [
            'variable' => $variable,
            'valoresPorHora' => $valoresPorHora
        ]);

        // Calcular datos agrupados acumulados para todo el período
        $datosAgrupados = [];
        $datosAcumulados = [
            'muy_bajo' => 0,
            'bajo' => 0,
            'optimo' => 0,
            'alto' => 0,
            'muy_alto' => 0
        ];

        if (!empty($valoresPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
            $datosAcumulados = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                $request->tipo_cultivo_id,
                $request->etapa_fenologica_id,
                $variable,
                $valoresPorHora
            );
            // Log temporal para depuración
            Log::info('grafica_estres datosAcumulados', [
                'datosAcumulados' => $datosAcumulados
            ]);
            // Guardar el total real de horas por rango, sin dividir entre días
            $datosAgrupados[] = [
                'muy_bajo' => $datosAcumulados['muy_bajo'],
                'bajo' => $datosAcumulados['bajo'],
                'optimo' => $datosAcumulados['optimo'],
                'alto' => $datosAcumulados['alto'],
                'muy_alto' => $datosAcumulados['muy_alto']
            ];
        }

        // Elimina la normalización y el promedio diario, solo muestra los datos reales

        // Calcular porcentaje y horas reales por rango
        $cumulo_real = [];
        foreach ($datosAcumulados as $k => $v) {
            $cumulo_real[$k] = [
                'h' => $v, // número de horas reales en ese rango
                'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0 // porcentaje
            ];
        }

        $total_horas_periodo = $total_horas;

        // Normalización y redondeo a horas completas
        if (!empty($datosAgrupados)) {
            $valores = $datosAgrupados[0];
            $sumaOriginal = $valores['muy_bajo'] + $valores['bajo'] + $valores['optimo'] + $valores['alto'] + $valores['muy_alto'];
            $normalizados = [];
            if ($sumaOriginal > 0) {
                // Normalizar a 24
                foreach ($valores as $k => $v) {
                    $normalizados[$k] = round($v * 24 / $sumaOriginal);
                }
                // Ajustar para que la suma sea exactamente 24
                $sumaNormalizada = array_sum($normalizados);
                if ($sumaNormalizada !== 24) {
                    // Diferencia a ajustar
                    $diff = 24 - $sumaNormalizada;
                    // Encuentra el índice del valor mayor
                    $mayorKey = array_keys($normalizados, max($normalizados))[0];
                    $normalizados[$mayorKey] += $diff;
                }
                $datosAgrupados[0] = $normalizados;
            }
        }

        // Calcular porcentaje entero de cada color respecto al total original
        $porcentajes = [];
        if (!empty($valores) && $sumaOriginal > 0) {
            foreach ($valores as $k => $v) {
                $porcentajes[$k] = round(($v / $sumaOriginal) * 100);
            }
        }

        // Crear etiqueta para el período
        $fechaInicioObj = Carbon::parse($fechaInicio, 'America/Mexico_City');
        $fechaFinObj = Carbon::parse($fechaFin, 'America/Mexico_City');
        $diasEnPeriodo = $fechaInicioObj->diffInDays($fechaFinObj) + 1;
        $labels = [
            $fechaInicioObj->format('d-m-Y') . ' a ' . $fechaFinObj->format('d-m-Y')
        ];

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => [$cumulo_real],
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $labels,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }

    /**
     * Endpoint de depuración: recorre estacion_dato con el mismo parsing de fechas que grafica_estres
     * GET /api/debug/estacion-dato
     * Params: estacion_id (zona manejo id), variable (opcional), startDate, endDate, limit (opcional)
     */
    public function debugEstacionDato(Request $request)
    {
        $fechas = $this->calcularPeriodoExacto($request->periodo);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Obtener datos históricos
        $datosHistoricos = DB::table('precipitacion_pluvial')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechas[0], $fechas[1]])
            ->selectRaw('
                DATE_FORMAT(fecha_hora_dato, "%d/%m/%Y") as fecha,
                SUM(precipitacion_mm) as precipitacion,
                DATE(fecha_hora_dato) as fecha_real
            ')
            ->groupBy('fecha', 'fecha_real')
            ->orderBy('fecha_real', 'DESC')
            ->get();

        // Calcular horas restantes del día actual + completar hasta 48 horas totales
        $now = Carbon::now('America/Mexico_City');
        $horaActual = $now->hour;
        $horasRestantesHoy = 24 - $horaActual;
        $totalHorasPronostico = 48; // Siempre 48 horas totales

        // Calcular fechas para el pronóstico de 48 horas exactas
        $fechaInicioPronostico = $now->copy()->startOfDay()->addHours($horaActual); // Desde la hora actual
        $fechaFinPronostico = $fechaInicioPronostico->copy()->addHours($totalHorasPronostico); // 48 horas después

        $datosPronostico = DB::table('precipitacion_pluvial')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'pronostico')
            ->whereBetween('fecha_hora_dato', [$fechaInicioPronostico, $fechaFinPronostico])
            ->selectRaw('
                DATE_FORMAT(fecha_hora_dato, "%d/%m/%Y") as fecha,
                DATE(fecha_hora_dato) as fecha_real,
                SUM(precipitacion_mm) as precipitacion
            ')
            ->groupBy('fecha', 'fecha_real')
            ->orderBy('fecha_real', 'DESC')
            ->get();

        // Combinar datos históricos y de pronóstico
        $datosCompletos = [];
        $acumulado = 0.0; // Usar float para mayor precisión

        // Procesar datos de pronóstico en orden DESC (del más reciente al más antiguo)
        // Primero calculamos las horas para cada día sin procesar
        $horasPorDia = [];
        $horasAcumuladas = 0;

        // Procesar en orden inverso para calcular horas correctamente
        $datosPronosticoReversed = $datosPronostico->reverse();
        foreach ($datosPronosticoReversed as $dato) {
            $fechaDato = Carbon::parse($dato->fecha_real);
            $esHoy = $fechaDato->isSameDay($now);
            $fechaFinPronostico = $now->copy()->addHours(48);
            $esUltimoDia = $fechaDato->isSameDay($fechaFinPronostico);

            if ($esHoy) {
                $horasDia = $horasRestantesHoy;
            } else {
                if ($esUltimoDia) {
                    $horasDia = 48 - $horasAcumuladas;
                } else {
                    $horasDia = 24;
                }
            }

            $horasPorDia[$dato->fecha_real] = $horasDia;
            $horasAcumuladas += $horasDia;
        }

        // Ahora procesamos en orden DESC con las horas ya calculadas
        foreach ($datosPronostico as $index => $dato) {
            $fechaDato = Carbon::parse($dato->fecha_real);
            $esHoy = $fechaDato->isSameDay($now);
            $horasDia = $horasPorDia[$dato->fecha_real];

            if ($esHoy) {
                $labelHoras = "({$horasDia} hrs restantes)";
            } else {
                $labelHoras = "({$horasDia} hrs)";
            }

            $acumulado += (float)$dato->precipitacion;
            $datosCompletos[] = [
                'fecha' => 'P ' . $dato->fecha . ' ' . $labelHoras,
                'precipitacion' => round((float)$dato->precipitacion, 2),
                'acumulado' => round($acumulado, 2),
                'es_pronostico' => true,
                'horas_dia' => $horasDia,
                'fecha_real' => $fechaDato
            ];
        }

        // Luego agregar datos históricos (más antiguos) en orden inverso
        // Calcular las horas reales del período para cada día histórico

        foreach ($datosHistoricos as $dato) {
            $valorPrecipitacion = (float)$dato->precipitacion;
            $acumulado += $valorPrecipitacion;

            // Calcular horas reales del período para cada día histórico
            $fechaDato = Carbon::parse($dato->fecha_real);
            $fechaInicioPeriodo = Carbon::parse($fechas[0]);
            $fechaFinPeriodo = Carbon::parse($fechas[1]);

            // Determinar las horas reales del día según el período
            if ($fechaDato->isSameDay($fechaInicioPeriodo)) {
                // Primer día del período - desde la hora especificada hacia atrás
                $horaInicio = $fechaInicioPeriodo->hour;
                $horasReales = 24 - $horaInicio; // Horas restantes del día
            } elseif ($fechaDato->isSameDay($fechaFinPeriodo)) {
                // Último día del período - hasta la hora especificada
                $horaFin = $fechaFinPeriodo->hour;
                $horasReales = $horaFin; // Horas del día hasta la hora especificada
            } else {
                // Días intermedios - día completo
                $horasReales = 24;
            }

            $datosCompletos[] = [
                'fecha' => $dato->fecha . ' (' . $horasReales . ' hrs)',
                'precipitacion' => round($valorPrecipitacion, 2),
                'acumulado' => round($acumulado, 2),
                'es_pronostico' => false,
                'fecha_real' => $fechaDato
            ];
        }

        // Recalcular acumulado de inicio a fin (orden cronológico) y aplicar al orden visual actual
        $sorted = $datosCompletos;
        usort($sorted, function ($a, $b) {
            return ($a['fecha_real'] <=> $b['fecha_real']);
        });
        $acc = 0.0;
        $accMap = [];
        foreach ($sorted as $row) {
            $acc += (float) ($row['precipitacion'] ?? 0);
            $accMap[$row['fecha']] = round($acc, 2);
        }
        foreach ($datosCompletos as &$row) {
            if (isset($accMap[$row['fecha']])) {
                $row['acumulado'] = $accMap[$row['fecha']];
            }
            // No exponer fecha_real en respuesta
            unset($row['fecha_real']);
        }
        unset($row);

        return response()->json([
            'datos' => $datosCompletos
        ]);
    }

    public function grafica_estres_pronostico(Request $request)
    {
        $now = Carbon::now('America/Mexico_City');
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        // Calcular horas restantes del día actual + completar hasta 48 horas totales
        $horaActual = $now->hour;
        $horasRestantesHoy = 24 - $horaActual;
        $totalHorasPronostico = 48; // Siempre 48 horas totales

        // Llamada a la API de OpenWeatherMap
        $response = Http::get('https://api.openweathermap.org/data/3.0/onecall', [
            'lat' => $zona_manejo->parcela->lat,
            'lon' => $zona_manejo->parcela->lon,
            'appid' => config('services.openweathermap.key'),
            'units' => 'metric',
            'tz' => '+06:00',
            'exclude' => 'current,minutely,alerts'
        ]);

        $labels = [];
        $datosAgrupados = [];
        $cumulo_real = [];
        $porcentajes = [];
        $total_horas_periodo = $totalHorasPronostico;
        $diasEnPeriodo = 1; // Un solo período de 48 horas
        $valores = [];
        $sumaOriginal = 0;
        $todosLosValores = []; // Para acumular todos los valores del pronóstico

        // Procesar exactamente 48 horas: horas restantes del día actual + completar con días siguientes
        $horasProcesadas = 0;
        $diaActual = 0;

        while ($horasProcesadas < 48) {
            // Determinar qué día estamos procesando
            if ($diaActual == 0) {
                // Día actual - solo horas restantes
                $fecha = $now->format('Y-m-d');
                $horasADescontar = min($horasRestantesHoy, 48 - $horasProcesadas);
                $labels[] = $fecha . ' (restantes)';
            } else {
                // Días siguientes - hasta completar 48 horas
                $fecha = $now->copy()->addDays($diaActual)->format('Y-m-d');
                $horasADescontar = min(24, 48 - $horasProcesadas);
                $labels[] = $fecha;
            }

            $promediosPorHora = [];

            if ($response->successful()) {
                $openWeatherData = $response->json();

                if ($diaActual == 0) {
                    // Procesar solo desde la hora actual hasta completar las horas restantes
                    for ($h = $horaActual; $h < $horaActual + $horasADescontar; $h++) {
                        $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                            return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                        });
                        if ($datoHora && isset($datoHora['temp'])) {
                            $promediosPorHora[] = round($datoHora['temp'], 2);
                        } else {
                            // Fallback: promedio diario min/max
                            $daily = collect($openWeatherData['daily'])->first(function ($d) use ($fecha) {
                                return date('Y-m-d', $d['dt']) === $fecha;
                            });
                            if ($daily && isset($daily['temp']['min']) && isset($daily['temp']['max'])) {
                                $promediosPorHora[] = round((($daily['temp']['min'] + $daily['temp']['max']) / 2), 2);
                            } else {
                                $promediosPorHora[] = null;
                            }
                        }
                    }
                } else {
                    // Procesar horas del día siguiente
                    for ($h = 0; $h < $horasADescontar; $h++) {
                        $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                            return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                        });
                        if ($datoHora && isset($datoHora['temp'])) {
                            $promediosPorHora[] = round($datoHora['temp'], 2);
                        } else {
                            // Fallback: promedio diario min/max
                            $daily = collect($openWeatherData['daily'])->first(function ($d) use ($fecha) {
                                return date('Y-m-d', $d['dt']) === $fecha;
                            });
                            if ($daily && isset($daily['temp']['min']) && isset($daily['temp']['max'])) {
                                $promediosPorHora[] = round((($daily['temp']['min'] + $daily['temp']['max']) / 2), 2);
                            } else {
                                $promediosPorHora[] = null;
                            }
                        }
                    }
                }
            } else {
                $promediosPorHora = array_fill(0, $horasADescontar, null);
            }

            // Acumular todos los valores para el cálculo total
            $todosLosValores = array_merge($todosLosValores, $promediosPorHora);
            $horasProcesadas += $horasADescontar;
            $diaActual++;

            // Calcular datos agrupados para cada día de pronóstico
            $agrupado = [
                'muy_bajo' => 0,
                'bajo' => 0,
                'optimo' => 0,
                'alto' => 0,
                'muy_alto' => 0
            ];
            if (!empty($promediosPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
                $agrupado = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                    $request->tipo_cultivo_id,
                    $request->etapa_fenologica_id,
                    $request->variable,
                    $promediosPorHora
                );
            } else {
                $agrupado['optimo'] = count($promediosPorHora);
            }

            // Calcular porcentaje y horas reales por rango
            $cumulo = [];
            $total_horas = array_sum($agrupado);
            foreach ($agrupado as $k => $v) {
                $cumulo[$k] = [
                    'h' => $v,
                    'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0
                ];
            }
            // Agregar suma total de horas al cumulo
            $cumulo['total_horas'] = $total_horas;
            $cumulo_real[] = $cumulo;

            // Para porcentajes y normalización - escalar a 24 horas
            $valores = $agrupado;
            $sumaOriginal = array_sum($valores);
            $normalizados = [];
            if ($sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $normalizados[$k] = round($v * 24 / $sumaOriginal);
                }
                $sumaNormalizada = array_sum($normalizados);
                if ($sumaNormalizada !== 24) {
                    $diff = 24 - $sumaNormalizada;
                    $mayorKey = array_keys($normalizados, max($normalizados))[0];
                    $normalizados[$mayorKey] += $diff;
                }
                // Guardar los datos escalados a 24 horas
                $datosAgrupados[] = $normalizados;
            } else {
                // Si no hay datos, usar valores por defecto escalados
                $datosAgrupados[] = [
                    'muy_bajo' => 0,
                    'bajo' => 0,
                    'optimo' => 24,
                    'alto' => 0,
                    'muy_alto' => 0
                ];
            }

            // Porcentajes por rango respecto al total
            $porcentajes_dia = [];
            if (!empty($valores) && $sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $porcentajes_dia[$k] = round(($v / $sumaOriginal) * 100);
                }
            }
            $porcentajes[] = $porcentajes_dia;
        }

        $fechas = $labels;

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => $cumulo_real,
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $fechas,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }

    public function grafica_estres_pronostico_humedad_relativa(Request $request)
    {
        $now = Carbon::now('America/Mexico_City');
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        // Calcular horas restantes del día actual + completar hasta 48 horas totales
        $horaActual = $now->hour;
        $horasRestantesHoy = 24 - $horaActual;
        $totalHorasPronostico = 48; // Siempre 48 horas totales

        // Llamada a la API de OpenWeatherMap
        $response = Http::get('https://api.openweathermap.org/data/3.0/onecall', [
            'lat' => $zona_manejo->parcela->lat,
            'lon' => $zona_manejo->parcela->lon,
            'appid' => config('services.openweathermap.key'),
            'units' => 'metric',
            'tz' => '+06:00',
            'exclude' => 'current,minutely,alerts'
        ]);

        $labels = [];
        $datosAgrupados = [];
        $cumulo_real = [];
        $porcentajes = [];
        $total_horas_periodo = $totalHorasPronostico;
        $diasEnPeriodo = 1; // Un solo período de 48 horas
        $valores = [];
        $sumaOriginal = 0;
        $todosLosValores = []; // Para acumular todos los valores del pronóstico

        // Procesar exactamente 48 horas: horas restantes del día actual + completar con días siguientes
        $horasProcesadas = 0;
        $diaActual = 0;

        while ($horasProcesadas < 48) {
            // Determinar qué día estamos procesando
            if ($diaActual == 0) {
                // Día actual - solo horas restantes
                $fecha = $now->format('Y-m-d');
                $horasADescontar = min($horasRestantesHoy, 48 - $horasProcesadas);
                $labels[] = $fecha . ' (restantes)';
            } else {
                // Días siguientes - hasta completar 48 horas
                $fecha = $now->copy()->addDays($diaActual)->format('Y-m-d');
                $horasADescontar = min(24, 48 - $horasProcesadas);
                $labels[] = $fecha;
            }

            $promediosPorHora = [];

            if ($response->successful()) {
                $openWeatherData = $response->json();

                if ($diaActual == 0) {
                    // Procesar solo desde la hora actual hasta completar las horas restantes
                    for ($h = $horaActual; $h < $horaActual + $horasADescontar; $h++) {
                        $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                            return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                        });
                        if ($datoHora && isset($datoHora['humidity'])) {
                            // OpenWeather devuelve humedad en porcentaje (0-100)
                            $promediosPorHora[] = round($datoHora['humidity'], 2);
                        } else {
                            // Fallback: promedio diario min/max de humedad
                            $daily = collect($openWeatherData['daily'])->first(function ($d) use ($fecha) {
                                return date('Y-m-d', $d['dt']) === $fecha;
                            });
                            if ($daily && isset($daily['humidity'])) {
                                $promediosPorHora[] = round($daily['humidity'], 2);
                            } else {
                                $promediosPorHora[] = null;
                            }
                        }
                    }
                } else {
                    // Procesar horas del día siguiente
                    for ($h = 0; $h < $horasADescontar; $h++) {
                        $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                            return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                        });
                        if ($datoHora && isset($datoHora['humidity'])) {
                            // OpenWeather devuelve humedad en porcentaje (0-100)
                            $promediosPorHora[] = round($datoHora['humidity'], 2);
                        } else {
                            // Fallback: promedio diario min/max de humedad
                            $daily = collect($openWeatherData['daily'])->first(function ($d) use ($fecha) {
                                return date('Y-m-d', $d['dt']) === $fecha;
                            });
                            if ($daily && isset($daily['humidity'])) {
                                $promediosPorHora[] = round($daily['humidity'], 2);
                            } else {
                                $promediosPorHora[] = null;
                            }
                        }
                    }
                }
            } else {
                $promediosPorHora = array_fill(0, $horasADescontar, null);
            }

            // Acumular todos los valores para el cálculo total
            $todosLosValores = array_merge($todosLosValores, $promediosPorHora);
            $horasProcesadas += $horasADescontar;
            $diaActual++;

            // Calcular datos agrupados para cada día de pronóstico
            $agrupado = [
                'muy_bajo' => 0,
                'bajo' => 0,
                'optimo' => 0,
                'alto' => 0,
                'muy_alto' => 0
            ];
            if (!empty($promediosPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
                $agrupado = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                    $request->tipo_cultivo_id,
                    $request->etapa_fenologica_id,
                    'humedad_relativa', // Variable fija para humedad relativa
                    $promediosPorHora
                );
            } else {
                $agrupado['optimo'] = count($promediosPorHora);
            }

            // Calcular porcentaje y horas reales por rango
            $cumulo = [];
            $total_horas = array_sum($agrupado);
            foreach ($agrupado as $k => $v) {
                $cumulo[$k] = [
                    'h' => $v,
                    'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0
                ];
            }
            // Agregar suma total de horas al cumulo
            $cumulo['total_horas'] = $total_horas;
            $cumulo_real[] = $cumulo;

            // Para porcentajes y normalización - escalar a 24 horas
            $valores = $agrupado;
            $sumaOriginal = array_sum($valores);
            $normalizados = [];
            if ($sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $normalizados[$k] = round($v * 24 / $sumaOriginal);
                }
                $sumaNormalizada = array_sum($normalizados);
                if ($sumaNormalizada !== 24) {
                    $diff = 24 - $sumaNormalizada;
                    $mayorKey = array_keys($normalizados, max($normalizados))[0];
                    $normalizados[$mayorKey] += $diff;
                }
                // Guardar los datos escalados a 24 horas
                $datosAgrupados[] = $normalizados;
            } else {
                // Si no hay datos, usar valores por defecto escalados
                $datosAgrupados[] = [
                    'muy_bajo' => 0,
                    'bajo' => 0,
                    'optimo' => 24,
                    'alto' => 0,
                    'muy_alto' => 0
                ];
            }

            // Porcentajes por rango respecto al total
            $porcentajes_dia = [];
            if (!empty($valores) && $sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $porcentajes_dia[$k] = round(($v / $sumaOriginal) * 100);
                }
            }
            $porcentajes[] = $porcentajes_dia;
        }

        $fechas = $labels;

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => $cumulo_real,
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $fechas,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }

    public function grafica_estres_pronostico_velocidad_viento(Request $request)
    {
        $now = Carbon::now('America/Mexico_City');
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Calcular horas restantes del día actual + completar hasta 48 horas totales
        $horaActual = $now->hour;
        $horasRestantesHoy = 24 - $horaActual;
        $totalHorasPronostico = 48; // Siempre 48 horas totales

        // Llamada a la API de OpenWeatherMap
        $response = Http::get('https://api.openweathermap.org/data/3.0/onecall', [
            'lat' => $zona_manejo->parcela->lat,
            'lon' => $zona_manejo->parcela->lon,
            'appid' => config('services.openweathermap.key'),
            'units' => 'metric',
            'tz' => '+06:00',
            'exclude' => 'current,minutely,alerts'
        ]);

        $labels = [];
        $datosAgrupados = [];
        $cumulo_real = [];
        $porcentajes = [];
        $total_horas_periodo = $totalHorasPronostico;
        $diasEnPeriodo = 1; // Un solo período de 48 horas
        $valores = [];
        $sumaOriginal = 0;
        $todosLosValores = []; // Para acumular todos los valores del pronóstico

        // Procesar exactamente 48 horas: horas restantes del día actual + completar con días siguientes
        $horasProcesadas = 0;
        $diaActual = 0;

        while ($horasProcesadas < 48) {
            // Determinar qué día estamos procesando
            if ($diaActual == 0) {
                // Día actual - solo horas restantes
                $fecha = $now->format('Y-m-d');
                $horasADescontar = min($horasRestantesHoy, 48 - $horasProcesadas);
                $labels[] = $fecha . ' (restantes)';
            } else {
                // Días siguientes - hasta completar 48 horas
                $fecha = $now->copy()->addDays($diaActual)->format('Y-m-d');
                $horasADescontar = min(24, 48 - $horasProcesadas);
                $labels[] = $fecha;
            }

            $promediosPorHora = [];

            if ($response->successful()) {
                $openWeatherData = $response->json();

                if ($diaActual == 0) {
                    // Procesar solo desde la hora actual hasta completar las horas restantes
                    for ($h = $horaActual; $h < $horaActual + $horasADescontar; $h++) {
                        $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                            return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                        });
                        if ($datoHora && isset($datoHora['wind_speed'])) {
                            $promediosPorHora[] = round($datoHora['wind_speed'], 2);
                        } else {
                            $promediosPorHora[] = null;
                        }
                    }
                } else {
                    // Procesar horas del día siguiente
                    for ($h = 0; $h < $horasADescontar; $h++) {
                        $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                            return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                        });
                        if ($datoHora && isset($datoHora['wind_speed'])) {
                            $promediosPorHora[] = round($datoHora['wind_speed'], 2);
                        } else {
                            $promediosPorHora[] = null;
                        }
                    }
                }
            } else {
                $promediosPorHora = array_fill(0, $horasADescontar, null);
            }

            // Acumular todos los valores para el cálculo total
            $todosLosValores = array_merge($todosLosValores, $promediosPorHora);
            $horasProcesadas += $horasADescontar;
            $diaActual++;

            // Calcular datos agrupados para cada día de pronóstico
            $agrupado = [
                'muy_bajo' => 0,
                'bajo' => 0,
                'optimo' => 0,
                'alto' => 0,
                'muy_alto' => 0
            ];
            if (!empty($promediosPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
                $agrupado = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                    $request->tipo_cultivo_id,
                    $request->etapa_fenologica_id,
                    'velocidad_viento',
                    $promediosPorHora
                );
            } else {
                $agrupado['optimo'] = count($promediosPorHora);
            }

            // Calcular porcentaje y horas reales por rango
            $cumulo = [];
            $total_horas = array_sum($agrupado);
            foreach ($agrupado as $k => $v) {
                $cumulo[$k] = [
                    'h' => $v,
                    'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0
                ];
            }
            // Agregar suma total de horas al cumulo
            $cumulo['total_horas'] = $total_horas;
            $cumulo_real[] = $cumulo;

            // Para porcentajes y normalización - escalar a 24 horas
            $valores = $agrupado;
            $sumaOriginal = array_sum($valores);
            $normalizados = [];
            if ($sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $normalizados[$k] = round($v * 24 / $sumaOriginal);
                }
                $sumaNormalizada = array_sum($normalizados);
                if ($sumaNormalizada !== 24) {
                    $diff = 24 - $sumaNormalizada;
                    $mayorKey = array_keys($normalizados, max($normalizados))[0];
                    $normalizados[$mayorKey] += $diff;
                }
                // Guardar los datos escalados a 24 horas
                $datosAgrupados[] = $normalizados;
            } else {
                // Si no hay datos, usar valores por defecto escalados
                $datosAgrupados[] = [
                    'muy_bajo' => 0,
                    'bajo' => 0,
                    'optimo' => 24,
                    'alto' => 0,
                    'muy_alto' => 0
                ];
            }

            // Porcentajes por rango respecto al total
            $porcentajes_dia = [];
            if (!empty($valores) && $sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $porcentajes_dia[$k] = round(($v / $sumaOriginal) * 100);
                }
            }
            $porcentajes[] = $porcentajes_dia;
        }

        $fechas = $labels;

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => $cumulo_real,
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $fechas,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }

    public function grafica_estres_pronostico_presion_atmosferica(Request $request)
    {
        $now = Carbon::now('America/Mexico_City');
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Calcular horas restantes del día actual + completar hasta 48 horas totales
        $horaActual = $now->hour;
        $horasRestantesHoy = 24 - $horaActual;
        $totalHorasPronostico = 48; // Siempre 48 horas totales

        // Llamada a la API de OpenWeatherMap
        $response = Http::get('https://api.openweathermap.org/data/3.0/onecall', [
            'lat' => $zona_manejo->parcela->lat,
            'lon' => $zona_manejo->parcela->lon,
            'appid' => config('services.openweathermap.key'),
            'units' => 'metric',
            'tz' => '+06:00',
            'exclude' => 'current,minutely,alerts'
        ]);

        $labels = [];
        $datosAgrupados = [];
        $cumulo_real = [];
        $porcentajes = [];
        $total_horas_periodo = $totalHorasPronostico;
        $diasEnPeriodo = 1; // Un solo período de 48 horas
        $valores = [];
        $sumaOriginal = 0;
        $todosLosValores = []; // Para acumular todos los valores del pronóstico

        // Procesar exactamente 48 horas: horas restantes del día actual + completar con días siguientes
        $horasProcesadas = 0;
        $diaActual = 0;

        while ($horasProcesadas < 48) {
            // Determinar qué día estamos procesando
            if ($diaActual == 0) {
                // Día actual - solo horas restantes
                $fecha = $now->format('Y-m-d');
                $horasADescontar = min($horasRestantesHoy, 48 - $horasProcesadas);
                $labels[] = $fecha . ' (restantes)';
            } else {
                // Días siguientes - hasta completar 48 horas
                $fecha = $now->copy()->addDays($diaActual)->format('Y-m-d');
                $horasADescontar = min(24, 48 - $horasProcesadas);
                $labels[] = $fecha;
            }

            $promediosPorHora = [];

            if ($response->successful()) {
                $openWeatherData = $response->json();

                if ($diaActual == 0) {
                    // Procesar solo desde la hora actual hasta completar las horas restantes
                    for ($h = $horaActual; $h < $horaActual + $horasADescontar; $h++) {
                        $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                            return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                        });
                        if ($datoHora && isset($datoHora['pressure'])) {
                            $promediosPorHora[] = round($datoHora['pressure'], 2);
                        } else {
                            $promediosPorHora[] = null;
                        }
                    }
                } else {
                    // Procesar horas del día siguiente
                    for ($h = 0; $h < $horasADescontar; $h++) {
                        $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                        $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                            return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                        });
                        if ($datoHora && isset($datoHora['pressure'])) {
                            $promediosPorHora[] = round($datoHora['pressure'], 2);
                        } else {
                            $promediosPorHora[] = null;
                        }
                    }
                }
            } else {
                $promediosPorHora = array_fill(0, $horasADescontar, null);
            }

            // Acumular todos los valores para el cálculo total
            $todosLosValores = array_merge($todosLosValores, $promediosPorHora);
            $horasProcesadas += $horasADescontar;
            $diaActual++;

            // Calcular datos agrupados para cada día de pronóstico
            $agrupado = [
                'muy_bajo' => 0,
                'bajo' => 0,
                'optimo' => 0,
                'alto' => 0,
                'muy_alto' => 0
            ];
            if (!empty($promediosPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
                $agrupado = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                    $request->tipo_cultivo_id,
                    $request->etapa_fenologica_id,
                    'presion_atmosferica',
                    $promediosPorHora
                );
            } else {
                $agrupado['optimo'] = count($promediosPorHora);
            }

            // Calcular porcentaje y horas reales por rango
            $cumulo = [];
            $total_horas = array_sum($agrupado);
            foreach ($agrupado as $k => $v) {
                $cumulo[$k] = [
                    'h' => $v,
                    'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0
                ];
            }
            // Agregar suma total de horas al cumulo
            $cumulo['total_horas'] = $total_horas;
            $cumulo_real[] = $cumulo;

            // Para porcentajes y normalización - escalar a 24 horas
            $valores = $agrupado;
            $sumaOriginal = array_sum($valores);
            $normalizados = [];
            if ($sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $normalizados[$k] = round($v * 24 / $sumaOriginal);
                }
                $sumaNormalizada = array_sum($normalizados);
                if ($sumaNormalizada !== 24) {
                    $diff = 24 - $sumaNormalizada;
                    $mayorKey = array_keys($normalizados, max($normalizados))[0];
                    $normalizados[$mayorKey] += $diff;
                }
                // Guardar los datos escalados a 24 horas
                $datosAgrupados[] = $normalizados;
            } else {
                // Si no hay datos, usar valores por defecto escalados
                $datosAgrupados[] = [
                    'muy_bajo' => 0,
                    'bajo' => 0,
                    'optimo' => 24,
                    'alto' => 0,
                    'muy_alto' => 0
                ];
            }

            // Porcentajes por rango respecto al total
            $porcentajes_dia = [];
            if (!empty($valores) && $sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $porcentajes_dia[$k] = round(($v / $sumaOriginal) * 100);
                }
            }
            $porcentajes[] = $porcentajes_dia;
        }

        $fechas = $labels;

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => $cumulo_real,
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $fechas,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }


    public function graficaTemperaturaSuelo($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('endDate', Carbon::now('America/Mexico_City')->format('Y-m-d'));
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        return view('components.grafica_temperatura_suelo', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    // ============================================================================================
    // GRÁFICAS DE PRECIPITACIÓN PLUVIAL
    // ============================================================================================

    public function graficaPrecipitacionPluvialComponente($zonaManejoId, Request $request)
    {
        $zonaManejo = ZonaManejos::find($zonaManejoId);

        if (!$zonaManejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        $periodo = $request->get('periodo', 1);
        $startDate = $request->get('startDate', null);
        $endDate = $request->get('endDate', null);
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $etapa_fenologica_id = $request->get('etapa_fenologica_id');

        return view('components.grafica_precipitacion_pluvial', [
            'zonaManejo' => $zonaManejo,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tipoCultivoId' => $tipo_cultivo_id,
            'etapaFenologicaId' => $etapa_fenologica_id,
        ]);
    }

    public function grafica_precipitacion_pluvial(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        $select = '';
        switch ($fechas[2]) {
            case 'd':
                $tipo = 'Día';
                $select = 'DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%d-%m-%Y") as fecha, ';
                break;
            case 's':
                $tipo = 'Semana';
                $select = 'DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%d-%m-%Y") as fecha, ';
                break;
            case 'm':
                $tipo = 'Mes';
                $select = 'DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%m-%Y") as fecha, ';
                break;
            case '4_horas':
                $tipo = 'Cada 4 horas';
                $select = "CASE
                    WHEN HOUR(precipitacion_pluvial.fecha_hora_dato) BETWEEN 0 AND 3  THEN CONCAT(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, '%d-%m-%Y'), ' 04:00')
                    WHEN HOUR(precipitacion_pluvial.fecha_hora_dato) BETWEEN 4 AND 7  THEN CONCAT(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, '%d-%m-%Y'), ' 08:00')
                    WHEN HOUR(precipitacion_pluvial.fecha_hora_dato) BETWEEN 8 AND 11 THEN CONCAT(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, '%d-%m-%Y'), ' 12:00')
                    WHEN HOUR(precipitacion_pluvial.fecha_hora_dato) BETWEEN 12 AND 15 THEN CONCAT(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, '%d-%m-%Y'), ' 16:00')
                    WHEN HOUR(precipitacion_pluvial.fecha_hora_dato) BETWEEN 16 AND 19 THEN CONCAT(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, '%d-%m-%Y'), ' 20:00')
                    ELSE CONCAT(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, '%d-%m-%Y'), ' 00:00') END as fecha,";
                break;
            case '8_horas':
                $tipo = 'Cada 8 horas';
                $select = '
                case
                when DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%H") between 0 and 7 then concat(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%d-%m-%Y")," 08:00")
                when DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%H") between 8 and 15 then concat(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%d-%m-%Y")," 16:00")
                when DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%H") between 16 and 23 then concat(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%d-%m-%Y")," 00:00")
                end as fecha,';
                break;
            case '12_horas':
                $tipo = 'Cada 12 horas';
                $select = '
                case
                when DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%H") between 0 and 11 then concat(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%d-%m-%Y")," 12:00")
                when DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%H") between 12 and 23 then concat(DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%d-%m-%Y")," 00:00")
                end as fecha,';
                break;
            case 'crudos':
                $tipo = 'Crudos';
                $select = 'precipitacion_pluvial.fecha_hora_dato as fecha, ';
                break;
            default:
                break;
        }

        // Primero intentar con datos históricos
        $rows = DB::table('precipitacion_pluvial')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
            ->selectRaw($select . '
                MAX(precipitacion_mm) as max_precipitacion,
                DATE(fecha_hora_dato) as fecha_real
            ')
            ->groupBy('fecha', 'fecha_real')
            ->orderBy('fecha_real', 'ASC')
            ->get()
            ->toArray();

        // Si no hay datos históricos suficientes (menos de 1 registro), usar datos de pronóstico
        if (count($rows) < 1) {
            // Para datos de pronóstico, mostrar todos los datos sin agrupar tanto
            $rows = DB::table('precipitacion_pluvial')
                ->where('parcela_id', $parcelaId)
                ->where('tipo_dato', 'pronostico')
                ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
                ->selectRaw('
                    DATE_FORMAT(precipitacion_pluvial.fecha_hora_dato, "%d-%m-%Y %H:00") as fecha,
                    precipitacion_mm as max_precipitacion
                ')
                ->orderBy('fecha_hora_dato', 'ASC')
                ->get()
                ->toArray();
        }

        // Transform rows into separate arrays
        $labels = array_column($rows, 'fecha');
        $maximosPrecipitacion = array_column($rows, 'max_precipitacion');

        $labels = array_map(function ($fecha) {
            // Si ya es un string tipo ISO, conviértelo
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d H:i:s');
            }
            if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                return date('Y-m-d H:i:s', strtotime($fecha));
            }
            return $fecha;
        }, array_column($rows, 'fecha'));

        return response()->json([
            'labels' => $labels,
            'maximosPrecipitacion' => $maximosPrecipitacion,
        ]);
    }

    public function grafica_precipitacion_pluvial_acumulada(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Obtener datos históricos
        $datosHistoricos = DB::table('precipitacion_pluvial')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
            ->selectRaw('
                DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y") as fecha,
                SUM(precipitacion_mm) as precipitacion,
                DATE(fecha_hora_dato) as fecha_real
            ')
            ->groupBy('fecha', 'fecha_real')
            ->orderBy('fecha_real', 'ASC')
            ->get();

        // Obtener datos de pronóstico para los próximos 2 días
        $fechaFin = Carbon::parse($fechas[0]);
        $fechaInicioPronostico = $fechaFin->copy()->addDay()->startOfDay();
        $fechaFinPronostico = $fechaInicioPronostico->copy()->addDays(1)->endOfDay();

        $datosPronostico = DB::table('precipitacion_pluvial')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'pronostico')
            ->whereBetween('fecha_hora_dato', [$fechaInicioPronostico, $fechaFinPronostico])
            ->selectRaw('
                DATE_FORMAT(fecha_hora_dato, "%d-%m-%Y") as fecha,
                SUM(precipitacion_mm) as precipitacion,
                DATE(fecha_hora_dato) as fecha_real
            ')
            ->groupBy('fecha', 'fecha_real')
            ->orderBy('fecha_real', 'ASC')
            ->get();

        // Combinar datos históricos y de pronóstico
        $datosCompletos = [];
        $acumulado = 0.0;
        $indicesPronostico = []; // Para marcar qué índices son de pronóstico

        // Primero agregar datos históricos (más antiguos)
        foreach ($datosHistoricos as $dato) {
            $valorPrecipitacion = (float)$dato->precipitacion;
            $acumulado += $valorPrecipitacion;

            $datosCompletos[] = [
                'fecha' => $dato->fecha,
                'precipitacion' => round($valorPrecipitacion, 2),
                'acumulado' => round($acumulado, 2),
                'es_pronostico' => false
            ];
        }

        // Luego agregar datos de pronóstico (más recientes)
        foreach ($datosPronostico as $dato) {
            $acumulado += (float)$dato->precipitacion;
            $indicesPronostico[] = count($datosCompletos); // Marcar el índice como pronóstico

            $datosCompletos[] = [
                'fecha' => $dato->fecha,
                'precipitacion' => round((float)$dato->precipitacion, 2),
                'acumulado' => round($acumulado, 2),
                'es_pronostico' => true
            ];
        }

        // Extraer arrays para la gráfica
        $labels = array_column($datosCompletos, 'fecha');
        $acumuladoPrecipitacion = array_column($datosCompletos, 'acumulado');

        return response()->json([
            'labels' => $labels,
            'acumuladoPrecipitacion' => $acumuladoPrecipitacion,
            'indicesPronostico' => $indicesPronostico, // Para que el frontend sepa qué puntos colorear
        ]);
    }

    public function tabla_precipitacion_pluvial(Request $request)
    {
        $fechas = $this->calcularPeriodoExacto($request->periodo);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Obtener datos históricos
        $datosHistoricos = DB::table('precipitacion_pluvial')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechas[0], $fechas[1]])
            ->selectRaw('
                DATE_FORMAT(fecha_hora_dato, "%d/%m/%Y") as fecha,
                SUM(precipitacion_mm) as precipitacion,
                DATE(fecha_hora_dato) as fecha_real
            ')
            ->groupBy('fecha', 'fecha_real')
            ->orderBy('fecha_real', 'DESC')
            ->get();

        // Calcular horas restantes del día actual + completar hasta 48 horas totales
        $now = Carbon::now('America/Mexico_City');
        $horaActual = $now->hour;
        $horasRestantesHoy = 24 - $horaActual;
        $totalHorasPronostico = 48; // Siempre 48 horas totales

        // Calcular fechas para el pronóstico de 48 horas exactas
        $fechaInicioPronostico = $now->copy()->startOfDay()->addHours($horaActual); // Desde la hora actual
        $fechaFinPronostico = $fechaInicioPronostico->copy()->addHours($totalHorasPronostico); // 48 horas después

        $datosPronostico = DB::table('precipitacion_pluvial')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'pronostico')
            ->whereBetween('fecha_hora_dato', [$fechaInicioPronostico, $fechaFinPronostico])
            ->selectRaw('
                DATE_FORMAT(fecha_hora_dato, "%d/%m/%Y") as fecha,
                DATE(fecha_hora_dato) as fecha_real,
                SUM(precipitacion_mm) as precipitacion
            ')
            ->groupBy('fecha', 'fecha_real')
            ->orderBy('fecha_real', 'DESC')
            ->get();

        // Combinar datos históricos y de pronóstico
        $datosCompletos = [];
        $acumulado = 0.0; // Usar float para mayor precisión

        // Procesar datos de pronóstico en orden DESC (del más reciente al más antiguo)
        // Primero calculamos las horas para cada día sin procesar
        $horasPorDia = [];
        $horasAcumuladas = 0;

        // Procesar en orden inverso para calcular horas correctamente
        $datosPronosticoReversed = $datosPronostico->reverse();
        foreach ($datosPronosticoReversed as $dato) {
            $fechaDato = Carbon::parse($dato->fecha_real);
            $esHoy = $fechaDato->isSameDay($now);
            $fechaFinPronostico = $now->copy()->addHours(48);
            $esUltimoDia = $fechaDato->isSameDay($fechaFinPronostico);

            if ($esHoy) {
                $horasDia = $horasRestantesHoy;
            } else {
                if ($esUltimoDia) {
                    $horasDia = 48 - $horasAcumuladas;
                } else {
                    $horasDia = 24;
                }
            }

            $horasPorDia[$dato->fecha_real] = $horasDia;
            $horasAcumuladas += $horasDia;
        }

        // Ahora procesamos en orden DESC con las horas ya calculadas
        foreach ($datosPronostico as $index => $dato) {
            $fechaDato = Carbon::parse($dato->fecha_real);
            $esHoy = $fechaDato->isSameDay($now);
            $horasDia = $horasPorDia[$dato->fecha_real];

            if ($esHoy) {
                $labelHoras = "({$horasDia} hrs restantes)";
            } else {
                $labelHoras = "({$horasDia} hrs)";
            }

            $acumulado += (float)$dato->precipitacion;
            $datosCompletos[] = [
                'fecha' => 'P ' . $dato->fecha . ' ' . $labelHoras,
                'precipitacion' => round((float)$dato->precipitacion, 2),
                'acumulado' => round($acumulado, 2),
                'es_pronostico' => true,
                'horas_dia' => $horasDia,
                'fecha_real' => $fechaDato
            ];
        }

        // Luego agregar datos históricos (más antiguos) en orden inverso
        // Calcular las horas reales del período para cada día histórico

        foreach ($datosHistoricos as $dato) {
            $valorPrecipitacion = (float)$dato->precipitacion;
            $acumulado += $valorPrecipitacion;

            // Calcular horas reales del período para cada día histórico
            $fechaDato = Carbon::parse($dato->fecha_real);
            $fechaInicioPeriodo = Carbon::parse($fechas[0]);
            $fechaFinPeriodo = Carbon::parse($fechas[1]);

            // Determinar las horas reales del día según el período
            if ($fechaDato->isSameDay($fechaInicioPeriodo)) {
                // Primer día del período - desde la hora especificada hacia atrás
                $horaInicio = $fechaInicioPeriodo->hour;
                $horasReales = 24 - $horaInicio; // Horas restantes del día
            } elseif ($fechaDato->isSameDay($fechaFinPeriodo)) {
                // Último día del período - hasta la hora especificada
                $horaFin = $fechaFinPeriodo->hour;
                $horasReales = $horaFin; // Horas del día hasta la hora especificada
            } else {
                // Días intermedios - día completo
                $horasReales = 24;
            }

            $datosCompletos[] = [
                'fecha' => $dato->fecha . ' (' . $horasReales . ' hrs)',
                'precipitacion' => round($valorPrecipitacion, 2),
                'acumulado' => round($acumulado, 2),
                'es_pronostico' => false,
                'fecha_real' => $fechaDato
            ];
        }

        // Recalcular acumulado de inicio a fin (orden cronológico) y aplicar al orden visual actual
        $sorted = $datosCompletos;
        usort($sorted, function ($a, $b) {
            return ($a['fecha_real'] <=> $b['fecha_real']);
        });
        $acc = 0.0;
        $accMap = [];
        foreach ($sorted as $row) {
            $acc += (float) ($row['precipitacion'] ?? 0);
            $accMap[$row['fecha']] = round($acc, 2);
        }
        foreach ($datosCompletos as &$row) {
            if (isset($accMap[$row['fecha']])) {
                $row['acumulado'] = $accMap[$row['fecha']];
            }
            // No exponer fecha_real en respuesta
            unset($row['fecha_real']);
        }
        unset($row);

        return response()->json([
            'datos' => $datosCompletos
        ]);
    }

    public function grafica_estres_precipitacion_pluvial(Request $request)
    {
        // Usar la nueva función para periodos exactos
        list($fechaInicio, $fechaFin) = $this->calcularPeriodoExacto($request->periodo);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Obtener datos de precipitación por hora (solo históricos)
        $rows = DB::table('precipitacion_pluvial')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->select('fecha_hora_dato', 'precipitacion_mm')
            ->orderBy('fecha_hora_dato')
            ->get();

        // Si no hay suficientes datos históricos, usar datos de pronóstico
        if ($rows->count() < 5) {
            $rows = DB::table('precipitacion_pluvial')
                ->where('parcela_id', $parcelaId)
                ->where('tipo_dato', 'pronostico')
                ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
                ->select('fecha_hora_dato', 'precipitacion_mm')
                ->orderBy('fecha_hora_dato')
                ->get();
        }

        // Agrupar registros por hora y calcular el promedio por hora
        $agrupadosPorHora = [];
        foreach ($rows as $row) {
            if (!isset($row->fecha_hora_dato)) continue;
            $fechaHora = date('Y-m-d H:00:00', strtotime($row->fecha_hora_dato));
            if (!isset($agrupadosPorHora[$fechaHora])) {
                $agrupadosPorHora[$fechaHora] = [];
            }
            $agrupadosPorHora[$fechaHora][] = $row->precipitacion_mm;
        }
        $valoresPorHora = [];
        foreach ($agrupadosPorHora as $hora => $valores) {
            $valoresPorHora[] = round(array_sum($valores) / count($valores), 2); // promedio por hora
        }

        $total_horas = count($valoresPorHora);

        // Calcular datos agrupados acumulados para todo el período
        $datosAgrupados = [];
        $datosAcumulados = [
            'muy_bajo' => 0,
            'bajo' => 0,
            'optimo' => 0,
            'alto' => 0,
            'muy_alto' => 0
        ];
        if (!empty($valoresPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
            $datosAcumulados = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                $request->tipo_cultivo_id,
                $request->etapa_fenologica_id,
                'precipitacion_pluvial',
                $valoresPorHora
            );
            $datosAgrupados[] = [
                'muy_bajo' => $datosAcumulados['muy_bajo'],
                'bajo' => $datosAcumulados['bajo'],
                'optimo' => $datosAcumulados['optimo'],
                'alto' => $datosAcumulados['alto'],
                'muy_alto' => $datosAcumulados['muy_alto']
            ];
        }

        // Calcular porcentaje y horas reales por rango
        $cumulo_real = [];
        foreach ($datosAcumulados as $k => $v) {
            $cumulo_real[$k] = [
                'h' => $v,
                'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0
            ];
        }

        $total_horas_periodo = $total_horas;

        // Calcular porcentajes para cada color respecto al total original
        $porcentajes = [];
        $sumaOriginal = array_sum($datosAcumulados);
        if ($sumaOriginal > 0) {
            foreach ($datosAcumulados as $k => $v) {
                $porcentajes[$k] = round(($v / $sumaOriginal) * 100);
            }
        }

        // Crear etiqueta para el período
        $fechaInicioObj = Carbon::parse($fechaInicio, 'America/Mexico_City');
        $fechaFinObj = Carbon::parse($fechaFin, 'America/Mexico_City');
        $diasEnPeriodo = $fechaInicioObj->diffInDays($fechaFinObj) + 1;
        $labels = [
            $fechaInicioObj->format('d-m-Y') . ' a ' . $fechaFinObj->format('d-m-Y')
        ];

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => [$cumulo_real],
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $labels,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }

    public function grafica_estres_velocidad_viento(Request $request)
    {
        // Usar la nueva función para periodos exactos
        list($fechaInicio, $fechaFin) = $this->calcularPeriodoExacto($request->periodo);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Obtener datos de precipitación por hora (solo históricos)
        $rows = DB::table('datos_viento')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->select('fecha_hora_dato', 'wind_speed')
            ->orderBy('fecha_hora_dato')
            ->get();

        // Si no hay suficientes datos históricos, usar datos de pronóstico
        if ($rows->count() < 5) {
            $rows = DB::table('datos_viento')
                ->where('parcela_id', $parcelaId)
                ->where('tipo_dato', 'pronostico')
                ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
                ->select('fecha_hora_dato', 'wind_speed')
                ->orderBy('fecha_hora_dato')
                ->get();
        }

        // Agrupar registros por hora y calcular el promedio por hora
        $agrupadosPorHora = [];
        foreach ($rows as $row) {
            if (!isset($row->fecha_hora_dato)) continue;
            $fechaHora = date('Y-m-d H:00:00', strtotime($row->fecha_hora_dato));
            if (!isset($agrupadosPorHora[$fechaHora])) {
                $agrupadosPorHora[$fechaHora] = [];
            }
            $agrupadosPorHora[$fechaHora][] = $row->wind_speed;
        }

        // Generar las horas exactas del período (no necesariamente 24 horas por día)
        $valoresPorHora = [];
        $fechaActual = Carbon::parse($fechaInicio);
        $fechaFinObj = Carbon::parse($fechaFin);

        while ($fechaActual->lt($fechaFinObj)) {
            $fechaHora = $fechaActual->format('Y-m-d H:00:00');
            if (isset($agrupadosPorHora[$fechaHora]) && !empty($agrupadosPorHora[$fechaHora])) {
                // Si hay datos para esta hora, calcular el promedio
                $valoresPorHora[] = round(array_sum($agrupadosPorHora[$fechaHora]) / count($agrupadosPorHora[$fechaHora]), 2);
            } else {
                // Si no hay datos para esta hora, usar 0
                $valoresPorHora[] = 0;
            }
            $fechaActual->addHour();
        }

        $total_horas = count($valoresPorHora);

        // Calcular datos agrupados acumulados para todo el período
        $datosAgrupados = [];
        $datosAcumulados = [
            'muy_bajo' => 0,
            'bajo' => 0,
            'optimo' => 0,
            'alto' => 0,
            'muy_alto' => 0
        ];
        if (!empty($valoresPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
            $datosAcumulados = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                $request->tipo_cultivo_id,
                $request->etapa_fenologica_id,
                'velocidad_viento',
                $valoresPorHora
            );

            // Escalar los datos a 24 horas para la visualización
            $factorEscala = 24 / $total_horas;
            $datosAgrupados[] = [
                'muy_bajo' => round($datosAcumulados['muy_bajo'] * $factorEscala),
                'bajo' => round($datosAcumulados['bajo'] * $factorEscala),
                'optimo' => round($datosAcumulados['optimo'] * $factorEscala),
                'alto' => round($datosAcumulados['alto'] * $factorEscala),
                'muy_alto' => round($datosAcumulados['muy_alto'] * $factorEscala)
            ];
        }

        // Calcular porcentaje y horas reales por rango
        $cumulo_real = [];
        foreach ($datosAcumulados as $k => $v) {
            $cumulo_real[$k] = [
                'h' => $v,
                'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0
            ];
        }

        $total_horas_periodo = $total_horas;

        // Calcular porcentajes para cada color respecto al total original
        $porcentajes = [];
        $sumaOriginal = array_sum($datosAcumulados);
        if ($sumaOriginal > 0) {
            foreach ($datosAcumulados as $k => $v) {
                $porcentajes[$k] = round(($v / $sumaOriginal) * 100);
            }
        }

        // Crear etiqueta para el período
        $fechaInicioObj = Carbon::parse($fechaInicio, 'America/Mexico_City');
        $fechaFinObj = Carbon::parse($fechaFin, 'America/Mexico_City');
        $diasEnPeriodo = $fechaInicioObj->diffInDays($fechaFinObj) + 1;
        $labels = [
            $fechaInicioObj->format('d-m-Y') . ' a ' . $fechaFinObj->format('d-m-Y')
        ];

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => [$cumulo_real],
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $labels,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }

    public function grafica_estres_presion_atmosferica(Request $request)
    {
        // Usar la nueva función para periodos exactos
        list($fechaInicio, $fechaFin) = $this->calcularPeriodoExacto($request->periodo);
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Obtener datos de precipitación por hora (solo históricos)
        $rows = DB::table('presion_atmosferica')
            ->where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->select('fecha_hora_dato', 'pressure')
            ->orderBy('fecha_hora_dato')
            ->get();

        // Si no hay suficientes datos históricos, usar datos de pronóstico
        if ($rows->count() < 5) {
            $rows = DB::table('presion_atmosferica')
                ->where('parcela_id', $parcelaId)
                ->where('tipo_dato', 'pronostico')
                ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
                ->select('fecha_hora_dato', 'pressure')
                ->orderBy('fecha_hora_dato')
                ->get();
        }

        // Agrupar registros por hora y calcular el promedio por hora
        $agrupadosPorHora = [];
        foreach ($rows as $row) {
            if (!isset($row->fecha_hora_dato)) continue;
            $fechaHora = date('Y-m-d H:00:00', strtotime($row->fecha_hora_dato));
            if (!isset($agrupadosPorHora[$fechaHora])) {
                $agrupadosPorHora[$fechaHora] = [];
            }
            $agrupadosPorHora[$fechaHora][] = $row->pressure;
        }

        // Generar las horas exactas del período (no necesariamente 24 horas por día)
        $valoresPorHora = [];
        $fechaActual = Carbon::parse($fechaInicio);
        $fechaFinObj = Carbon::parse($fechaFin);

        while ($fechaActual->lt($fechaFinObj)) {
            $fechaHora = $fechaActual->format('Y-m-d H:00:00');
            if (isset($agrupadosPorHora[$fechaHora]) && !empty($agrupadosPorHora[$fechaHora])) {
                // Si hay datos para esta hora, calcular el promedio
                $valoresPorHora[] = round(array_sum($agrupadosPorHora[$fechaHora]) / count($agrupadosPorHora[$fechaHora]), 2);
            } else {
                // Si no hay datos para esta hora, usar 0
                $valoresPorHora[] = 0;
            }
            $fechaActual->addHour();
        }

        $total_horas = count($valoresPorHora);

        // Calcular datos agrupados acumulados para todo el período
        $datosAgrupados = [];
        $datosAcumulados = [
            'muy_bajo' => 0,
            'bajo' => 0,
            'optimo' => 0,
            'alto' => 0,
            'muy_alto' => 0
        ];
        if (!empty($valoresPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
            $datosAcumulados = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                $request->tipo_cultivo_id,
                $request->etapa_fenologica_id,
                'presion_atmosferica',
                $valoresPorHora
            );

            // Escalar los datos a 24 horas para la visualización
            $factorEscala = 24 / $total_horas;
            $datosAgrupados[] = [
                'muy_bajo' => round($datosAcumulados['muy_bajo'] * $factorEscala),
                'bajo' => round($datosAcumulados['bajo'] * $factorEscala),
                'optimo' => round($datosAcumulados['optimo'] * $factorEscala),
                'alto' => round($datosAcumulados['alto'] * $factorEscala),
                'muy_alto' => round($datosAcumulados['muy_alto'] * $factorEscala)
            ];
        }

        // Calcular porcentaje y horas reales por rango
        $cumulo_real = [];
        foreach ($datosAcumulados as $k => $v) {
            $cumulo_real[$k] = [
                'h' => $v,
                'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0
            ];
        }

        $total_horas_periodo = $total_horas;

        // Calcular porcentajes para cada color respecto al total original
        $porcentajes = [];
        $sumaOriginal = array_sum($datosAcumulados);
        if ($sumaOriginal > 0) {
            foreach ($datosAcumulados as $k => $v) {
                $porcentajes[$k] = round(($v / $sumaOriginal) * 100);
            }
        }

        // Crear etiqueta para el período
        $fechaInicioObj = Carbon::parse($fechaInicio, 'America/Mexico_City');
        $fechaFinObj = Carbon::parse($fechaFin, 'America/Mexico_City');
        $diasEnPeriodo = $fechaInicioObj->diffInDays($fechaFinObj) + 1;
        $labels = [
            $fechaInicioObj->format('d-m-Y') . ' a ' . $fechaFinObj->format('d-m-Y')
        ];

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => [$cumulo_real],
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $labels,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }

    public function grafica_estres_pronostico_precipitacion_pluvial(Request $request)
    {
        $now = Carbon::now('America/Mexico_City');
        $zona_manejo = ZonaManejos::find($request->estacion_id);

        // Check if zona_manejo exists
        if (!$zona_manejo) {
            return response()->json([
                'error' => 'Zona de manejo no encontrada',
                'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
            ], 404);
        }

        $parcelaId = $zona_manejo->parcela_id;

        // Llamada a la API de OpenWeatherMap
        $response = Http::get('https://api.openweathermap.org/data/3.0/onecall', [
            'lat' => $zona_manejo->parcela->lat,
            'lon' => $zona_manejo->parcela->lon,
            'appid' => config('services.openweathermap.key'),
            'units' => 'metric',
            'tz' => '+06:00',
            'exclude' => 'current,minutely,alerts'
        ]);

        $labels = [];
        $datosAgrupados = [];
        $cumulo_real = [];
        $porcentajes = [];
        $total_horas_periodo = 24; // Por día de pronóstico
        $diasEnPeriodo = 1;
        $valores = [];
        $sumaOriginal = 0;

        // Procesar 2 días de pronóstico
        for ($i = 0; $i <= 1; $i++) {
            $fecha = $now->copy()->addDays($i + 1)->format('Y-m-d');
            $labels[] = $fecha;
            $promediosPorHora = [];
            if ($response->successful()) {
                $openWeatherData = $response->json();
                for ($h = 0; $h < 24; $h++) {
                    $hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
                    $datoHora = collect($openWeatherData['hourly'])->first(function ($hData) use ($fecha, $hora) {
                        return date('Y-m-d H:i:s', $hData['dt']) === $fecha . ' ' . $hora;
                    });
                    if ($datoHora && isset($datoHora['rain']['1h'])) {
                        // OpenWeather devuelve precipitación en mm
                        $promediosPorHora[] = round($datoHora['rain']['1h'], 2);
                    } else {
                        // Fallback: promedio diario
                        $daily = collect($openWeatherData['daily'])->first(function ($d) use ($fecha) {
                            return date('Y-m-d', $d['dt']) === $fecha;
                        });
                        if ($daily && isset($daily['rain'])) {
                            $promediosPorHora[] = round($daily['rain'] / 24, 2); // Distribuir precipitación diaria en 24 horas
                        } else {
                            $promediosPorHora[] = null;
                        }
                    }
                }
            } else {
                $promediosPorHora = array_fill(0, 24, null);
            }

            // Calcular datos agrupados para cada día de pronóstico
            $agrupado = [
                'muy_bajo' => 0,
                'bajo' => 0,
                'optimo' => 0,
                'alto' => 0,
                'muy_alto' => 0
            ];
            if (!empty($promediosPorHora) && $request->tipo_cultivo_id && $request->etapa_fenologica_id) {
                $agrupado = NutricionEtapaFenologicaTipoCultivo::semaforoNutricionAgrupado(
                    $request->tipo_cultivo_id,
                    $request->etapa_fenologica_id,
                    'precipitacion_pluvial',
                    $promediosPorHora
                );
            } else {
                $agrupado['optimo'] = count($promediosPorHora);
            }

            // Guardar el total real de horas por rango
            $datosAgrupados[] = [
                'muy_bajo' => $agrupado['muy_bajo'],
                'bajo' => $agrupado['bajo'],
                'optimo' => $agrupado['optimo'],
                'alto' => $agrupado['alto'],
                'muy_alto' => $agrupado['muy_alto']
            ];

            // Calcular porcentaje y horas reales por rango
            $cumulo = [];
            $total_horas = array_sum($agrupado);
            foreach ($agrupado as $k => $v) {
                $cumulo[$k] = [
                    'h' => $v,
                    'p' => $total_horas > 0 ? min(100, round(($v / $total_horas) * 100)) : 0
                ];
            }
            // Agregar suma total de horas al cumulo
            $cumulo['total_horas'] = $total_horas;
            $cumulo_real[] = $cumulo;

            // Para porcentajes y normalización
            $valores = $agrupado;
            $sumaOriginal = array_sum($valores);
            $normalizados = [];
            if ($sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $normalizados[$k] = round($v * 24 / $sumaOriginal);
                }
                $sumaNormalizada = array_sum($normalizados);
                if ($sumaNormalizada !== 24) {
                    $diff = 24 - $sumaNormalizada;
                    $mayorKey = array_keys($normalizados, max($normalizados))[0];
                    $normalizados[$mayorKey] += $diff;
                }
                $datosAgrupados[$i] = $normalizados;
            }

            // Porcentajes por rango respecto al total
            $porcentajes_dia = [];
            if (!empty($valores) && $sumaOriginal > 0) {
                foreach ($valores as $k => $v) {
                    $porcentajes_dia[$k] = round(($v / $sumaOriginal) * 100);
                }
            }
            $porcentajes[] = $porcentajes_dia;
        }

        $fechas = $labels;

        return response()->json([
            'labels' => $labels,
            'datos_agrupados' => $datosAgrupados,
            'cumulo_real' => $cumulo_real,
            'total_horas_periodo' => $total_horas_periodo,
            'fechas' => $fechas,
            'porcentajes' => $porcentajes,
            'diasEnPeriodo' => $diasEnPeriodo,
        ]);
    }

    public function grafica_variables_multiples(Request $request)
    {
        try {
            // Validaciones básicas
            if (!$request->estacion_id) {
                return response()->json(['error' => 'estacion_id requerido'], 400);
            }

            if (!$request->periodo) {
                return response()->json(['error' => 'periodo requerido'], 400);
            }

            $fechas = $this->calcularPeriodo($request->periodo, $request->startDate, $request->endDate);

            $zona_manejo = ZonaManejos::find($request->estacion_id);

            // Check if zona_manejo exists
            if (!$zona_manejo) {
                return response()->json([
                    'error' => 'Zona de manejo no encontrada',
                    'message' => 'La zona de manejo con ID ' . $request->estacion_id . ' no existe'
                ], 404);
            }

            $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

            // Validar variables
            $variables = $request->variables ?? [];
            $agrupaciones = $request->agrupaciones ?? [];

            if (empty($variables) || empty($agrupaciones)) {
                return response()->json([
                    'error' => 'Variables y agrupaciones requeridas',
                    'message' => 'Debe especificar al menos una variable y una agrupación'
                ], 400);
            }

            // Definir las columnas válidas en la tabla estacion_dato
            $columnasValidas = [
                'temperatura',
                'humedad_relativa',
                'radiacion_solar',
                'precipitacion_acumulada',
                'velocidad_viento',
                'direccion_viento',
                'co2',
                'ph',
                'phos',
                'nit',
                'pot',
                'temperatura_suelo',
                'conductividad_electrica',
                'potencial_de_hidrogeno',
                'viento',
                'humedad_15',
                'temperatura_lvl1'
            ];

            // Validar que todas las variables solicitadas existan
            $variablesInvalidas = array_diff($variables, $columnasValidas);
            if (!empty($variablesInvalidas)) {
                return response()->json([
                    'error' => 'Variables no válidas',
                    'message' => 'Las siguientes variables no existen en la tabla: ' . implode(', ', $variablesInvalidas),
                    'variables_validas' => $columnasValidas
                ], 400);
            }

            // Usar el mismo formato de agrupación que grafica_temperatura
            $select = '';
            $selectWind = '';
            // Rango por defecto (de helpers existentes)
            $rangoInicio = $fechas[1];
            $rangoFin = $fechas[0];
            switch ($fechas[2]) {
                case 'd':
                    $tipo = 'Día';
                    $select = 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ';
                    $selectWind = 'DATE_FORMAT(datos_viento.fecha_hora_dato, "%d-%m-%Y") as fecha, ';
                    break;
                case 's':
                    $tipo = 'Semana';
                    $select = 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ';
                    $selectWind = 'DATE_FORMAT(datos_viento.fecha_hora_dato, "%d-%m-%Y") as fecha, ';
                    break;
                case 'm':
                    $tipo = 'Mes';
                    $select = 'DATE_FORMAT(estacion_dato.created_at, "%m-%Y") as fecha, ';
                    $selectWind = 'DATE_FORMAT(datos_viento.fecha_hora_dato, "%m-%Y") as fecha, ';
                    break;
                case '4_horas':
                    $tipo = 'Cada 4 horas';
                    $select = "CASE
                        WHEN HOUR(estacion_dato.created_at) BETWEEN 0 AND 3  THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 04:00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN 4 AND 7  THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 08:00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN 8 AND 11 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 12:00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN 12 AND 15 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 16:00')
                        WHEN HOUR(estacion_dato.created_at) BETWEEN 16 AND 19 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 20:00')
                        ELSE CONCAT(DATE_FORMAT(DATE_ADD(estacion_dato.created_at, INTERVAL 1 DAY), '%d-%m-%Y'), ' 00:00') END as fecha,";
                    $selectWind = "CASE
                        WHEN HOUR(datos_viento.fecha_hora_dato) BETWEEN 0 AND 3  THEN CONCAT(DATE_FORMAT(datos_viento.fecha_hora_dato, '%d-%m-%Y'), ' 04:00')
                        WHEN HOUR(datos_viento.fecha_hora_dato) BETWEEN 4 AND 7  THEN CONCAT(DATE_FORMAT(datos_viento.fecha_hora_dato, '%d-%m-%Y'), ' 08:00')
                        WHEN HOUR(datos_viento.fecha_hora_dato) BETWEEN 8 AND 11 THEN CONCAT(DATE_FORMAT(datos_viento.fecha_hora_dato, '%d-%m-%Y'), ' 12:00')
                        WHEN HOUR(datos_viento.fecha_hora_dato) BETWEEN 12 AND 15 THEN CONCAT(DATE_FORMAT(datos_viento.fecha_hora_dato, '%d-%m-%Y'), ' 16:00')
                        WHEN HOUR(datos_viento.fecha_hora_dato) BETWEEN 16 AND 19 THEN CONCAT(DATE_FORMAT(datos_viento.fecha_hora_dato, '%d-%m-%Y'), ' 20:00')
                        ELSE CONCAT(DATE_FORMAT(DATE_ADD(datos_viento.fecha_hora_dato, INTERVAL 1 DAY), '%d-%m-%Y'), ' 00:00') END as fecha,";
                    // Forzar ventana: desde la hora actual redondeada a 4h hacia abajo, 24h hacia atrás
                    $ahora = \Carbon\Carbon::now('America/Mexico_City')->startOfHour();
                    $ahora->subHours($ahora->hour % 4);
                    $inicioVentana = $ahora->copy()->subHours(24);
                    $rangoInicio = $inicioVentana->format('Y-m-d H:i:s');
                    $rangoFin = $ahora->format('Y-m-d H:i:s');
                    break;
                case '8_horas':
                    $tipo = 'Cada 8 horas';
                    $select = '
                    case
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 0 and 7 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 08:00")
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 8 and 15 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 16:00")
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 16 and 23 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 00:00")
                    end as fecha,';
                    $selectWind = '
                    case
                    when DATE_FORMAT(datos_viento.fecha_hora_dato, "%H") between 0 and 7 then concat(DATE_FORMAT(datos_viento.fecha_hora_dato, "%d-%m-%Y")," 08:00")
                    when DATE_FORMAT(datos_viento.fecha_hora_dato, "%H") between 8 and 15 then concat(DATE_FORMAT(datos_viento.fecha_hora_dato, "%d-%m-%Y")," 16:00")
                    when DATE_FORMAT(datos_viento.fecha_hora_dato, "%H") between 16 and 23 then concat(DATE_FORMAT(datos_viento.fecha_hora_dato, "%d-%m-%Y")," 00:00")
                    end as fecha,';
                    break;
                case '12_horas':
                    $tipo = 'Cada 12 horas';
                    $select = '
                    case
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 0 and 11 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 12:00")
                    when DATE_FORMAT(estacion_dato.created_at, "%H") between 12 and 23 then concat(DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y")," 00:00")
                    end as fecha,';
                    $selectWind = '
                    case
                    when DATE_FORMAT(datos_viento.fecha_hora_dato, "%H") between 0 and 11 then concat(DATE_FORMAT(datos_viento.fecha_hora_dato, "%d-%m-%Y")," 12:00")
                    when DATE_FORMAT(datos_viento.fecha_hora_dato, "%H") between 12 and 23 then concat(DATE_FORMAT(datos_viento.fecha_hora_dato, "%d-%m-%Y")," 00:00")
                    end as fecha,';
                    break;
                case 'crudos':
                    $tipo = 'Crudos';
                    $select = 'estacion_dato.created_at as fecha, ';
                    $selectWind = 'datos_viento.fecha_hora_dato as fecha, ';
                    break;
                default:
                    break;
            }

            // Separar variables: viento vs estación
            $variablesEstacion = array_values(array_filter($variables, fn($v) => $v !== 'velocidad_viento'));
            $requiereViento = in_array('velocidad_viento', $variables, true);

            // Construir SELECT para variables de estacion_dato
            foreach ($variablesEstacion as $variable) {
                foreach ($agrupaciones as $agrupacion) {
                    $tipo_agrupacion = explode('|', $agrupacion)[0]; // max, min, avg
                    $select .= "{$tipo_agrupacion}({$variable}) as {$tipo_agrupacion}s" . ucfirst($variable) . ", ";
                }
            }

            // Construir SELECT para viento si se solicitó
            if ($requiereViento) {
                foreach ($agrupaciones as $agrupacion) {
                    $tipo_agrupacion = explode('|', $agrupacion)[0];
                    // Mapear a columna wind_speed en datos_viento
                    $selectWind .= "{$tipo_agrupacion}(datos_viento.wind_speed) as {$tipo_agrupacion}sVelocidad_viento, ";
                }
            }

            // Remover la última coma y espacio
            $select = rtrim($select, ', ');
            $selectWind = rtrim($selectWind, ', ');

            // Ejecutar consulta para estacion_dato (si hay variables)
            $rowsEstacion = [];
            if (!empty($variablesEstacion)) {
                $rowsEstacion = EstacionDato::whereIn('estacion_id', $ids)
                    ->whereBetween('created_at', [$rangoInicio, $rangoFin])
                    ->selectRaw($select)
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->get()
                    ->toArray();
                Log::info('grafica_variables_multiples: estacion_dato', [
                    'fechas' => $fechas,
                    'variables_estacion' => $variablesEstacion,
                    'rows_estacion_count' => count($rowsEstacion),
                    'sample_fechas_estacion' => array_slice(array_column($rowsEstacion, 'fecha'), 0, 3)
                ]);
            }

            // Ejecutar consulta para datos_viento si se solicitó
            $rowsViento = [];
            if ($requiereViento) {
                $rowsViento = DB::table('datos_viento')
                    ->where('parcela_id', $zona_manejo->parcela_id)
                    ->where('tipo_dato', 'historico')
                    ->whereBetween('fecha_hora_dato', [$rangoInicio, $rangoFin])
                    ->selectRaw($selectWind)
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->get()
                    ->toArray();
                Log::info('grafica_variables_multiples: datos_viento', [
                    'fechas' => $fechas,
                    'rows_viento_count' => count($rowsViento),
                    'sample_fechas_viento' => array_slice(array_column($rowsViento, 'fecha'), 0, 3)
                ]);
            }

            // Para 4_horas, generar malla fija de 6 slots y fusionar datos
            if ($fechas[2] === '4_horas') {
                // Generar malla fija de 6 slots (24 horas / 4) usando el mismo formato que las consultas SQL
                $inicioMalla = \Carbon\Carbon::parse($fechas[1], 'America/Mexico_City');
                $finMalla = \Carbon\Carbon::parse($fechas[0], 'America/Mexico_City');

                $slotsGenerados = [];
                $currentSlot = $inicioMalla->copy();

                // Generar exactamente 6 slots (24 horas / 4) con formato que coincida con SQL
                // Las consultas SQL devuelven: 12:00, 16:00, 20:00, 00:00, 04:00, 08:00
                $horasSlots = [12, 16, 20, 0, 4, 8]; // Horas exactas que devuelven las consultas SQL

                for ($i = 0; $i < 6; $i++) {
                    $currentSlot->addHours(4); // Avanzar 4 horas para el siguiente slot
                    $horaSlot = $horasSlots[$i];

                    // Si la hora es 0, significa que es el día siguiente (después de las 20:00)
                    if ($horaSlot == 0) {
                        $slotFormatted = $currentSlot->format('d-m-Y') . ' 00:00';
                    } else {
                        $slotFormatted = $currentSlot->format('d-m-Y') . ' ' . sprintf('%02d:00', $horaSlot);
                    }
                    $slotsGenerados[] = $slotFormatted;
                }

                Log::info('grafica_variables_multiples: slots generados', [
                    'slots_generados' => $slotsGenerados
                ]);

                // Crear mapa con slots fijos
                $mapa = [];
                foreach ($slotsGenerados as $slot) {
                    $mapa[$slot] = ['fecha' => $slot];
                }

                // Fusionar datos de estacion_dato
                $fechasNormalizadas = [];
                foreach ($rowsEstacion as $r) {
                    // Normalizar formato de fecha de estacion_dato (ISO UTC -> d-m-Y H:i)
                    $fechaNormalizada = null;
                    if (is_string($r['fecha']) && strpos($r['fecha'], 'T') !== false) {
                        // Formato ISO: 2025-10-06T12:00:00.000000Z
                        // Las fechas ISO ya están en la hora correcta, solo extraer la fecha y hora
                        $carbon = \Carbon\Carbon::parse($r['fecha']);
                        $fechaNormalizada = $carbon->format('d-m-Y H:i');
                    } else {
                        $fechaNormalizada = $r['fecha'];
                    }

                    $fechasNormalizadas[] = $fechaNormalizada;

                    if (isset($mapa[$fechaNormalizada])) {
                        $r['fecha'] = $fechaNormalizada; // Actualizar la fecha en el array
                        $mapa[$fechaNormalizada] = array_merge($mapa[$fechaNormalizada], $r);
                    }
                }

                Log::info('grafica_variables_multiples: fusion estacion_dato', [
                    'fechas_originales' => array_slice(array_column($rowsEstacion, 'fecha'), 0, 3),
                    'fechas_normalizadas' => array_slice($fechasNormalizadas, 0, 3),
                    'slots_disponibles' => array_slice($slotsGenerados, 0, 3)
                ]);

                // Fusionar datos de datos_viento
                foreach ($rowsViento as $r) {
                    $fila = (array) $r;
                    if (isset($mapa[$fila['fecha']])) {
                        $mapa[$fila['fecha']] = array_merge($mapa[$fila['fecha']], $fila);
                    }
                }

                $rows = array_values($mapa);
            } else {
                // Lógica original para otros tipos de agrupación
                $mapa = [];

                // Agregar datos de estacion_dato
                foreach ($rowsEstacion as $r) {
                    $mapa[$r['fecha']] = $r;
                }

                // Agregar datos de datos_viento, fusionando con estacion_dato si existe
                foreach ($rowsViento as $r) {
                    $fila = (array) $r;
                    if (isset($mapa[$fila['fecha']])) {
                        // Fusionar datos existentes con nuevos datos de viento
                        $mapa[$fila['fecha']] = array_merge($mapa[$fila['fecha']], $fila);
                    } else {
                        // Solo datos de viento para esta fecha
                        $mapa[$fila['fecha']] = $fila;
                    }
                }

                ksort($mapa);
                $rows = array_values($mapa);
            }
            Log::info('grafica_variables_multiples: fusion final', [
                'rows_final_count' => count($rows),
                'requiere_viento' => $requiereViento
            ]);

            // Transform rows into separate arrays
            $labels = array_column($rows, 'fecha');

            // Procesar cada variable y agrupación
            $resultados = [];
            foreach ($variables as $variable) {
                foreach ($agrupaciones as $agrupacion) {
                    $tipo_agrupacion = explode('|', $agrupacion)[0];
                    $nombre_columna = "{$tipo_agrupacion}s" . ucfirst($variable);

                    $valores = array_column($rows, $nombre_columna);

                    // Manejar valores nulos - reemplazar con 0 o null según corresponda
                    $valores = array_map(function ($v) {
                        return $v === null ? null : $v;
                    }, $valores);

                    // Redondear promedios a 2 decimales
                    if ($tipo_agrupacion === 'avg') {
                        $valores = array_map(function ($v) {
                            return $v === null ? null : round($v, 2);
                        }, $valores);
                    }

                    $resultados[$nombre_columna] = $valores;
                }
            }

            $labels = array_map(function ($fecha) {
                // Si ya es un string tipo ISO, conviértelo
                if ($fecha instanceof \DateTimeInterface) {
                    return $fecha->format('Y-m-d H:i:s');
                }
                if (is_string($fecha) && strpos($fecha, 'T') !== false) {
                    return date('Y-m-d H:i:s', strtotime($fecha));
                }
                return $fecha;
            }, $labels);

            $response = array_merge([
                'labels' => $labels
            ], $resultados);

            // Debug temporal - remover después de verificar
            if (in_array('velocidad_viento', $variables)) {
                $response['debug'] = [
                    'rows_estacion_count' => count($rowsEstacion),
                    'rows_viento_count' => count($rowsViento),
                    'rows_final_count' => count($rows),
                    'variables_estacion' => $variablesEstacion,
                    'requiere_viento' => $requiereViento,
                    'fechas' => $fechas,
                    'sample_rows' => array_slice($rows, 0, 3)
                ];
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error en grafica_variables_multiples:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function view_grafica_variables_multiples(Request $request)
    {
        $zona_manejo = ZonaManejos::find($request->zona_manejo);
        $periodo = $request->periodo;
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        return view('components.grafica_variables_multiples', [
            'zonaManejoId' => $zona_manejo ? $zona_manejo->id : null,
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function test_grafica_variables_multiples(Request $request)
    {
        return response()->json([
            'message' => 'Función de prueba funcionando',
            'request' => $request->all(),
            'timestamp' => now()
        ]);
    }

    public function jsonEnfermedades($enfermedad_id, $tipo_cultivo_id, $zona_manejo_id, $periodo, $startDate, $endDate)
    {

        // Obtener estaciones de la zona de manejo desde la tabla pivote
        $estaciones = DB::table('zona_manejos_estaciones')
            ->where('zona_manejo_id', $zona_manejo_id)
            ->pluck('estacion_id')
            ->toArray();

        // Obtener datos por hora desde las estaciones
        $datosPorHora = collect();

        // Generar rango de horas entre startDate y endDate
        if ($periodo && $zona_manejo_id) {
            // Si se proporciona período y zona, calcular fechas exactas por hora
            $fechasCalculadas = $this->calcularPeriodoExacto($periodo);
            $fechaInicio = $fechasCalculadas[0]; // Fecha de inicio exacta
            $fechaFin = $fechasCalculadas[1];    // Fecha de fin exacta

            // Usar horas exactas sin modificar
            $fechasReales = [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ];
        } else {
            // Si no se proporciona período, usar startDate y endDate con redondeo a hora
            $fechaInicioDefault = Carbon::now('America/Mexico_City')->startOfHour()->subHours(24);
            $fechaFinDefault = Carbon::now('America/Mexico_City')->startOfHour();

            $fechasReales = [
                'inicio' => $startDate ? Carbon::parse($startDate)->startOfHour()->format('Y-m-d H:i:s') : $fechaInicioDefault->format('Y-m-d H:i:s'),
                'fin' => $endDate ? Carbon::parse($endDate)->startOfHour()->format('Y-m-d H:i:s') : $fechaFinDefault->format('Y-m-d H:i:s')
            ];
        }

        // Obtener datos de estación por hora
        $datosEstacion = DB::table('estacion_dato')
            ->whereIn('estacion_id', $estaciones)
            ->whereBetween('created_at', [$fechasReales['inicio'], $fechasReales['fin']])
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as fecha_hora,
                AVG(temperatura) as temperatura_promedio,
                AVG(humedad_relativa) as humedad_promedio,
                COUNT(*) as registros
            ')
            ->groupBy('fecha_hora')
            ->orderBy('fecha_hora')
            ->get();

        // Obtener parámetros de riesgo de la enfermedad
        $parametrosRiesgo = DB::table('tipo_cultivos_enfermedades')
            ->where('enfermedad_id', $enfermedad_id)
            ->where('tipo_cultivo_id', $tipo_cultivo_id)
            ->first();

        // Procesar cada hora
        foreach ($datosEstacion as $datoHora) {
            $temperatura = $datoHora->temperatura_promedio ?? 0;
            $humedad = $datoHora->humedad_promedio ?? 0;

            // Determinar estatus basado en condiciones
            $estatus = 'Sin riesgo';
            $condicionesFavorables = false;

            if ($parametrosRiesgo) {
                $condicionesFavorables = $this->verificarCondicionesRiesgo(
                    $humedad,
                    $temperatura,
                    $parametrosRiesgo->riesgo_humedad,
                    $parametrosRiesgo->riesgo_humedad_max,
                    $parametrosRiesgo->riesgo_temperatura,
                    $parametrosRiesgo->riesgo_temperatura_max
                );

                if ($condicionesFavorables) {
                    // Determinar nivel de riesgo basado en intensidad
                    if ($humedad >= 90 && $temperatura >= 25) {
                        $estatus = 'Alto';
                    } elseif ($humedad >= 80 || $temperatura >= 22) {
                        $estatus = 'Bajo';
                    } else {
                        $estatus = 'Sin riesgo';
                    }
                }
            }

            $datosPorHora->push([
                'fecha' => Carbon::parse($datoHora->fecha_hora)->format('Y-m-d'),
                'hora' => Carbon::parse($datoHora->fecha_hora)->format('H:i'),
                'fecha_hora_completa' => $datoHora->fecha_hora,
                'temperatura' => round($temperatura, 2),
                'humedad' => round($humedad, 2),
                'estatus' => $estatus,
                'condiciones_favorables' => $condicionesFavorables,
                'registros' => $datoHora->registros
            ]);
        }

        // Si no hay datos, crear un registro por defecto
        if ($datosPorHora->isEmpty()) {
            $fechaInicioCarbon = Carbon::parse($fechasReales['inicio']);
            $datosPorHora->push([
                'fecha' => $fechaInicioCarbon->format('Y-m-d'),
                'hora' => $fechaInicioCarbon->format('H:i'),
                'fecha_hora_completa' => $fechaInicioCarbon->format('Y-m-d H:00:00'),
                'temperatura' => 0,
                'humedad' => 0,
                'estatus' => 'Sin riesgo',
                'condiciones_favorables' => false,
                'registros' => 0
            ]);
        }

        // Agrupar por fecha y contar por categorías
        $datosAgrupados = $datosPorHora->groupBy('fecha');
        $resultado = collect();

        foreach ($datosAgrupados as $fecha => $horas) {
            $sinRiesgo = $horas->where('estatus', 'Sin riesgo')->count();
            $bajo = $horas->where('estatus', 'Bajo')->count();
            $alto = $horas->where('estatus', 'Alto')->count();
            $total = $horas->count();

            $resultado->push([
                'tipo' => 'Histórico',
                'fecha' => $fecha,
                'fecha_formateada' => Carbon::parse($fecha)->format('d-m-y'),
                'sin_riesgo' => $sinRiesgo,
                'bajo' => $bajo,
                'alto' => $alto,
                'total' => $total,
                'detalle_horas' => $horas->map(function ($hora) {
                    return [
                        'hora' => $hora['hora'],
                        'temperatura' => $hora['temperatura'],
                        'humedad' => $hora['humedad'],
                        'estatus' => $hora['estatus'],
                        'condiciones_favorables' => $hora['condiciones_favorables'] ?? false
                    ];
                })
            ]);
        }

        $resultado = $resultado->sortByDesc(function ($item) {
            return $item['fecha'];
        })->values();

        return [
            'resultado' => $resultado,
            'fechasReales' => $fechasReales,
        ];
    }

    /**
     * Verifica si las condiciones ambientales cumplen los parámetros de riesgo de una enfermedad
     * Valida humedad y temperatura contra los umbrales configurados
     */
    private function verificarCondicionesRiesgo($humedad, $temperatura, $riesgoHumedad, $riesgoHumedadMax, $riesgoTemperatura, $riesgoTemperaturaMax)
    {
        // Verificar humedad
        $humedadCumple = $humedad >= $riesgoHumedad && $humedad <= $riesgoHumedadMax;

        // Verificar temperatura
        $temperaturaCumple = $temperatura >= $riesgoTemperatura && $temperatura <= $riesgoTemperaturaMax;

        // Ambas condiciones deben cumplirse
        return $humedadCumple && $temperaturaCumple;
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

public function lectura_correctivos_por_zona($zonaIdExt){
    // Lógica para la lectura de fechas correctivos por zona_id
    $correctivos = DB::table('lote_correctivo as lc')
    ->join('correctivos as c', 'lc.correctivo_id', '=', 'c.id')
    ->join('zona_manejo_lote_externo as zmle', 'lc.lote_id', '=', 'zmle.externo_lote_id')
    ->join('zona_manejos as zm', 'zmle.zona_manejo_id', '=', 'zm.id')
    ->where('lc.lote_id', $zonaIdExt)
    ->select(
        'lc.id',
        DB::raw('c.nombre as correctivo'),
        'c.unidad_medida',
        'c.efecto_esperado',
        'lc.fecha_aplicacion',
        'lc.cantidad_sugerida'
    )
    ->get();

    return response()->json($correctivos);
}

public function lectura_correctivos_por_fecha($zonaIdExt, $fecha){
    // Lógica para la lectura de correctivos por zona_id y fecha
    // fecha viene 2026-01-01

    $anio = Carbon::parse($fecha)->year;

    $correctivos = DB::table('lote_correctivo as lc')
    ->join('correctivos as c', 'lc.correctivo_id', '=', 'c.id')
    ->join('zona_manejo_lote_externo as zmle', 'lc.lote_id', '=', 'zmle.externo_lote_id')
    ->join('zona_manejos as zm', 'zmle.zona_manejo_id', '=', 'zm.id')
    ->where('lc.lote_id', $zonaIdExt)
    ->when($anio, function ($q) use ($anio) {
        return $q->whereYear('lc.fecha_aplicacion', (int) $anio);
    })
    ->select(
        'lc.id',
        DB::raw('c.nombre as correctivo'),
        'c.unidad_medida',
        'c.efecto_esperado',
        'lc.fecha_aplicacion',
        'lc.cantidad_sugerida'
    )
    ->get();


    return response()->json($correctivos);  
}
public function cargar_correctivos_por_zona_anio(Request $request, $zonaIdExt, $anio){
    // Lógica para la lectura de correctivos por zona_id y año
    // en  zona_manejo_lote_externo  es donde se encuentra zona_manejo_id y lote_id (externo_lote_id) que se relaciona con lote_correctivo.lote_id
    $correctivos = DB::table('zona_manejo_lote_externo as zmle')
    ->join('lote_correctivo as lc', 'zmle.externo_lote_id', '=', 'lc.lote_id')
    ->join('correctivos as c', 'lc.correctivo_id', '=', 'c.id')
    ->where('zmle.zona_manejo_id', $zonaIdExt)
    ->whereYear('lc.fecha_aplicacion', (int) $anio)
    ->select(
        'lc.id',
        DB::raw('c.nombre as correctivo'),
        'c.unidad_medida',
        'c.efecto_esperado',
        'lc.fecha_aplicacion',
        'lc.cantidad_sugerida'
    )
    ->get();

    return response()->json($correctivos);
}
  public function fertilidad(Request $request)
{
    // 1) Validar query param
    $zonaId = $request->query('zona_manejo_id');

    if (!$zonaId) {
        return response()->json([
            'success' => false,
            'message' => 'zona_manejo_id es requerido'
        ], 422);
    }

    // 2) Obtener externo_lote_id (local MySQL)
    $externoLoteId = DB::table('zona_manejo_lote_externo')
        ->where('zona_manejo_id', $zonaId)
        ->value('externo_lote_id'); // más directo que pluck()->first()

    if (!$externoLoteId) {
        return response()->json([
            'success' => false,
            'message' => 'No se encontró externo_lote_id para la zona indicada',
            'zona_manejo_id' => (int) $zonaId
        ], 404);
    }

    // 3) Consultar año máximo en SQL Server (external)
    $anioMax = DB::connection('external')
        ->table('icamex2.dbo.lote_indicador_icp')
        ->where('id_lote', $externoLoteId)
        ->max('anio');

    if (!$anioMax) {
        return response()->json([
            'success' => true,
            'zona_manejo_id' => (int) $zonaId,
            'externo_lote_id' => (int) $externoLoteId,
            'anio' => null,
            'data' => []
        ]);
    }

    // 4) Traer la estructura requerida para el último año
    $data = DB::connection('external')
        ->table('icamex2.dbo.lote_indicador_icp as li')
        ->join('icamex2.dbo.indicador as ind', 'ind.id_indicador', '=', 'li.id_indicador')
        ->where('li.id_lote', $externoLoteId)
        ->where('li.anio', $anioMax)
        ->where ('li.id_seccion', 1) // Solo indicadores de fertilidad (Saturacion de las Bases)
        ->select([
            'ind.indicador as indicador',
            'li.icp as icp',
            'li.resultado as resultado',
            'li.fp as Ponderacion',
            'li.fr as Restriccion',
            'li.nivel as Nivel',
            'ind.descripcion as descripcion',
        ])
        ->orderBy('li.id_seccion')
        ->orderBy('li.id_indicador')
        ->get();

    // 5) Respuesta
    return response()->json([
        'success' => true,
        'zona_manejo_id' => (int) $zonaId,
        'externo_lote_id' => (int) $externoLoteId,
        'anio' => (int) $anioMax,
        'data' => $data
    ]);
}

}
