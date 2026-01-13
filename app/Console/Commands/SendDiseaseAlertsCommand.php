<?php

namespace App\Console\Commands;

use App\Jobs\SendDiseaseAlertsJob;
use Illuminate\Console\Command;

class SendDiseaseAlertsCommand extends Command
{
    protected $signature = 'diseases:send-alerts {--dry-run : Ejecutar en modo prueba sin enviar emails}';
    protected $description = 'Enviar alertas de enfermedades por email (ejecuta el Job)';

    public function handle()
    {
        $this->info('🚀 Ejecutando SendDiseaseAlertsJob...');

        try {
            // Ejecutar el job directamente
            $job = new SendDiseaseAlertsJob();
            $job->handle();

            $this->info('✅ Job ejecutado correctamente');
            $this->info('📋 Revisa los logs para ver los detalles de la ejecución');
        } catch (\Exception $e) {
            $this->error('❌ Error ejecutando el job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
