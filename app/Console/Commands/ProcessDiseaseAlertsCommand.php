<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessDiseaseAlertsCommand extends Command
{
    protected $signature = 'diseases:process-alerts 
                            {--start_date= : Fecha de inicio (YYYY-MM-DD HH:mm:ss)}
                            {--end_date= : Fecha de fin (YYYY-MM-DD HH:mm:ss)}
                            {--estaciones= : IDs de estaciones específicas (separadas por coma, ej: 65,66)}
                            {--all-estaciones : Procesar todas las estaciones disponibles}
                            {--dry-run : Ejecutar sin guardar cambios (solo mostrar qué se haría)}';

    protected $description = 'Procesa alertas de enfermedades usando datos de estacion_dato';

    // Cache para optimizar consultas
    private $enfermedades = null;
    private $horasCondicionesCache = [];

    public function handle()
    {
        try {
            $startDate = $this->option('start_date');
            $endDate = $this->option('end_date');
            $estacionesEspecificas = $this->option('estaciones');
            $todasEstaciones = $this->option('all-estaciones');
            $dryRun = $this->option('dry-run');

            $this->info('🚀 Iniciando procesamiento de alertas de enfermedades');
            if ($dryRun) {
                $this->warn('🔍 MODO DRY-RUN: No se guardarán cambios');
            }

            // Determinar fechas: usar las proporcionadas o por defecto última hora
            if ($startDate && $endDate) {
                $fechaInicio = Carbon::parse($startDate);
                $fechaFin = Carbon::parse($endDate);
            } else {
                // Por defecto: última hora
                $fechaInicio = Carbon::now()->subHour();
                $fechaFin = Carbon::now();
            }

            $this->info("📅 Procesando datos desde: {$fechaInicio} hasta: {$fechaFin}");

            // Determinar qué estaciones procesar
            $estacionesAProcesar = $this->determinarEstaciones($estacionesEspecificas, $todasEstaciones);

            if (empty($estacionesAProcesar)) {
                $this->error("❌ No se especificaron estaciones válidas para procesar");
                return 1;
            }

            $this->info("🏭 Estaciones a procesar: " . implode(', ', $estacionesAProcesar));

            // OPTIMIZACIÓN 1: Precargar enfermedades una sola vez
            $this->precargarEnfermedades();

            // OPTIMIZACIÓN 2: Precargar registros de enfermedad_horas_condiciones para todas las estaciones
            $this->precargarHorasCondiciones($estacionesAProcesar);

            // Obtener datos de estacion_dato ordenados por estacion_id y fecha
            $query = DB::table('estacion_dato')
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->whereIn('estacion_id', $estacionesAProcesar)
                ->orderBy('estacion_id')
                ->orderBy('created_at');

            $datosEstacion = $query->get();

            if ($datosEstacion->isEmpty()) {
                $this->warn("⚠️  No hay datos de estacion_dato en el rango especificado para las estaciones seleccionadas");
                return 0;
            }

            $this->info("📊 Encontrados {$datosEstacion->count()} registros de estacion_dato");

            // Agrupar datos por estación para procesar cada una
            $datosPorEstacion = $datosEstacion->groupBy('estacion_id');
            $totalEstaciones = count($datosPorEstacion);
            $this->info("🏭 Procesando {$totalEstaciones} estaciones");

            // Crear barra de progreso
            $progressBar = $this->output->createProgressBar($totalEstaciones);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
            $progressBar->start();

            $totalRegistrosProcesados = 0;
            $estadisticas = [];

            // Procesar cada estación
            foreach ($datosPorEstacion as $estacionId => $datosEstacion) {
                $registrosProcesados = $this->procesarEstacion($estacionId, $datosEstacion, $dryRun);
                $totalRegistrosProcesados += $registrosProcesados;
                $estadisticas[$estacionId] = $registrosProcesados;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("✅ Procesamiento completado");
            $this->info("📈 Total de registros procesados: {$totalRegistrosProcesados}");

            // Mostrar estadísticas por estación
            $this->info("📊 Estadísticas por estación:");
            foreach ($estadisticas as $estacionId => $registros) {
                $this->line("   Estación {$estacionId}: {$registros} registros");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("📍 Archivo: " . $e->getFile() . ":" . $e->getLine());
            return 1;
        }
    }

    /**
     * OPTIMIZACIÓN: Precarga todas las enfermedades una sola vez
     */
    private function precargarEnfermedades()
    {
        $this->info("📋 Precargando enfermedades...");
        $this->enfermedades = DB::select("SELECT ee.* FROM enfermedades e INNER JOIN tipo_cultivos_enfermedades ee ON ee.enfermedad_id=e.id WHERE 1");
        $this->info("✅ Enfermedades precargadas: " . count($this->enfermedades));
    }

    /**
     * OPTIMIZACIÓN: Precarga registros de enfermedad_horas_condiciones para todas las estaciones
     */
    private function precargarHorasCondiciones($estacionesIds)
    {
        $this->info("📋 Precargando registros de horas condiciones...");

        $registros = DB::table('enfermedad_horas_condiciones')
            ->whereIn('estacion_id', $estacionesIds)
            ->get();

        // Organizar por clave compuesta: estacion_id_tipo_cultivo_id_enfermedad_id
        foreach ($registros as $registro) {
            $key = $registro->estacion_id . '_' . $registro->tipo_cultivo_id . '_' . $registro->enfermedad_id;
            $this->horasCondicionesCache[$key] = $registro;
        }

        $this->info("✅ Registros de horas condiciones precargados: " . count($registros));
    }

    /**
     * Determina qué estaciones procesar basado en las opciones
     */
    private function determinarEstaciones($estacionesEspecificas, $todasEstaciones)
    {
        if ($todasEstaciones) {
            // Obtener todas las estaciones que tienen datos
            $estaciones = DB::table('estaciones')
                ->where('status', 1) // Solo estaciones activas
                ->pluck('id')
                ->toArray();

            if (empty($estaciones)) {
                $this->warn("⚠️  No se encontraron estaciones activas");
                return [];
            }

            return $estaciones;
        }

        if ($estacionesEspecificas) {
            $estaciones = array_map('trim', explode(',', $estacionesEspecificas));
            $estaciones = array_filter($estaciones, 'is_numeric');

            if (empty($estaciones)) {
                $this->warn("⚠️  No se proporcionaron IDs de estaciones válidos");
                return [];
            }

            return $estaciones;
        }

        // Por defecto: estaciones 65 y 66 (comportamiento original)
        return [65, 66];
    }

    /**
     * Procesa una estación específica
     */
    private function procesarEstacion($estacionId, $datosEstacion, $dryRun = false)
    {
        $registrosProcesados = 0;

        // OPTIMIZACIÓN: Acumular operaciones en lote
        $insertsHorasCondiciones = [];
        $updatesHorasCondiciones = [];
        $insertsAcumuladas = [];

        // Procesar cada registro de la estación
        foreach ($datosEstacion as $dato) {
            // Construir el array $data como lo hace StationController
            $data = [
                'estacion_id' => $dato->estacion_id,
                'id_origen' => $dato->id_origen ?? null,
                'temperatura' => $dato->temperatura ?? 0,
                'humedad_relativa' => $dato->humedad_relativa ?? 0,
                'co2' => $dato->co2 ?? null,
                'precipitacion_acumulada' => $dato->precipitacion_acumulada ?? 0,
                'humedad_15' => $dato->humedad_15 ?? null,
                'temperatura_suelo' => $dato->temperatura_suelo ?? null,
                'nit' => $dato->nit ?? null,
                'ph' => $dato->ph ?? null,
                'phos' => $dato->phos ?? null,
                'pot' => $dato->pot ?? null,
                'conductividad_electrica' => $dato->conductividad_electrica ?? null,
                'bateria' => $dato->bateria ?? null,
                'direccion_viento' => $dato->direccion_viento ?? null,
                'velocidad_viento' => $dato->velocidad_viento ?? null,
                'created_at' => $dato->created_at,
                'updated_at' => $dato->updated_at,
            ];

            // Llamar a processDiseaseAlerts con los datos (ahora optimizado)
            $this->processDiseaseAlertsOptimizado($data, $dato->estacion_id, $dryRun, $insertsHorasCondiciones, $updatesHorasCondiciones, $insertsAcumuladas);
            $registrosProcesados++;
        }

        // OPTIMIZACIÓN: Ejecutar operaciones en lote
        if (!$dryRun && !empty($insertsHorasCondiciones)) {
            try {
                DB::table('enfermedad_horas_condiciones')->insert($insertsHorasCondiciones);
            } catch (\Exception $e) {
                Log::error("Error en bulk insert horas condiciones: " . $e->getMessage());
            }
        }

        if (!$dryRun && !empty($insertsAcumuladas)) {
            try {
                DB::table('enfermedad_horas_acumuladas_condiciones')->insert($insertsAcumuladas);
            } catch (\Exception $e) {
                Log::error("Error en bulk insert acumuladas: " . $e->getMessage());
            }
        }

        // Ejecutar updates individuales (no se pueden hacer en lote fácilmente)
        if (!$dryRun && !empty($updatesHorasCondiciones)) {
            foreach ($updatesHorasCondiciones as $update) {
                try {
                    DB::update($update['query'], $update['bindings']);
                } catch (\Exception $e) {
                    Log::error("Error en update horas condiciones: " . $e->getMessage());
                }
            }
        }

        return $registrosProcesados;
    }

    /**
     * Procesa las alertas de enfermedades (VERSIÓN OPTIMIZADA)
     */
    private function processDiseaseAlertsOptimizado($data, $estacion_id, $dryRun = false, &$insertsHorasCondiciones, &$updatesHorasCondiciones, &$insertsAcumuladas)
    {
        try {
            // OPTIMIZACIÓN: Usar enfermedades precargadas
            foreach ($this->enfermedades as $enfermedad) {
                // OPTIMIZACIÓN: Usar cache en lugar de consulta
                $key = $data['estacion_id'] . '_' . $enfermedad->tipo_cultivo_id . '_' . $enfermedad->enfermedad_id;
                $dEnfermedadHoras = isset($this->horasCondicionesCache[$key]) ? [$this->horasCondicionesCache[$key]] : [];

                //Si no existe el registro lo creamos
                if (count($dEnfermedadHoras) == 0) {
                    $dataHoras = [
                        'fecha_ultima_transmision' => $data['created_at'],
                        'enfermedad_id' => $enfermedad->enfermedad_id,
                        'tipo_cultivo_id' => $enfermedad->tipo_cultivo_id,
                        'estacion_id' => $data['estacion_id'],
                        'minutos' => 0,
                    ];

                    if (!$dryRun) {
                        $insertsHorasCondiciones[] = $dataHoras;
                        // Actualizar cache
                        $this->horasCondicionesCache[$key] = (object) $dataHoras;
                    } else {
                        $this->line("  [DRY-RUN] Se insertaría registro para enfermedad {$enfermedad->enfermedad_id} en estación {$estacion_id}");
                    }
                }

                //Si se cumplen las condiciones de riesgo entonces se acumulan las horas
                if (
                    $data['humedad_relativa'] >= $enfermedad->riesgo_humedad &&
                    $data['humedad_relativa'] <= $enfermedad->riesgo_humedad_max &&
                    $data['temperatura'] >= $enfermedad->riesgo_temperatura &&
                    $data['temperatura'] <= $enfermedad->riesgo_temperatura_max
                ) {

                    // Verificar que existe el registro antes de acceder
                    if (count($dEnfermedadHoras) > 0) {
                        if ($dEnfermedadHoras[0]->minutos == 0)
                            $minutosTranscurridos = 1;
                        else
                            $minutosTranscurridos = abs((strtotime($data['created_at']) - strtotime($dEnfermedadHoras[0]->fecha_ultima_transmision))) / 60;
                    } else {
                        $minutosTranscurridos = 1; // Si no existe registro, es la primera vez
                    }

                    if (!$dryRun) {
                        $updatesHorasCondiciones[] = [
                            'query' => "UPDATE enfermedad_horas_condiciones SET fecha_ultima_transmision=?, minutos=minutos+? WHERE tipo_cultivo_id=? AND enfermedad_id=? AND estacion_id=?",
                            'bindings' => [$data['created_at'], $minutosTranscurridos, $enfermedad->tipo_cultivo_id, $enfermedad->enfermedad_id, $data['estacion_id']]
                        ];

                        // Actualizar cache
                        $this->horasCondicionesCache[$key]->fecha_ultima_transmision = $data['created_at'];
                        $this->horasCondicionesCache[$key]->minutos += $minutosTranscurridos;
                    } else {
                        $this->line("  [DRY-RUN] Se actualizaría acumulación para enfermedad {$enfermedad->enfermedad_id} en estación {$estacion_id}");
                    }
                } else {
                    //Antes de reiniciar el contador deberíamos registrar en otra tabla cuántas horas se habían acumulado hasta el momento 
                    $minutosAcumulados = 0;
                    if (count($dEnfermedadHoras) > 0) {
                        $minutosAcumulados = $dEnfermedadHoras[0]->minutos ?? 0;
                    }

                    // Solo registrar si hay minutos acumulados (no registrar 0s)
                    if ($minutosAcumulados > 0) {
                        if (!$dryRun) {
                            $insertsAcumuladas[] = [
                                'fecha' => $data['created_at'],
                                'created_at' => $data['created_at'],
                                'minutos' => $minutosAcumulados,
                                'tipo_cultivo_id' => $enfermedad->tipo_cultivo_id,
                                'enfermedad_id' => $enfermedad->enfermedad_id,
                                'estacion_id' => $data['estacion_id'],
                            ];
                        } else {
                            $this->line("  [DRY-RUN] Se insertaría registro acumulado de " . $minutosAcumulados . " minutos para enfermedad {$enfermedad->enfermedad_id} en estación {$estacion_id} con fecha {$data['created_at']}");
                        }
                    }

                    //Si no se cumplen las condiciones de riesgo entonces se reinicia el contador
                    if (!$dryRun) {
                        $updatesHorasCondiciones[] = [
                            'query' => "UPDATE enfermedad_horas_condiciones SET fecha_ultima_transmision=?, minutos=0 WHERE tipo_cultivo_id=? AND enfermedad_id=? AND estacion_id=?",
                            'bindings' => [$data['created_at'], $enfermedad->tipo_cultivo_id, $enfermedad->enfermedad_id, $data['estacion_id']]
                        ];

                        // Actualizar cache
                        $this->horasCondicionesCache[$key]->fecha_ultima_transmision = $data['created_at'];
                        $this->horasCondicionesCache[$key]->minutos = 0;
                    } else {
                        $this->line("  [DRY-RUN] Se reiniciaría contador para enfermedad {$enfermedad->enfermedad_id} en estación {$estacion_id}");
                    }
                }
            }
        } catch (\Exception $e) {
            if (!$dryRun) {
                $this->error("Error procesando alertas de enfermedades: " . $e->getMessage());
            }
        }
    }

    /**
     * Procesa las alertas de enfermedades (misma función que StationController) - MANTENER PARA COMPATIBILIDAD
     */
    private function processDiseaseAlerts($data, $estacion_id, $dryRun = false)
    {
        try {
            $enfermedades = DB::select("SELECT ee.*  FROM enfermedades e INNER JOIN tipo_cultivos_enfermedades ee ON ee.enfermedad_id=e.id WHERE 1");

            foreach ($enfermedades as $enfermedad) {
                //Vamos por el registro en la tabla que acumula las horas
                $qEnfermedadHoras = "SELECT * FROM enfermedad_horas_condiciones WHERE tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "' AND enfermedad_id='" . $enfermedad->enfermedad_id . "' AND estacion_id='" . $data['estacion_id'] . "'";
                $dEnfermedadHoras = DB::select($qEnfermedadHoras);

                //Si no existe el registro lo creamos
                if (count($dEnfermedadHoras) == 0) {
                    $dataHoras = array();
                    $dataHoras['fecha_ultima_transmision'] = $data['created_at'];
                    $dataHoras['enfermedad_id'] = $enfermedad->enfermedad_id;
                    $dataHoras['tipo_cultivo_id'] = $enfermedad->tipo_cultivo_id;
                    $dataHoras['estacion_id'] = $data['estacion_id'];
                    $dataHoras['minutos'] = 0;

                    if (!$dryRun) {
                        try {
                            DB::table('enfermedad_horas_condiciones')->insert($dataHoras);
                        } catch (\Illuminate\Database\QueryException $ex) {
                            Log::info($ex->getMessage());
                        }
                    } else {
                        $this->line("  [DRY-RUN] Se insertaría registro para enfermedad {$enfermedad->enfermedad_id} en estación {$estacion_id}");
                    }
                }

                //Si se cumplen las condiciones de riesgo entonces se acumulan las horas
                if ($data['humedad_relativa'] >= $enfermedad->riesgo_humedad && $data['humedad_relativa'] <= $enfermedad->riesgo_humedad_max && $data['temperatura'] >= $enfermedad->riesgo_temperatura && $data['temperatura'] <= $enfermedad->riesgo_temperatura_max) {
                    if ($dEnfermedadHoras[0]->minutos == 0)
                        $minutosTranscurridos = 1;
                    else
                        $minutosTranscurridos = abs((strtotime($data['created_at']) - strtotime($dEnfermedadHoras[0]->fecha_ultima_transmision))) / 60;

                    if (!$dryRun) {
                        DB::update("UPDATE enfermedad_horas_condiciones SET fecha_ultima_transmision='" . $data['created_at'] . "', minutos=minutos+" . $minutosTranscurridos . " WHERE tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "' AND enfermedad_id='" . $enfermedad->enfermedad_id . "' AND estacion_id='" . $data['estacion_id'] . "'");
                    } else {
                        $this->line("  [DRY-RUN] Se actualizaría acumulación para enfermedad {$enfermedad->enfermedad_id} en estación {$estacion_id}");
                    }
                } else {
                    //Antes de reiniciar el contador deberíamos registrar en otra tabla cuántas horas se habían acumulado hasta el momento 
                    if (!$dryRun) {
                        DB::insert("INSERT INTO enfermedad_horas_acumuladas_condiciones SET fecha='" . $data['created_at'] . "', created_at='" . $data['created_at'] . "', minutos='" . $dEnfermedadHoras[0]->minutos . "', tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "', enfermedad_id='" . $enfermedad->enfermedad_id . "', estacion_id='" . $data['estacion_id'] . "'");
                    } else {
                        $this->line("  [DRY-RUN] Se insertaría registro acumulado de {$dEnfermedadHoras[0]->minutos} minutos para enfermedad {$enfermedad->enfermedad_id} en estación {$estacion_id} con fecha {$data['created_at']}");
                    }

                    //Si no se cumplen las condiciones de riesgo entonces se reinicia el contador
                    if (!$dryRun) {
                        DB::update("UPDATE enfermedad_horas_condiciones SET fecha_ultima_transmision='" . $data['created_at'] . "', minutos=0 WHERE tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "' AND enfermedad_id='" . $enfermedad->enfermedad_id . "' AND estacion_id='" . $data['estacion_id'] . "'");
                    } else {
                        $this->line("  [DRY-RUN] Se reiniciaría contador para enfermedad {$enfermedad->enfermedad_id} en estación {$estacion_id}");
                    }
                }
            }
        } catch (\Exception $e) {
            if (!$dryRun) {
                $this->error("Error procesando alertas de enfermedades: " . $e->getMessage());
            }
        }
    }
}
