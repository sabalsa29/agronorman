<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ZonaManejos;
use App\Models\UnidadesFrio;
use App\Models\ResumenTemperaturas;
use App\Models\TipoCultivos;
use Carbon\Carbon;

class UnidadesController extends Controller
{
    public function unidadeschart(Request $request)
    {
        $fechas = $this->calcularPeriodoFechas(
            $request->input('rango_fechas'),
            $request->input('startDate'),
            $request->input('endDate')
        );

        // Obtenemos la zona de manejo
        $estacion = ZonaManejos::find($request->input('zonas_manejo')[0]);

        // 1. Hacemos la consulta a la tabla de indicadores calculados
        if ($request->filled('startDate') && $request->filled('endDate')) {
            $unidadesFrio = DB::table('unidades_frio')
                ->selectRaw('SUM(unidades) as unidades')
                ->where('fecha', '>=', $request->input('startDate') . ' 00:00:00')
                ->where('fecha', '<=', $request->input('endDate') . ' 23:59:59')
                ->where('zona_manejo_id', $request->input('zonas_manejo')[0])
                ->first();
        } else {
            $unidadesFrio = DB::table('unidades_frio')
                ->selectRaw('SUM(unidades) as unidades')
                ->where('fecha', '>=', $fechas[1] . ' 00:00:00')
                ->where('zona_manejo_id', $request->input('zonas_manejo')[0])
                ->first();
        }

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $unidadesCalor = DB::table('resumen_temperaturas')
                ->selectRaw('SUM(uc) as unidades')
                ->where('uc', '>', 0)
                ->where('fecha', '>=', $request->input('startDate'))
                ->where('fecha', '<=', $request->input('endDate') . ' 23:59:59')
                ->where('zona_manejo_id', $estacion->id)
                ->first();
        } else {
            $unidadesCalor = DB::table('resumen_temperaturas')
                ->selectRaw('SUM(uc) as unidades')
                ->where('uc', '>', 0)
                ->where('fecha', '>=', $fechas[1])
                ->where('zona_manejo_id', $estacion->id)
                ->first();
        }

        // Obtenemos la amplitud térmica
        if ($request->filled('startDate') && $request->filled('endDate')) {
            $resumen = DB::table('resumen_temperaturas')
                ->selectRaw('MAX(max) as temp_max, MIN(min) as temp_min, MAX(max)-MIN(min) as amplitud, SUM(uc) as uc, SUM(uf) as uf')
                ->where('zona_manejo_id', $estacion->id)
                ->where('fecha', '>=', $request->input('startDate'))
                ->where('fecha', '<=', $request->input('endDate'))
                ->first();

            $desglose = DB::table('resumen_temperaturas')
                ->where('zona_manejo_id', $estacion->id)
                ->where('fecha', '>=', $request->input('startDate'))
                ->where('fecha', '<=', $request->input('endDate'))
                ->get();
        } else {
            $resumen = DB::table('resumen_temperaturas')
                ->selectRaw('MAX(max) as temp_max, MIN(min) as temp_min, MAX(max)-MIN(min) as amplitud, SUM(uc) as uc, SUM(uf) as uf')
                ->where('zona_manejo_id', $estacion->id)
                ->where('fecha', '>=', $fechas[1])
                ->first();

            $desglose = DB::table('resumen_temperaturas')
                ->where('zona_manejo_id', $estacion->id)
                ->where('fecha', '>=', $fechas[1])
                ->get();
        }

        $tipoCultivo = TipoCultivos::find($estacion->tipo_cultivo_id);

        return view('charts.unidades', [
            'unidadesFrio' => is_null($resumen->uf) ? 0 : $resumen->uf,
            'unidadesCalor' => is_null($resumen->uc) ? 0 : $resumen->uc,
            'desglose' => $desglose,
            'resumen' => $resumen
        ]);
    }

    /**
     * Calcula el período de fechas basado en el rango seleccionado
     */
    private function calcularPeriodoFechas($rangoFechas, $startDate, $endDate)
    {
        // Implementar la lógica de cálculo de período de fechas
        // Esta función debe ser adaptada según la lógica original
        $fechaActual = Carbon::now();

        switch ($rangoFechas) {
            case 'hoy':
                return [$fechaActual->format('Y-m-d'), $fechaActual->format('Y-m-d')];
            case 'ayer':
                return [$fechaActual->subDay()->format('Y-m-d'), $fechaActual->subDay()->format('Y-m-d')];
            case 'semana':
                return [$fechaActual->startOfWeek()->format('Y-m-d'), $fechaActual->endOfWeek()->format('Y-m-d')];
            case 'mes':
                return [$fechaActual->startOfMonth()->format('Y-m-d'), $fechaActual->endOfMonth()->format('Y-m-d')];
            default:
                return [$startDate, $endDate];
        }
    }
}
