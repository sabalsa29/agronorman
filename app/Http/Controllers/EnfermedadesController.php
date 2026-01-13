<?php

namespace App\Http\Controllers;

use App\Models\Cultivo;
use App\Models\TipoCultivosEnfermedad;
use App\Models\Enfermedades;
use App\Models\TipoCultivos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnfermedadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Se cargan las enfermedades con sus especies asociadas.
        $enfermedades = Enfermedades::with('tipoCultivos')->get();

        // Puedes transformar cada registro para incluir ya la cadena de especies y los botones.
        $enfermedades->transform(function ($enfermedades) {
            // Transformar la columna de especies en una cadena separada por comas.
            $enfermedades->tipo_cultivos_list = implode(', ', $enfermedades->tipoCultivos->pluck('nombre')->toArray());
            return $enfermedades;
        });

        return view('enfermedades.index', [
            "section_name" => "Enfermedades",
            "section_description" => "Enfermedades de las plantas",
            "list" => compact('enfermedades'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('enfermedades.create', [
            "section_name" => "Crear Enfermedad",
            "section_description" => "Crear una nueva enfermedad",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:enfermedades,nombre',
            'status' => 'boolean',
        ]);

        $enfermedad = Enfermedades::create([
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'status' => $request->status ?? true,
        ]);

        return redirect()->route('enfermedades.index')->with('success', 'Enfermedad creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Enfermedades $enfermedades)
    {
        return view('enfermedades.show', [
            "section_name" => "Ver Enfermedad",
            "section_description" => "Detalles de la enfermedad",
            "enfermedad" => $enfermedades,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Enfermedades $enfermedade)
    {

        return view('enfermedades.edit', [
            "section_name" => "Editar Enfermedad",
            "section_description" => "Editar la enfermedad: " . $enfermedade->nombre,
            "enfermedade" => $enfermedade,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Enfermedades $enfermedade)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:enfermedades,nombre,' . $enfermedade->id,
        ]);

        $enfermedade->update([
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'status' => $request->status,
        ]);

        return redirect()->route('enfermedades.index')->with('success', 'Enfermedad actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Enfermedades $enfermedade)
    {
        $enfermedade->delete();
        return redirect()->route('enfermedades.index')->with('success', 'Enfermedad eliminada correctamente.');
    }


    // 1) Listar cultivos y marcar los ya asociados
    public function cultivosIndex(Enfermedades $enfermedad)
    {
        $especie_enfermedad = TipoCultivosEnfermedad::with('enfermedad')->where('enfermedad_id', $enfermedad->id)->get();
        return view('enfermedades.enfermedad_cultivo.index', [
            "section_name" => "Enfermedad: " . $enfermedad->nombre,
            "section_description" => "Tipos de Cultivo de la Enfermedad",
            "enfermedad" => $enfermedad,
            "list" => $especie_enfermedad,
        ]);
    }

    // 2) Form para asociar un cultivo nuevo
    public function cultivosCreate(Enfermedades $enfermedad)
    {
        $cultivos = TipoCultivos::all();
        return view('enfermedades.enfermedad_cultivo.create', [
            "section_name" => "Enfermdad: " . $enfermedad->nombre,
            "section_description" => "A continuación elige los tipos de cultivo que pueden contagiarse de Antracnosis y configura sus parámetros.",
            "cultivos" => $cultivos,
            "enfermedad" => $enfermedad,
        ]);
    }

    // 3) Guardar la asociación (attach)
    public function cultivosStore(Request $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'enfermedad_id' => 'required|exists:enfermedades,id',
            'cultivo_ids' => 'required|array|min:1',
            'cultivo_ids.*' => 'exists:tipo_cultivos,id',
            'riesgo_humedad' => 'nullable|numeric',
            'riesgo_temperatura' => 'nullable|numeric',
            'riesgo_humedad_max' => 'nullable|numeric',
            'riesgo_temperatura_max' => 'nullable|numeric',
            'riesgo_medio' => 'nullable|integer',
            'riesgo_mediciones' => 'nullable|integer',
        ]);

        // Eliminar asociaciones existentes para esta enfermedad
        TipoCultivosEnfermedad::where('enfermedad_id', $request->enfermedad_id)->delete();

        // Crear las nuevas asociaciones para cada tipo de cultivo seleccionado
        foreach ($request->cultivo_ids as $tipoCultivoId) {
            TipoCultivosEnfermedad::create([
                'enfermedad_id' => $request->enfermedad_id,
                'tipo_cultivo_id' => $tipoCultivoId,
                'riesgo_humedad' => $request->riesgo_humedad,
                'riesgo_temperatura' => $request->riesgo_temperatura,
                'riesgo_humedad_max' => $request->riesgo_humedad_max,
                'riesgo_temperatura_max' => $request->riesgo_temperatura_max,
                'riesgo_medio' => $request->riesgo_medio,
                'riesgo_mediciones' => $request->riesgo_mediciones,
            ]);
        }

        // Redirigir a la lista de enfermedades con un mensaje de éxito
        $cultivosCount = count($request->cultivo_ids);
        $mensaje = $cultivosCount == 1 ? 'Tipo de Cultivo asociado a la enfermedad correctamente.' : $cultivosCount . ' tipos de cultivo asociados a la enfermedad correctamente.';

        return redirect()->route('enfermedades.cultivos.index', ['enfermedad' => $request->enfermedad_id])->with('success', $mensaje);
    }

    // 4) Editar datos de la relación (si tu pivot tuviera campos extra)
    public function cultivosEdit(Enfermedades $enfermedad, TipoCultivos $tipoCultivo)
    {
        $cultivos = TipoCultivos::all();
        $registro = TipoCultivosEnfermedad::where('enfermedad_id', $enfermedad->id)->where('tipo_cultivo_id', $tipoCultivo->id)->firstOrFail();
        return view('enfermedades.enfermedad_cultivo.edit', [
            "section_name" => "Editar: " . $registro->enfermedad->nombre . " - " . $registro->tipoCultivo->nombre,
            "section_description" => "Edita los parámetros de riesgo para este tipo de cultivo. El tipo de cultivo no se puede modificar.",
            "especie_enfermedad" => $registro,
            "cultivos" => $cultivos,
        ]);
    }

    // 5) Actualizar datos de la pivot (sync sin romper otras asociaciones)
    public function cultivosUpdate(Request $request, Enfermedades $enfermedad, TipoCultivos $tipoCultivo)
    {
        // Validar los datos de entrada
        $request->validate([
            'riesgo_humedad' => 'nullable|numeric',
            'riesgo_temperatura' => 'nullable|numeric',
            'riesgo_humedad_max' => 'nullable|numeric',
            'riesgo_temperatura_max' => 'nullable|numeric',
            'riesgo_medio' => 'nullable|integer',
            'riesgo_mediciones' => 'nullable|integer',
        ]);

        // Actualizar la especie_enfermedad
        TipoCultivosEnfermedad::updateOrCreate(
            [
                'enfermedad_id' => $enfermedad->id,
                'tipo_cultivo_id' => $tipoCultivo->id,
            ],
            [
                'riesgo_humedad' => $request->riesgo_humedad,
                'riesgo_temperatura' => $request->riesgo_temperatura,
                'riesgo_humedad_max' => $request->riesgo_humedad_max,
                'riesgo_temperatura_max' => $request->riesgo_temperatura_max,
                'riesgo_medio' => $request->riesgo_medio,
                'riesgo_mediciones' => $request->riesgo_mediciones,
            ]
        );

        // Redirigir a la lista de enfermedades con un mensaje de éxito
        return redirect()->route('enfermedades.cultivos.index', ['enfermedad' => $enfermedad->id])->with('success', 'Tipo de Cultivo asociado a la enfermedad actualizado correctamente.');
    }

    // 6) Desvincular
    public function cultivosDestroy(Enfermedades $enfermedad, TipoCultivos $tipoCultivo)
    {
        // Eliminar la enfermedad
        $registro = TipoCultivosEnfermedad::where('enfermedad_id', $enfermedad->id)->where('tipo_cultivo_id', $tipoCultivo->id)->firstOrFail();

        // Eliminar el registro
        $registro->delete();

        // Redirigir a la lista de enfermedades con un mensaje de éxito
        return redirect()->route('enfermedades.cultivos.index', ['enfermedad' => $enfermedad->id])->with('success', 'Tipo de Cultivo asociado a la enfermedad eliminado correctamente.');
    }

    public function jsonEnfermedades(Request $request)
    {

        $enfermedad_id = $request->get('enfermedad_id');
        $tipo_cultivo_id = $request->get('tipo_cultivo_id');
        $zona_manejo_id = $request->get('zona_manejo_id');
        $periodo = $request->get('periodo');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');


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
                        'estatus' => $hora['estatus']
                    ];
                })
            ]);
        }

        $resultado = $resultado->sortByDesc(function ($item) {
            return $item['fecha'];
        })->values();

        return response()->json([
            'resultado' => $resultado,
            'fechasReales' => $fechasReales,
        ]);
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
}
