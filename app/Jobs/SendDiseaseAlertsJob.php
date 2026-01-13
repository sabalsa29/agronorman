<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendDiseaseAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos de timeout
    public $tries = 3; // Reintentar 3 veces si falla

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $fechaActual = Carbon::now('America/Mexico_City');
        $fechaInicio = $fechaActual->copy()->subHour();

        Log::info("SendDiseaseAlertsJob: Consultando registros desde: {$fechaInicio->format('Y-m-d H:i:s')} hasta: {$fechaActual->format('Y-m-d H:i:s')}");

        $registros = DB::select("
            SELECT 
                ehac.*,
                tce.riesgo_medio,
                tce.riesgo_mediciones,
                (ehac.minutos / 60.0) as horas
            FROM enfermedad_horas_acumuladas_condiciones ehac
            INNER JOIN tipo_cultivos_enfermedades tce 
                ON ehac.enfermedad_id = tce.enfermedad_id 
                AND ehac.tipo_cultivo_id = tce.tipo_cultivo_id
            WHERE ehac.fecha BETWEEN '{$fechaInicio->format('Y-m-d H:i:s')}' AND '{$fechaActual->format('Y-m-d H:i:s')}' 
            ORDER BY ehac.fecha DESC
        ");

        Log::info("SendDiseaseAlertsJob: Se encontraron " . count($registros) . " registros");

        $alertasEnviadas = 0;

        foreach ($registros as $registro) {
            $horas = round($registro->horas, 2);
            $riesgoMedio = $registro->riesgo_medio;
            $riesgoMediciones = $registro->riesgo_mediciones;

            $estado = "BAJO";
            if ($horas >= $riesgoMediciones) {
                $estado = "ALTO RIESGO";
            } elseif ($horas >= $riesgoMedio) {
                $estado = "RIESGO MEDIO";
            }

            Log::info("SendDiseaseAlertsJob: Horas: {$horas} | Riesgo Medio: {$riesgoMedio} | Riesgo Máximo: {$riesgoMediciones} | Estado: {$estado}");

            // Enviar alertas por email según el nivel de riesgo
            if ($estado === "ALTO RIESGO") {
                $this->enviarAlertaAltoRiesgo($registro, $estado, $horas);
                $alertasEnviadas++;
            } elseif ($estado === "RIESGO MEDIO") {
                // $this->enviarAlertaRiesgoMedio($registro, $estado, $horas);
                // $alertasEnviadas++;
            }
        }

        if ($alertasEnviadas > 0) {
            Log::info("SendDiseaseAlertsJob: 📧 Se enviaron {$alertasEnviadas} alertas por email (Riesgo Medio y Alto)");
        } else {
            Log::info("SendDiseaseAlertsJob: ✅ No se detectaron registros con Riesgo Medio o Alto");
        }

        Log::info("SendDiseaseAlertsJob: Comando ejecutado correctamente");
    }

    private function enviarAlertaAltoRiesgo($registro, $estado, $horas)
    {
        try {
            // Obtener información de la enfermedad
            $enfermedad = DB::select("
                SELECT e.nombre as enfermedad_nombre, tc.nombre as tipo_cultivo_nombre, est.uuid as estacion_nombre
                FROM enfermedades e
                INNER JOIN tipo_cultivos_enfermedades tce ON e.id = tce.enfermedad_id
                INNER JOIN tipo_cultivos tc ON tce.tipo_cultivo_id = tc.id
                INNER JOIN estaciones est ON est.id = ?
                WHERE e.id = ? AND tce.tipo_cultivo_id = ?
            ", [$registro->estacion_id, $registro->enfermedad_id, $registro->tipo_cultivo_id]);

            if (empty($enfermedad)) {
                Log::error("SendDiseaseAlertsJob: No se pudo obtener información de la enfermedad para el registro ID: {$registro->id}");
                return;
            }

            $info = $enfermedad[0];

            $payload = [
                'enfermedad' => $info->enfermedad_nombre,
                'tipo_cultivo' => $info->tipo_cultivo_nombre,
                'estacion' => $info->estacion_nombre,
                'horas_acumuladas' => $horas,
                'umbral_riesgo' => $registro->riesgo_mediciones,
                'riesgo_medio' => $registro->riesgo_medio,
                'riesgo_maximo' => $registro->riesgo_mediciones,
                'fecha_deteccion' => Carbon::parse($registro->fecha)->format('d/m/Y H:i:s'),
                'zona_manejo' => 'Zona de Monitoreo',
                'estado' => $estado,
                'recomendaciones' => [
                    'Aplicar fungicida preventivo inmediatamente',
                    'Reducir humedad en el cultivo',
                    'Aumentar ventilación',
                    'Monitorear cada 2 horas'
                ]
            ];

            // Usar la plantilla simplificada directamente
            Mail::send('emails.high_risk_disease_alert', ['data' => $payload], function ($message) use ($info, $horas) {
                $message->to(config('services.disease_alert_email'))
                    ->subject('🚨 ALERTA CRÍTICA: Enfermedad en ALTO RIESGO - ' . $info->enfermedad_nombre)
                    ->from(config('services.brevo.from_address'), 'PIA Alertas');
            });

            Log::info("SendDiseaseAlertsJob: 🚨 ALERTA CRÍTICA enviada para: {$info->enfermedad_nombre} - {$info->tipo_cultivo_nombre} - {$horas} horas");
        } catch (\Exception $e) {
            Log::error("SendDiseaseAlertsJob: Error enviando alerta de alto riesgo: " . $e->getMessage());
            throw $e; // Re-lanzar para que el job pueda reintentar
        }
    }

    private function enviarAlertaRiesgoMedio($registro, $estado, $horas)
    {
        try {
            // Obtener información de la enfermedad
            $enfermedad = DB::select("
                SELECT e.nombre as enfermedad_nombre, tc.nombre as tipo_cultivo_nombre, est.uuid as estacion_nombre
                FROM enfermedades e
                INNER JOIN tipo_cultivos_enfermedades tce ON e.id = tce.enfermedad_id
                INNER JOIN tipo_cultivos tc ON tce.tipo_cultivo_id = tc.id
                INNER JOIN estaciones est ON est.id = ?
                WHERE e.id = ? AND tce.tipo_cultivo_id = ?
            ", [$registro->estacion_id, $registro->enfermedad_id, $registro->tipo_cultivo_id]);

            if (empty($enfermedad)) {
                Log::error("SendDiseaseAlertsJob: No se pudo obtener información de la enfermedad para el registro ID: {$registro->id}");
                return;
            }

            $info = $enfermedad[0];

            $payload = [
                'enfermedad' => $info->enfermedad_nombre,
                'tipo_cultivo' => $info->tipo_cultivo_nombre,
                'estacion' => $info->estacion_nombre,
                'horas_acumuladas' => $horas,
                'umbral_riesgo' => $registro->riesgo_medio,
                'riesgo_medio' => $registro->riesgo_medio,
                'riesgo_maximo' => $registro->riesgo_mediciones,
                'fecha_deteccion' => Carbon::parse($registro->fecha)->format('d/m/Y H:i:s'),
                'zona_manejo' => 'Zona de Monitoreo',
                'estado' => $estado,
                'recomendaciones' => [
                    'Monitorear condiciones climáticas',
                    'Preparar aplicación preventiva',
                    'Revisar humedad del suelo',
                    'Evaluar en próximas 4 horas'
                ]
            ];

            // Usar la plantilla simplificada directamente
            Mail::send('emails.medium_risk_disease_alert', ['data' => $payload], function ($message) use ($info, $horas) {
                $message->to(config('services.disease_alert_email'))
                    ->subject('⚠️ ALERTA: Enfermedad en RIESGO MEDIO - ' . $info->enfermedad_nombre)
                    ->from(config('services.brevo.from_address'), 'PIA Alertas');
            });

            Log::info("SendDiseaseAlertsJob: ⚠️ ALERTA RIESGO MEDIO enviada para: {$info->enfermedad_nombre} - {$info->tipo_cultivo_nombre} - {$horas} horas");
        } catch (\Exception $e) {
            Log::error("SendDiseaseAlertsJob: Error enviando alerta de riesgo medio: " . $e->getMessage());
            throw $e; // Re-lanzar para que el job pueda reintentar
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SendDiseaseAlertsJob falló después de todos los intentos: ' . $exception->getMessage());
    }
}
