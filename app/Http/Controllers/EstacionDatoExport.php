<?php

namespace App\Http\Controllers;

use App\Exports\MedicionesExportAllQuery;
use App\Models\DatosViento;
use App\Models\MedicionesExport;
use App\Models\EstacionDato;
use App\Models\MedicionesExportAll;
use App\Models\PrecipitacionPluvial;
use App\Models\PresionAtmosferica;
use App\Models\TipoCultivos;
use App\Models\ZonaManejos;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EstacionDatoExport extends Controller
{
    public function export(Request $request)
    {
        $fechas = $this->calcularPeriodo($request->periodo, $request->start_date, $request->end_date);
        $inicio = $request->input('start_date') ?? $fechas[1];
        $fin = $request->input('end_date') ?? $fechas[0];
        $zona_manejo_id = $request->input('zona_manejo_id');

        // Primero obtenemos el modelo ZonaManejos
        $zona_manejo = ZonaManejos::find($zona_manejo_id);
        if (!$zona_manejo) {
            return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
        }

        // Ahora sí podemos acceder a las estaciones
        $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();
        $id_tipo_cultivo = $zona_manejo->tipoCultivos->pluck('id')->map(fn($id) => (int) $id)->toArray();

        $select = '';
        switch ($fechas[2]) {
            case 'd':
                $tipo = 'Día';
                $select = 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ';
                break;
            case 's':
                $tipo = 'Semana';
                $select = 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ';
                break;
            case 'm':
                $tipo = 'Mes';
                $select = 'DATE_FORMAT(estacion_dato.created_at, "%d-%m-%Y") as fecha, ';
                break;
            case '4_horas':
                $tipo = 'Cada 4 horas';
                $select = "CONCAT(DATE_FORMAT(estacion_dato.created_at, '%Y-%m-%d '),
                    LPAD(FLOOR(HOUR(estacion_dato.created_at)/4)*4, 2, '0'), ':00:00') as fecha, ";
                break;
            case '8_horas':
                $tipo = 'Cada 8 horas';
                $select = "CONCAT(DATE_FORMAT(estacion_dato.created_at, '%Y-%m-%d '),
                    LPAD(FLOOR(HOUR(estacion_dato.created_at)/8)*8, 2, '0'), ':00:00') as fecha, ";
                break;
            case '12_horas':
                $tipo = 'Cada 12 horas';
                $select = "CONCAT(DATE_FORMAT(estacion_dato.created_at, '%Y-%m-%d '),
                    LPAD(FLOOR(HOUR(estacion_dato.created_at)/12)*12, 2, '0'), ':00:00') as fecha, ";
                break;
            case 'crudos':
                $tipo = 'Crudos';
                $select = 'estacion_dato.created_at as fecha, ';
                break;
            default:
                break;
        }

        $rows = EstacionDato::whereIn('estacion_id', $ids)
            ->whereBetween('created_at', [$fechas[1], $fechas[0]])
            ->selectRaw($select . '
                MAX(temperatura) as max_temperatura,
                MIN(temperatura) as min_temperatura,
                AVG(temperatura) as avg_temperatura,            
                MAX(co2) as max_co2,
                MIN(co2) as min_co2,
                AVG(co2) as avg_co2,            
                MAX(temperatura_suelo) as max_temperatura_suelo,
                MIN(temperatura_suelo) as min_temperatura_suelo,
                AVG(temperatura_suelo) as avg_temperatura_suelo,
                MAX(conductividad_electrica) as max_conductividad_electrica,
                MIN(conductividad_electrica) as min_conductividad_electrica,
                AVG(conductividad_electrica) as avg_conductividad_electrica,
                MAX(ph) as max_ph,
                MIN(ph) as min_ph,
                AVG(ph) as avg_ph,
                MAX(nit) as max_nit,
                MIN(nit) as min_nit,
                AVG(nit) as avg_nit,
                MAX(phos) as max_phos,
                MIN(phos) as min_phos,
                AVG(phos) as avg_phos,
                MAX(pot) as max_pot,
                MIN(pot) as min_pot,
                AVG(pot) as avg_pot,
                MAX(humedad_relativa) as max_humedad_relativa,
                MIN(humedad_relativa) as min_humedad_relativa,
                AVG(humedad_relativa) as avg_humedad_relativa,
                MAX(humedad_15) as max_humedad_15,
                MIN(humedad_15) as min_humedad_15,
                AVG(humedad_15) as avg_humedad_15,                
                NULL as max_precipitacion_mm,
                NULL as min_precipitacion_mm,
                NULL as avg_precipitacion_mm,                
                NULL as max_wind_speed,
                NULL as min_wind_speed,
                NULL as avg_wind_speed,
                NULL as max_pressure,
                NULL as min_pressure,
                NULL as avg_pressure,
                DATE(created_at) as fecha_real                                                                                          
            ')
            ->groupBy('fecha', 'fecha_real')
            ->orderBy('fecha_real', 'DESC')
            ->get()
            ->toArray();

        // Obtener datos de precipitación pluvial
        $precipitacionData = PrecipitacionPluvial::where('zona_manejo_id', $zona_manejo_id)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
            ->selectRaw('
                MAX(precipitacion_mm) as max_precipitacion_mm,
                MIN(precipitacion_mm) as min_precipitacion_mm,
                AVG(precipitacion_mm) as avg_precipitacion_mm,
                DATE(fecha_hora_dato) as fecha_real
            ')
            ->groupBy('fecha_real')
            ->get()
            ->keyBy('fecha_real')
            ->toArray();

        $vientoData = DatosViento::where('zona_manejo_id', $zona_manejo_id)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
            ->selectRaw('
                MAX(wind_speed) as max_wind_speed,
                MIN(wind_speed) as min_wind_speed,
                AVG(wind_speed) as avg_wind_speed,
                DATE(fecha_hora_dato) as fecha_real
            ')
            ->groupBy('fecha_real')
            ->get()
            ->keyBy('fecha_real')
            ->toArray();

        $presionAtmosfericaData = PresionAtmosferica::where('zona_manejo_id', $zona_manejo_id)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechas[1], $fechas[0]])
            ->selectRaw('
                MAX(pressure) as max_pressure,
                MIN(pressure) as min_pressure,
                AVG(pressure) as avg_pressure,
                DATE(fecha_hora_dato) as fecha_real
            ')
            ->groupBy('fecha_real')
            ->get()
            ->keyBy('fecha_real')
            ->toArray();

        // Combinar datos de precipitación con datos de estación
        foreach ($rows as &$row) {
            $fecha = $row['fecha_real'];
            if (isset($precipitacionData[$fecha])) {
                $row['max_precipitacion_mm'] = $precipitacionData[$fecha]['max_precipitacion_mm'];
                $row['min_precipitacion_mm'] = $precipitacionData[$fecha]['min_precipitacion_mm'];
                $row['avg_precipitacion_mm'] = $precipitacionData[$fecha]['avg_precipitacion_mm'];
            }
            if (isset($vientoData[$fecha])) {
                $row['max_wind_speed'] = $vientoData[$fecha]['max_wind_speed'];
                $row['min_wind_speed'] = $vientoData[$fecha]['min_wind_speed'];
                $row['avg_wind_speed'] = $vientoData[$fecha]['avg_wind_speed'];
            }
            if (isset($presionAtmosfericaData[$fecha])) {
                $row['max_pressure'] = $presionAtmosfericaData[$fecha]['max_pressure'];
                $row['min_pressure'] = $presionAtmosfericaData[$fecha]['min_pressure'];
                $row['avg_pressure'] = $presionAtmosfericaData[$fecha]['avg_pressure'];
            }
        }

        // Por ahora datos falsos, luego conectamos modelo real
        $zona_manejo = ZonaManejos::find($zona_manejo_id);
        $tipo_cultivo = TipoCultivos::find($id_tipo_cultivo);
        $parcela = $zona_manejo->parcela;
        $cliente = $zona_manejo->parcela->cliente;

        return Excel::download(new MedicionesExport($rows, $inicio, $fin, $zona_manejo, $tipo_cultivo, $parcela, $cliente), 'mediciones.xlsx');
    }

    public function exportAll(Request $request)
    {
        try {
            $zona_manejo_id = $request->input('zona_manejo_id');

            // Validar zona de manejo
            $zona_manejo = ZonaManejos::find($zona_manejo_id);
            if (!$zona_manejo) {
                return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
            }

            // Accede a las estaciones
            $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

            if (empty($ids)) {
                return response()->json(['error' => 'No hay estaciones asociadas a esta zona de manejo'], 404);
            }

            // Verificar si hay datos en las estaciones (sin límite de fecha)
            $count = EstacionDato::whereIn('estacion_id', $ids)->count();

            if ($count === 0) {
                return response()->json([
                    'error' => 'No hay datos disponibles para las estaciones de esta zona de manejo.',
                    'estaciones' => $ids
                ], 404);
            }

            // Generar nombre de archivo
            $filename = sprintf(
                'todas_mediciones_zona_%d_%s.xlsx',
                $zona_manejo_id,
                now()->format('Y-m-d_H-i-s')
            );

            // Log de inicio de exportación
            Log::info('Iniciando exportación completa', [
                'zona_manejo_id' => $zona_manejo_id,
                'total_records' => $count,
                'estaciones' => $ids,
                'sin_limite_fecha' => true
            ]);

            // Exportar TODOS los datos sin filtros de fecha
            return Excel::download(
                new MedicionesExportAllQuery($ids), // Sin parámetros de fecha
                $filename
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error en exportAll: ' . $e->getMessage(), [
                'zona_manejo_id' => $zona_manejo_id ?? 'no definido',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor durante la exportación.',
                'message' => 'Por favor, contacta al administrador si el problema persiste.'
            ], 500);
        }
    }

    public function exportAllOptimized(Request $request)
    {
        try {
            $zona_manejo_id = $request->input('zona_manejo_id');

            // Validar zona de manejo
            $zona_manejo = ZonaManejos::find($zona_manejo_id);
            if (!$zona_manejo) {
                return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
            }

            // Accede a las estaciones
            $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

            if (empty($ids)) {
                return response()->json(['error' => 'No hay estaciones asociadas a esta zona de manejo'], 404);
            }

            // Verificar si hay datos en las estaciones (sin límite de fecha)
            $count = EstacionDato::whereIn('estacion_id', $ids)->count();

            if ($count === 0) {
                return response()->json([
                    'error' => 'No hay datos disponibles para las estaciones de esta zona de manejo.',
                    'estaciones' => $ids
                ], 404);
            }

            // Si hay demasiados registros, usar enfoque por lotes
            if ($count > 50000) {
                return response()->json([
                    'error' => 'Demasiados registros para exportar directamente (' . number_format($count) . ').',
                    'suggestion' => 'Por favor, usa la exportación por períodos o contacta al administrador para una exportación especial.',
                    'count' => $count
                ], 400);
            }

            // Generar nombre de archivo
            $filename = sprintf(
                'todas_mediciones_zona_%d_%s.xlsx',
                $zona_manejo_id,
                now()->format('Y-m-d_H-i-s')
            );

            // Log de inicio de exportación
            Log::info('Iniciando exportación optimizada', [
                'zona_manejo_id' => $zona_manejo_id,
                'total_records' => $count,
                'estaciones' => $ids,
                'sin_limite_fecha' => true
            ]);

            // Configurar límites de memoria para esta operación
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 300); // 5 minutos

            // Exportar usando el enfoque optimizado
            return Excel::download(
                new MedicionesExportAllQuery($ids),
                $filename
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error en exportAllOptimized: ' . $e->getMessage(), [
                'zona_manejo_id' => $zona_manejo_id ?? 'no definido',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor durante la exportación.',
                'message' => 'Por favor, contacta al administrador si el problema persiste.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function exportAllCSV(Request $request)
    {
        try {
            $zona_manejo_id = $request->input('zona_manejo_id');

            // Validar zona de manejo
            $zona_manejo = ZonaManejos::find($zona_manejo_id);
            if (!$zona_manejo) {
                return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
            }

            // Accede a las estaciones
            $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

            if (empty($ids)) {
                return response()->json(['error' => 'No hay estaciones asociadas a esta zona de manejo'], 404);
            }

            // Verificar si hay datos en las estaciones (sin límite de fecha)
            $count = EstacionDato::whereIn('estacion_id', $ids)->count();

            if ($count === 0) {
                return response()->json([
                    'error' => 'No hay datos disponibles para las estaciones de esta zona de manejo.',
                    'estaciones' => $ids
                ], 404);
            }

            // Generar nombre de archivo
            $filename = sprintf(
                'todas_mediciones_zona_%d_%s.csv',
                $zona_manejo_id,
                now()->format('Y-m-d_H-i-s')
            );

            // Log de inicio de exportación
            Log::info('Iniciando exportación CSV', [
                'zona_manejo_id' => $zona_manejo_id,
                'total_records' => $count,
                'estaciones' => $ids,
                'sin_limite_fecha' => true
            ]);

            // Configurar headers para descarga de CSV
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            // Crear callback para streaming de datos
            $callback = function () use ($ids, $zona_manejo_id) {
                $file = fopen('php://output', 'w');

                // BOM para UTF-8
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Headers
                fputcsv($file, [
                    'Fecha (Hora)',
                    'Temperatura Máxima (°C)',
                    'Temperatura Mínima (°C)',
                    'Temperatura Promedio (°C)',
                    'CO2 Máximo (ppm)',
                    'CO2 Mínimo (ppm)',
                    'CO2 Promedio (ppm)',
                    'Temperatura Suelo Máxima (°C)',
                    'Temperatura Suelo Mínima (°C)',
                    'Temperatura Suelo Promedio (°C)',
                    'Conductividad Eléctrica Máxima (Ds/m)',
                    'Conductividad Eléctrica Mínima (Ds/m)',
                    'Conductividad Eléctrica Promedio (Ds/m)',
                    'pH Máximo',
                    'pH Mínimo',
                    'pH Promedio',
                    'Nitrógeno Máximo (ppm)',
                    'Nitrógeno Mínimo (ppm)',
                    'Nitrógeno Promedio (ppm)',
                    'Fósforo Máximo (ppm)',
                    'Fósforo Mínimo (ppm)',
                    'Fósforo Promedio (ppm)',
                    'Potasio Máximo (ppm)',
                    'Potasio Mínimo (ppm)',
                    'Potasio Promedio (ppm)',
                    'Humedad Relativa Máxima (%)',
                    'Humedad Relativa Mínima (%)',
                    'Humedad Relativa Promedio (%)',
                    'Precipitación Máxima (mm)',
                    'Precipitación Mínima (mm)',
                    'Precipitación Promedio (mm)',
                ]);

                // Obtener datos de precipitación pluvial (sin límite de fecha para exportAllCSV)
                $precipitacionData = PrecipitacionPluvial::where('zona_manejo_id', $zona_manejo_id)
                    ->where('tipo_dato', 'historico')
                    ->selectRaw('
                        MAX(precipitacion_mm) as max_precipitacion_mm,
                        MIN(precipitacion_mm) as min_precipitacion_mm,
                        ROUND(AVG(precipitacion_mm), 2) as avg_precipitacion_mm,
                        DATE_FORMAT(fecha_hora_dato, "%Y-%m-%d %H:00:00") as fecha
                    ')
                    ->groupBy('fecha')
                    ->get()
                    ->keyBy('fecha')
                    ->toArray();

                // Procesar datos en chunks para ahorrar memoria
                EstacionDato::whereIn('estacion_id', $ids)
                    ->selectRaw('
                        DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as fecha,
                        MAX(temperatura) as max_temperatura,
                        MIN(temperatura) as min_temperatura,
                        ROUND(AVG(temperatura), 2) as avg_temperatura,
                        MAX(co2) as max_co2,
                        MIN(co2) as min_co2,
                        ROUND(AVG(co2), 2) as avg_co2,
                        MAX(temperatura_suelo) as max_temperatura_suelo,
                        MIN(temperatura_suelo) as min_temperatura_suelo,
                        ROUND(AVG(temperatura_suelo), 2) as avg_temperatura_suelo,
                        MAX(conductividad_electrica) as max_conductividad_electrica,
                        MIN(conductividad_electrica) as min_conductividad_electrica,
                        ROUND(AVG(conductividad_electrica), 2) as avg_conductividad_electrica,
                        MAX(ph) as max_ph,
                        MIN(ph) as min_ph,
                        ROUND(AVG(ph), 2) as avg_ph,
                        MAX(nit) as max_nit,
                        MIN(nit) as min_nit,
                        ROUND(AVG(nit), 2) as avg_nit,
                        MAX(phos) as max_phos,
                        MIN(phos) as min_phos,
                        ROUND(AVG(phos), 2) as avg_phos,
                        MAX(pot) as max_pot,
                        MIN(pot) as min_pot,
                        ROUND(AVG(pot), 2) as avg_pot,
                        MAX(humedad_relativa) as max_humedad_relativa,
                        MIN(humedad_relativa) as min_humedad_relativa,
                        ROUND(AVG(humedad_relativa), 2) as avg_humedad_relativa
                    ')
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->chunk(1000, function ($records) use ($file, $precipitacionData) {
                        foreach ($records as $record) {
                            // Obtener datos de precipitación para esta fecha
                            $precipitacion = $precipitacionData[$record->fecha] ?? null;

                            fputcsv($file, [
                                $record->fecha,
                                $record->max_temperatura ?? 'N/A',
                                $record->min_temperatura ?? 'N/A',
                                $record->avg_temperatura ?? 'N/A',
                                $record->max_co2 ?? 'N/A',
                                $record->min_co2 ?? 'N/A',
                                $record->avg_co2 ?? 'N/A',
                                $record->max_temperatura_suelo ?? 'N/A',
                                $record->min_temperatura_suelo ?? 'N/A',
                                $record->avg_temperatura_suelo ?? 'N/A',
                                $record->max_conductividad_electrica ?? 'N/A',
                                $record->min_conductividad_electrica ?? 'N/A',
                                $record->avg_conductividad_electrica ?? 'N/A',
                                $record->max_ph ?? 'N/A',
                                $record->min_ph ?? 'N/A',
                                $record->avg_ph ?? 'N/A',
                                $record->max_nit ?? 'N/A',
                                $record->min_nit ?? 'N/A',
                                $record->avg_nit ?? 'N/A',
                                $record->max_phos ?? 'N/A',
                                $record->min_phos ?? 'N/A',
                                $record->avg_phos ?? 'N/A',
                                $record->max_pot ?? 'N/A',
                                $record->min_pot ?? 'N/A',
                                $record->avg_pot ?? 'N/A',
                                $record->max_humedad_relativa ?? 'N/A',
                                $record->min_humedad_relativa ?? 'N/A',
                                $record->avg_humedad_relativa ?? 'N/A',
                                $precipitacion['max_precipitacion_mm'] ?? 'N/A',
                                $precipitacion['min_precipitacion_mm'] ?? 'N/A',
                                $precipitacion['avg_precipitacion_mm'] ?? 'N/A',
                            ]);
                        }
                    });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error en exportAllCSV: ' . $e->getMessage(), [
                'zona_manejo_id' => $zona_manejo_id ?? 'no definido',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor durante la exportación CSV.',
                'message' => 'Por favor, contacta al administrador si el problema persiste.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar el progreso de la exportación
     */
    public function checkExportProgress(Request $request)
    {
        try {
            $zona_manejo_id = $request->input('zona_manejo_id');

            // Validar zona de manejo
            $zona_manejo = ZonaManejos::find($zona_manejo_id);
            if (!$zona_manejo) {
                return response()->json(['error' => 'Zona de manejo no encontrada'], 404);
            }

            // Accede a las estaciones
            $ids = $zona_manejo->estaciones->pluck('id')->map(fn($id) => (int) $id)->toArray();

            if (empty($ids)) {
                return response()->json(['error' => 'No hay estaciones asociadas a esta zona de manejo'], 404);
            }

            // Contar registros totales (sin filtros de fecha)
            $total_records = EstacionDato::whereIn('estacion_id', $ids)->count();

            if ($total_records === 0) {
                return response()->json([
                    'progress' => 0,
                    'total_records' => 0,
                    'processed_records' => 0,
                    'status' => 'no_data',
                    'estimated_time' => 'Sin datos'
                ]);
            }

            // Calcular progreso basado en el tiempo transcurrido y volumen de datos
            $session_key = "export_progress_{$zona_manejo_id}";
            $start_time = session($session_key . '_start', time());
            $current_time = time();
            $elapsed_seconds = $current_time - $start_time;

            // Estimación más realista basada en el volumen de datos
            // Para CSV: ~500 registros por segundo
            // Para Excel: ~100 registros por segundo
            $records_per_second = 500; // CSV es más rápido
            $estimated_total_seconds = $total_records / $records_per_second;

            // Progreso más conservador y realista
            $progress = min(85, ($elapsed_seconds / $estimated_total_seconds) * 100);

            // Si no hay tiempo de inicio en sesión, iniciarlo
            if (!session($session_key . '_start')) {
                session([$session_key . '_start' => $current_time]);
                $progress = 10; // Inicio más realista
            }

            // Ajustar progreso para que no llegue al 100% hasta que realmente termine
            if ($progress > 85) {
                $progress = 85; // Mantener en 85% hasta que realmente termine
            }

            $processed_records = round(($progress / 100) * $total_records);

            return response()->json([
                'progress' => round($progress, 2),
                'total_records' => $total_records,
                'processed_records' => $processed_records,
                'status' => $progress >= 85 ? 'finalizing' : 'processing',
                'estimated_time' => $this->estimateRemainingTime($progress, $total_records),
                'elapsed_seconds' => $elapsed_seconds,
                'estimated_total_seconds' => $estimated_total_seconds
            ]);
        } catch (\Exception $e) {
            Log::error('Error en checkExportProgress: ' . $e->getMessage());
            return response()->json(['error' => 'Error al verificar progreso'], 500);
        }
    }

    /**
     * Estimar tiempo restante basado en el progreso
     */
    private function estimateRemainingTime($progress, $total_records)
    {
        if ($progress >= 100) {
            return 'Completado';
        }

        if ($progress <= 0) {
            return 'Calculando...';
        }

        // Estimación más precisa basada en el progreso actual
        $records_per_second = 500; // CSV es más rápido
        $remaining_records = $total_records - ($total_records * $progress / 100);
        $remaining_seconds = $remaining_records / $records_per_second;

        // Ajustar estimación si el progreso es muy bajo
        if ($progress < 10) {
            $remaining_seconds = $remaining_seconds * 1.5; // Más conservador al inicio
        }

        // Ajustar estimación si está cerca del final
        if ($progress > 80) {
            $remaining_seconds = $remaining_seconds * 0.8; // Más optimista al final
        }

        if ($remaining_seconds < 30) {
            return 'Menos de 1 minuto';
        } elseif ($remaining_seconds < 60) {
            return round($remaining_seconds) . ' segundos';
        } elseif ($remaining_seconds < 3600) {
            $minutes = round($remaining_seconds / 60);
            return $minutes . ' minuto' . ($minutes > 1 ? 's' : '');
        } else {
            $hours = round($remaining_seconds / 3600, 1);
            return $hours . ' hora' . ($hours > 1 ? 's' : '');
        }
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
}
