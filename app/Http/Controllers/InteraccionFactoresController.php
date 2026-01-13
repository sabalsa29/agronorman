<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ZonaManejos;
use App\Models\VariablesMedicion;
use App\Models\EstacionDato;

class InteraccionFactoresController extends Controller
{
    public function analizarInteraccion(Request $request)
    {
        try {
            // Validar los datos de entrada
            $request->validate([
                'variables' => 'required|array|min:1',
                'variables.*' => 'exists:variables_medicion,slug',
                'agrupacion' => 'required|array|min:1',
                'agrupacion.*' => 'string',
                'zona_manejo_id' => 'required|exists:zona_manejos,id',
                'periodo' => 'required|integer',
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date'
            ]);

            $variables = $request->variables;
            $agrupaciones = $request->agrupacion;
            $zonaManejoId = $request->zona_manejo_id;
            $periodo = $request->periodo;
            $startDate = $request->startDate;
            $endDate = $request->endDate;

            // Obtener la zona de manejo
            $zonaManejo = ZonaManejos::findOrFail($zonaManejoId);
            $estacionIds = $zonaManejo->estaciones->pluck('id')->toArray();

            if (empty($estacionIds)) {
                return response()->json([
                    'error' => 'No hay estaciones asociadas a esta zona de manejo'
                ], 400);
            }

            // Calcular fechas según el periodo
            $fechas = $this->calcularPeriodo($periodo, $startDate, $endDate);
            $fechaInicio = $fechas[1];
            $fechaFin = $fechas[0];

            // Obtener datos de las variables seleccionadas
            $datos = $this->obtenerDatosVariables($estacionIds, $variables, $agrupaciones, $fechaInicio, $fechaFin, $fechas[2]);

            // Calcular correlaciones
            $correlaciones = $this->calcularCorrelaciones($datos);

            // Generar matriz de interacción
            $matrizInteraccion = $this->generarMatrizInteraccion($datos, $correlaciones);

            return response()->json([
                'success' => true,
                'data' => [
                    'variables' => $this->obtenerInfoVariables($variables),
                    'agrupaciones' => $agrupaciones,
                    'periodo' => [
                        'inicio' => $fechaInicio,
                        'fin' => $fechaFin,
                        'tipo' => $this->getTipoPeriodo($fechas[2])
                    ],
                    'datos' => $datos,
                    'correlaciones' => $correlaciones,
                    'matriz_interaccion' => $matrizInteraccion
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al analizar interacción de factores',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function calcularPeriodo($periodo, $startDate = null, $endDate = null)
    {
        $now = Carbon::now('America/Mexico_City');

        if ($periodo == 9 && $startDate && $endDate) {
            return [$endDate, $startDate, 'd'];
        }

        // Calcular fechas según el periodo
        switch ($periodo) {
            case 1: // Últimas 24 horas
                $inicio = $now->copy()->subDay()->format('Y-m-d H:i:s');
                $fin = $now->format('Y-m-d H:i:s');
                return [$fin, $inicio, '4_horas'];
            case 2: // Últimas 48 horas
                $inicio = $now->copy()->subDays(2)->format('Y-m-d H:i:s');
                $fin = $now->format('Y-m-d H:i:s');
                return [$fin, $inicio, '8_horas'];
            case 3: // Última semana
                $inicio = $now->copy()->subWeek()->format('Y-m-d H:i:s');
                $fin = $now->format('Y-m-d H:i:s');
                return [$fin, $inicio, 'd'];
            case 4: // Últimas 2 semanas
                $inicio = $now->copy()->subWeeks(2)->format('Y-m-d H:i:s');
                $fin = $now->format('Y-m-d H:i:s');
                return [$fin, $inicio, 'd'];
            case 5: // Último mes
                $inicio = $now->copy()->subMonth()->format('Y-m-d H:i:s');
                $fin = $now->format('Y-m-d H:i:s');
                return [$fin, $inicio, 'd'];
            default:
                $inicio = $now->copy()->subDay()->format('Y-m-d H:i:s');
                $fin = $now->format('Y-m-d H:i:s');
                return [$fin, $inicio, '4_horas'];
        }
    }

    private function getTipoPeriodo($tipo)
    {
        switch ($tipo) {
            case 'd':
                return 'Día';
            case 's':
                return 'Semana';
            case 'm':
                return 'Mes';
            case '4_horas':
                return 'Cada 4 horas';
            case '8_horas':
                return 'Cada 8 horas';
            case '12_horas':
                return 'Cada 12 horas';
            case 'crudos':
                return 'Crudos';
            default:
                return 'Día';
        }
    }

    private function obtenerDatosVariables($estacionIds, $variables, $agrupaciones, $fechaInicio, $fechaFin, $tipoAgrupacion)
    {
        $datos = [];
        $variablesInfo = VariablesMedicion::whereIn('slug', $variables)->get();

        // Construir el SELECT dinámicamente
        $select = $this->construirSelect($tipoAgrupacion);

        foreach ($variablesInfo as $variable) {
            $slug = $variable->slug;

            // Agregar campos para cada variable
            foreach ($agrupaciones as $agrupacion) {
                $tipo = explode('|', $agrupacion)[0]; // max, min, avg
                $select .= ", {$tipo}({$slug}) as {$tipo}_{$slug}";
            }
        }

        // Ejecutar la consulta
        $rows = EstacionDato::whereIn('estacion_id', $estacionIds)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->selectRaw($select)
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->toArray();

        // Procesar los resultados
        foreach ($variablesInfo as $variable) {
            $slug = $variable->slug;
            $datos[$variable->nombre] = [];

            foreach ($agrupaciones as $agrupacion) {
                $tipo = explode('|', $agrupacion)[0];
                $columna = "{$tipo}_{$slug}";

                // Calcular el promedio de todos los valores para esta variable y agrupación
                $valores = array_column($rows, $columna);
                $valores = array_filter($valores, function ($v) {
                    return $v !== null;
                }); // Filtrar valores null

                if (!empty($valores)) {
                    $datos[$variable->nombre][$agrupacion] = round(array_sum($valores) / count($valores), 2);
                } else {
                    $datos[$variable->nombre][$agrupacion] = null;
                }
            }
        }

        return $datos;
    }

    private function construirSelect($tipoAgrupacion)
    {
        switch ($tipoAgrupacion) {
            case 'd':
                return 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha';
            case 's':
                return 'DATE_FORMAT(estacion_dato.created_at, "%V") as fecha';
            case 'm':
                return 'DATE_FORMAT(estacion_dato.created_at, "%m-%Y") as fecha';
            case '4_horas':
                return "CASE
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 0 AND 3  THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 04:00')
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 4 AND 7  THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 08:00')
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 8 AND 11 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 12:00')
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 12 AND 15 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 16:00')
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 16 AND 19 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 20:00')
                    ELSE CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 00:00') END as fecha";
            case '8_horas':
                return "CASE
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 0 AND 7  THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 08:00')
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 8 AND 15 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 16:00')
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 16 AND 23 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 00:00')
                    END as fecha";
            case '12_horas':
                return "CASE
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 0 AND 11 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 12:00')
                    WHEN HOUR(estacion_dato.created_at) BETWEEN 12 AND 23 THEN CONCAT(DATE_FORMAT(estacion_dato.created_at, '%d-%m-%Y'), ' 00:00')
                    END as fecha";
            case 'crudos':
                return 'estacion_dato.created_at as fecha';
            default:
                return 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha';
        }
    }

    private function calcularCorrelaciones($datos)
    {
        $correlaciones = [];
        $variables = array_keys($datos);

        // Si solo hay una variable, no hay correlaciones que calcular
        if (count($variables) < 2) {
            return $correlaciones;
        }

        for ($i = 0; $i < count($variables); $i++) {
            for ($j = $i + 1; $j < count($variables); $j++) {
                $var1 = $variables[$i];
                $var2 = $variables[$j];

                // Calcular correlación simple basada en los valores promedio
                $valores1 = array_values(array_filter($datos[$var1], function ($v) {
                    return $v !== null;
                }));
                $valores2 = array_values(array_filter($datos[$var2], function ($v) {
                    return $v !== null;
                }));

                if (count($valores1) > 0 && count($valores2) > 0) {
                    $correlacion = $this->calcularCoeficienteCorrelacion($valores1, $valores2);

                    $correlaciones[] = [
                        'variable1' => $var1,
                        'variable2' => $var2,
                        'correlacion' => round($correlacion, 3),
                        'tipo' => $this->interpretarCorrelacion($correlacion)
                    ];
                }
            }
        }

        return $correlaciones;
    }

    private function calcularCoeficienteCorrelacion($x, $y)
    {
        $n = count($x);
        if ($n != count($y) || $n == 0) return 0;

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $numerador = ($n * $sumXY) - ($sumX * $sumY);
        $denominador = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));

        return $denominador != 0 ? $numerador / $denominador : 0;
    }

    private function interpretarCorrelacion($correlacion)
    {
        $abs = abs($correlacion);
        if ($abs >= 0.8) return 'Muy alta';
        if ($abs >= 0.6) return 'Alta';
        if ($abs >= 0.4) return 'Moderada';
        if ($abs >= 0.2) return 'Baja';
        return 'Muy baja';
    }

    private function generarMatrizInteraccion($datos, $correlaciones)
    {
        $variables = array_keys($datos);
        $matriz = [];

        // Crear matriz de correlaciones
        foreach ($variables as $var1) {
            $matriz[$var1] = [];
            foreach ($variables as $var2) {
                if ($var1 == $var2) {
                    $matriz[$var1][$var2] = 1.0; // Correlación perfecta consigo misma
                } else {
                    // Buscar correlación en el array de correlaciones
                    $correlacion = 0;
                    foreach ($correlaciones as $corr) {
                        if (($corr['variable1'] == $var1 && $corr['variable2'] == $var2) ||
                            ($corr['variable1'] == $var2 && $corr['variable2'] == $var1)
                        ) {
                            $correlacion = $corr['correlacion'];
                            break;
                        }
                    }
                    $matriz[$var1][$var2] = $correlacion;
                }
            }
        }

        return $matriz;
    }

    private function obtenerInfoVariables($variableSlugs)
    {
        return VariablesMedicion::whereIn('slug', $variableSlugs)
            ->select('id', 'nombre', 'slug', 'unidad')
            ->get();
    }
}
