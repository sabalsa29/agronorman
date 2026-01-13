<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PopulateDiseaseHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diseases:populate-history 
                            {--estacion_id= : ID de la estación específica (opcional)}
                            {--start_date= : Fecha de inicio (YYYY-MM-DD) (opcional)}
                            {--end_date= : Fecha de fin (YYYY-MM-DD) (opcional)}
                            {--enfermedad_id= : ID de enfermedad específica (opcional)}
                            {--tipo_cultivo_id= : ID de tipo de cultivo específico (opcional)}
                            {--limit= : Limitar número de enfermedades a procesar (opcional)}
                            {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pobla la tabla enfermedad_horas_acumuladas_condiciones con datos históricos de estacion_dato';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando población de datos históricos de enfermedades...');

        // Obtener parámetros
        $estacionId = $this->option('estacion_id');
        $startDate = $this->option('start_date');
        $endDate = $this->option('end_date');
        $enfermedadId = $this->option('enfermedad_id');
        $tipoCultivoId = $this->option('tipo_cultivo_id');
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');

        // Configurar fechas por defecto si no se especifican
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
        }

        $this->info("📅 Período: {$startDate} a {$endDate}");
        if ($estacionId) {
            $this->info("🏭 Estación: {$estacionId}");
        }
        if ($enfermedadId) {
            $this->info("🦠 Enfermedad: {$enfermedadId}");
        }
        if ($tipoCultivoId) {
            $this->info("🌱 Tipo de cultivo: {$tipoCultivoId}");
        }
        if ($dryRun) {
            $this->warn("🔍 MODO DRY-RUN: No se realizarán cambios en la base de datos");
        }

        try {
            // Obtener enfermedades configuradas
            $enfermedades = $this->obtenerEnfermedades($enfermedadId, $tipoCultivoId);

            if ($enfermedades->isEmpty()) {
                $this->error('❌ No se encontraron enfermedades configuradas');
                return 1;
            }

            $this->info("📊 Encontradas " . $enfermedades->count() . " enfermedades configuradas");

            // Mostrar enfermedades que se van a procesar
            foreach ($enfermedades as $enfermedad) {
                $this->line("  🦠 Enfermedad {$enfermedad->enfermedad_id} - Tipo cultivo {$enfermedad->tipo_cultivo_id}");
            }

            // Obtener estaciones únicas desde estacion_dato
            $estacionesUnicas = $this->obtenerEstacionesDesdeEstacionDato($startDate, $endDate, $estacionId);

            if ($estacionesUnicas->isEmpty()) {
                $this->error('❌ No se encontraron datos de estacion_dato en el período especificado');
                return 1;
            }

            $this->info("🏭 Encontradas " . $estacionesUnicas->count() . " estaciones con datos en estacion_dato");

            $totalRegistros = 0;
            $totalEnfermedades = 0;

            // Procesar cada estación que tiene datos
            foreach ($estacionesUnicas as $estacionId) {
                $this->info("🔄 Procesando estación {$estacionId}");

                // Procesar cada enfermedad para esta estación
                foreach ($enfermedades as $enfermedad) {
                    $registrosGenerados = $this->procesarEnfermedadHistorica(
                        $estacionId,
                        $enfermedad,
                        $startDate,
                        $endDate,
                        $dryRun
                    );

                    $totalRegistros += $registrosGenerados;
                    $totalEnfermedades++;

                    $this->info("  ✅ Enfermedad {$enfermedad->enfermedad_id}: {$registrosGenerados} períodos generados");
                }
            }

            $this->info("🎉 ¡Proceso completado!");
            $this->info("📈 Total de períodos generados: {$totalRegistros}");
            $this->info("🦠 Total de enfermedades procesadas: {$totalEnfermedades}");

            if ($dryRun) {
                $this->warn("💡 Para ejecutar realmente, elimina la opción --dry-run");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error('Error en PopulateDiseaseHistoryCommand', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Obtiene las enfermedades configuradas
     */
    private function obtenerEnfermedades($enfermedadId = null, $tipoCultivoId = null)
    {
        $query = DB::table('enfermedades as e')
            ->join('tipo_cultivos_enfermedades as ee', 'ee.enfermedad_id', '=', 'e.id')
            ->select('ee.*', 'e.nombre as nombre_enfermedad');

        if ($enfermedadId) {
            $query->where('ee.enfermedad_id', $enfermedadId);
        }

        if ($tipoCultivoId) {
            $query->where('ee.tipo_cultivo_id', $tipoCultivoId);
        }

        return $query->get();
    }

    /**
     * Obtiene las estaciones únicas desde estacion_dato
     */
    private function obtenerEstacionesDesdeEstacionDato($startDate, $endDate, $estacionId = null)
    {
        $query = DB::table('estacion_dato')
            ->select('estacion_id')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('humedad_relativa')
            ->whereNotNull('temperatura')
            ->distinct();

        if ($estacionId) {
            $query->where('estacion_id', $estacionId);
        }

        return $query->pluck('estacion_id');
    }

    /**
     * Procesa una enfermedad específica para una estación en un período histórico
     */
    private function procesarEnfermedadHistorica($estacionId, $enfermedad, $startDate, $endDate, $dryRun = false)
    {
        // Obtener datos de estacion_dato para el período
        $datosEstacion = DB::table('estacion_dato')
            ->where('estacion_id', $estacionId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('humedad_relativa')
            ->whereNotNull('temperatura')
            ->orderBy('created_at')
            ->get();

        if ($datosEstacion->isEmpty()) {
            $this->warn("  ⚠️  No hay datos de estación para el período especificado");
            return 0;
        }

        $this->info("  📊 Procesando " . $datosEstacion->count() . " registros de estacion_dato");

        // Procesar datos minuto a minuto
        $acumulacionActual = 0;
        $inicioAcumulacion = null;
        $registrosGenerados = 0;

        foreach ($datosEstacion as $dato) {
            // Verificar condiciones de riesgo
            $condicionesCumplidas = $this->verificarCondicionesRiesgo(
                $dato->humedad_relativa,
                $dato->temperatura,
                $enfermedad->riesgo_humedad,
                $enfermedad->riesgo_humedad_max,
                $enfermedad->riesgo_temperatura,
                $enfermedad->riesgo_temperatura_max
            );

            if ($condicionesCumplidas) {
                // Condiciones cumplidas - acumular
                if ($inicioAcumulacion === null) {
                    $inicioAcumulacion = Carbon::parse($dato->created_at);
                }
                $acumulacionActual += 1; // 1 minuto por registro
            } else {
                // Condiciones NO cumplidas - guardar acumulación anterior y reiniciar
                if ($acumulacionActual > 0) {
                    if (!$dryRun) {
                        $this->insertarRegistroAcumulado(
                            $enfermedad->enfermedad_id,
                            $enfermedad->tipo_cultivo_id,
                            $estacionId,
                            $inicioAcumulacion,
                            $acumulacionActual
                        );
                    }
                    $registrosGenerados++;
                }

                // Guardar reinicio (0 minutos) - SIEMPRE guardar, incluso con 0 minutos
                if (!$dryRun) {
                    $this->insertarRegistroAcumulado(
                        $enfermedad->enfermedad_id,
                        $enfermedad->tipo_cultivo_id,
                        $estacionId,
                        Carbon::parse($dato->created_at),
                        0 // Reinicio con 0 minutos
                    );
                }
                $registrosGenerados++;

                // Reiniciar contadores
                $acumulacionActual = 0;
                $inicioAcumulacion = null;
            }
        }

        // Guardar acumulación final si existe
        if ($acumulacionActual > 0) {
            if (!$dryRun) {
                $this->insertarRegistroAcumulado(
                    $enfermedad->enfermedad_id,
                    $enfermedad->tipo_cultivo_id,
                    $estacionId,
                    $inicioAcumulacion,
                    $acumulacionActual
                );
            }
            $registrosGenerados++;
        }

        return $registrosGenerados;
    }

    /**
     * Verifica si las condiciones ambientales cumplen los parámetros de riesgo
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

    /**
     * Inserta un registro en enfermedad_horas_acumuladas_condiciones
     */
    private function insertarRegistroAcumulado($enfermedadId, $tipoCultivoId, $estacionId, $fecha, $minutos)
    {
        try {
            // Verificar si ya existe un registro similar para evitar duplicados
            $existe = DB::table('enfermedad_horas_acumuladas_condiciones')
                ->where('fecha', $fecha->format('Y-m-d H:i:s'))
                ->where('tipo_cultivo_id', $tipoCultivoId)
                ->where('enfermedad_id', $enfermedadId)
                ->where('estacion_id', $estacionId)
                ->where('minutos', $minutos) // También verificar minutos para distinguir reinicios
                ->exists();

            if ($existe) {
                $this->line("    ⚠️  Registro duplicado, omitiendo: " . $fecha->format('Y-m-d H:i:s') . " - {$minutos} min");
                return; // Ya existe, no insertar duplicado
            }

            $inserted = DB::table('enfermedad_horas_acumuladas_condiciones')->insert([
                'fecha' => $fecha->format('Y-m-d H:i:s'),
                'minutos' => $minutos,
                'tipo_cultivo_id' => $tipoCultivoId,
                'enfermedad_id' => $enfermedadId,
                'estacion_id' => $estacionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            if ($inserted) {
                // Log para reinicios
                if ($minutos === 0) {
                    $this->line("    🔄 Reinicio registrado: " . $fecha->format('Y-m-d H:i:s'));
                } else {
                    $this->line("    ✅ Registro insertado: " . $fecha->format('Y-m-d H:i:s') . " - {$minutos} min");
                }
            } else {
                $this->error("    ❌ Error al insertar registro: " . $fecha->format('Y-m-d H:i:s'));
            }
        } catch (\Exception $e) {
            $this->error("    ❌ Error en inserción: " . $e->getMessage());
            Log::error('Error insertando registro de enfermedad', [
                'error' => $e->getMessage(),
                'fecha' => $fecha->format('Y-m-d H:i:s'),
                'minutos' => $minutos,
                'tipo_cultivo_id' => $tipoCultivoId,
                'enfermedad_id' => $enfermedadId,
                'estacion_id' => $estacionId
            ]);
        }
    }
}
