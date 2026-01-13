<?php

namespace App\Jobs;

use App\Mail\DiseaseAlertMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDiseaseAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $enfermedadId;
    public int $tipoCultivoId;
    public int $zonaManejoId;
    public float $horasAcumuladas;
    public float $umbralMedio;
    public float $umbralMaximo;
    public string $enfermedadNombre;

    public function __construct(
        int $enfermedadId,
        int $tipoCultivoId,
        int $zonaManejoId,
        float $horasAcumuladas,
        float $umbralMedio,
        float $umbralMaximo,
        string $enfermedadNombre
    ) {
        $this->enfermedadId = $enfermedadId;
        $this->tipoCultivoId = $tipoCultivoId;
        $this->zonaManejoId = $zonaManejoId;
        $this->horasAcumuladas = $horasAcumuladas;
        $this->umbralMedio = $umbralMedio;
        $this->umbralMaximo = $umbralMaximo;
        $this->enfermedadNombre = $enfermedadNombre;
    }

    public function handle(): void
    {
        try {
            $to = config('services.disease_alert_email');
            if (!$to) {
                Log::warning('SendDiseaseAlertJob: DISEASE_ALERT_EMAIL no configurado, omitiendo envío.');
                return;
            }

            Mail::to($to)->send(new DiseaseAlertMail([
                'enfermedad_id' => $this->enfermedadId,
                'tipo_cultivo_id' => $this->tipoCultivoId,
                'zona_manejo_id' => $this->zonaManejoId,
                'horas' => $this->horasAcumuladas,
                'umbral_medio' => $this->umbralMedio,
                'umbral_maximo' => $this->umbralMaximo,
                'enfermedad' => $this->enfermedadNombre,
            ]));
        } catch (\Throwable $e) {
            Log::error('SendDiseaseAlertJob error: ' . $e->getMessage());
        }
    }
}
