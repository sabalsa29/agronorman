<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessDiseaseAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fechaInicio;
    protected $fechaFin;

    /**
     * Create a new job instance.
     */
    public function __construct($fechaInicio = null, $fechaFin = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Iniciando ProcessDiseaseAlertsJob', [
                'fechaInicio' => $this->fechaInicio,
                'fechaFin' => $this->fechaFin
            ]);

            // Determinar fechas: usar las proporcionadas o por defecto última hora
            if ($this->fechaInicio && $this->fechaFin) {
                $fechaInicio = Carbon::parse($this->fechaInicio);
                $fechaFin = Carbon::parse($this->fechaFin);
            } else {
                // Por defecto: última hora
                $fechaInicio = Carbon::now()->subHour();
                $fechaFin = Carbon::now();
            }

            $datosEstacion = DB::table('estacion_dato')
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->orderBy('estacion_id')
                ->orderBy('created_at')
                ->get();

            if ($datosEstacion->isEmpty()) {
                Log::info("No hay datos recientes de estacion_dato");
                return;
            }

            // Obtener enfermedades configuradas
            $enfermedades = DB::select("SELECT ee.* FROM enfermedades e INNER JOIN tipo_cultivos_enfermedades ee ON ee.enfermedad_id=e.id WHERE 1");

            if (empty($enfermedades)) {
                Log::info("No hay enfermedades configuradas");
                return;
            }

            // Agrupar datos por estación
            $datosPorEstacion = $datosEstacion->groupBy('estacion_id');

            // Procesar cada estación
            foreach ($datosPorEstacion as $estacionId => $datosEstacion) {
                $this->procesarEstacion($estacionId, $datosEstacion, $enfermedades);
            }

            Log::info('ProcessDiseaseAlertsJob completado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error en ProcessDiseaseAlertsJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Procesa una estación específica
     */
    private function procesarEstacion($estacionId, $datosEstacion, $enfermedades)
    {
        try {
            if ($datosEstacion->isEmpty()) {
                Log::info("No hay datos recientes para estación {$estacionId}");
                return;
            }

            // Procesar cada enfermedad
            foreach ($enfermedades as $enfermedad) {
                $this->procesarEnfermedad($enfermedad, $estacionId, $datosEstacion);
            }
        } catch (\Exception $e) {
            Log::error("Error procesando estación {$estacionId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Procesa una enfermedad específica para una estación
     */
    private function procesarEnfermedad($enfermedad, $estacionId, $datosEstacion)
    {
        // Obtener parámetros de riesgo
        $riesgoHumedad = $enfermedad->riesgo_humedad ?? 80;
        $riesgoHumedadMax = $enfermedad->riesgo_humedad_max ?? 100;
        $riesgoTemperatura = $enfermedad->riesgo_temperatura ?? 20;
        $riesgoTemperaturaMax = $enfermedad->riesgo_temperatura_max ?? 30;

        $acumulacionActual = 0;
        $inicioAcumulacion = null;

        foreach ($datosEstacion as $dato) {
            // Obtener valores reales de humedad y temperatura
            $humedad = $dato->humedad_relativa ?? 0;
            $temperatura = $dato->temperatura ?? 0;

            // Verificar si las condiciones cumplen los parámetros de riesgo
            $condicionesCumplidas = $this->verificarCondicionesRiesgo(
                $humedad,
                $temperatura,
                $riesgoHumedad,
                $riesgoHumedadMax,
                $riesgoTemperatura,
                $riesgoTemperaturaMax
            );

            if ($condicionesCumplidas) {
                // Condiciones cumplidas - acumular minutos
                if ($inicioAcumulacion === null) {
                    $inicioAcumulacion = Carbon::parse($dato->created_at);
                }

                // Acumular 1 minuto por cada registro que cumple condiciones
                $acumulacionActual += 1;
            } else {
                // Condiciones NO cumplidas - guardar acumulación anterior y reiniciar
                if ($acumulacionActual > 0) {
                    $this->insertarRegistroAcumulado(
                        $enfermedad->enfermedad_id,
                        $enfermedad->tipo_cultivo_id,
                        $estacionId,
                        $inicioAcumulacion,
                        $acumulacionActual
                    );
                }

                // Reiniciar contadores
                $acumulacionActual = 0;
                $inicioAcumulacion = null;
            }
        }

        // Guardar acumulación final si existe
        if ($acumulacionActual > 0) {
            $this->insertarRegistroAcumulado(
                $enfermedad->enfermedad_id,
                $enfermedad->tipo_cultivo_id,
                $estacionId,
                $inicioAcumulacion,
                $acumulacionActual
            );
        }
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
        DB::table('enfermedad_horas_acumuladas_condiciones')->insert([
            'fecha' => $fecha->format('Y-m-d H:i:s'),
            'minutos' => $minutos,
            'tipo_cultivo_id' => $tipoCultivoId,
            'enfermedad_id' => $enfermedadId,
            'estacion_id' => $estacionId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Log::info("Registro de enfermedad insertado", [
            'enfermedad_id' => $enfermedadId,
            'tipo_cultivo_id' => $tipoCultivoId,
            'estacion_id' => $estacionId,
            'fecha' => $fecha->format('Y-m-d H:i:s'),
            'minutos' => $minutos
        ]);
    }
}
