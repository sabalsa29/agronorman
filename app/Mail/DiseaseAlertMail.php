<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DiseaseAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build(): self
    {
        $fromAddress = config('services.brevo.from_address');
        $fromName = config('services.brevo.from_name');

        $mail = $this
            ->subject('Alerta de Enfermedad: ' . ($this->payload['enfermedad'] ?? ''))
            ->view('emails.disease_alert')
            ->with(['data' => $this->payload]);

        if ($fromAddress) {
            $mail->from($fromAddress, $fromName ?: null);
        }

        return $mail;
    }
}
