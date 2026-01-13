<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CalcularIndicadoresEstresJob;
use Carbon\Carbon;

class CalcularIndicadoresEstresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indicadores:calcular-estres 
                            {--fecha= : Fecha específica a procesar (formato: Y-m-d)}
                            {--dias=2 : Número de días de pronóstico a procesar}
                            {--force : Forzar ejecución incluso si ya se procesó}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula indicadores de estrés para las zonas de manejo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fecha = $this->option('fecha');
        $dias = (int) $this->option('dias');
        $force = $this->option('force');

        $this->info('🚀 Iniciando cálculo de indicadores de estrés...');

        if ($fecha) {
            $this->info("📅 Procesando fecha específica: {$fecha}");
        } else {
            $this->info('📅 Procesando fecha por defecto (ayer)');
        }

        $this->info("📊 Días de pronóstico a procesar: {$dias}");

        try {
            // Ejecutar el job
            CalcularIndicadoresEstresJob::dispatch($fecha, $dias);

            $this->info('✅ Job de cálculo de indicadores de estrés enviado a la cola');
            $this->info('📋 Para ver el progreso, revisa los logs en storage/logs/laravel.log');
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar el job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
