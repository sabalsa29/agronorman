<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\UpdateWeatherForecastJob;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ResumenTemperaturasJob;
use App\Jobs\CalcularIndicadoresEstresJob;
use App\Console\Commands\CalcularIndicadoresEstresCommand;
use App\Jobs\CalcularUnidadesFrioJob;
use App\Console\Commands\CalcularUnidadesFrioCommand;
use App\Jobs\CalcularUnidadesCalorJob;
use App\Console\Commands\CalcularUnidadesCalorCommand;
use App\Jobs\SendDiseaseAlertsJob;
use App\Console\Commands\ResumenTemperaturasCronjob;
use App\Console\Commands\DesgloseTemperaturasCommand;
use App\Console\Commands\DiagnosticoZonasTemperaturaCommand;
use App\Jobs\ProcessDiseaseAlertsJob;
use App\Console\Commands\SincronizarDatosVientoCommand;
// use Illuminate\Console\Scheduling\Schedule; // Eliminada para evitar conflicto

// Ejecutar resumen de temperaturas todos los días a las 06:00 con fecha del día en curso
Schedule::command('app:resumen-temperaturas-cronjob')->dailyAt('08:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Comando manual para calcular indicadores de estrés
Artisan::command('calcular:indicadores-estres {--fecha=}', function () {
    $fecha = $this->option('fecha') ?: now()->format('Y-m-d');
    $this->info("Calculando indicadores de estrés para la fecha: {$fecha}");

    $job = new CalcularIndicadoresEstresJob($fecha);
    $job->handle();

    $this->info('✅ Cálculo completado exitosamente');
})->purpose('Calcular indicadores de estrés para una fecha específica');

// Comando manual para calcular desglose de temperaturas
Artisan::command('calcular:desglose-temperaturas {--fecha=}', function () {
    $fecha = $this->option('fecha') ?: now()->subDay()->format('Y-m-d');
    $this->info("Calculando desglose de temperaturas para la fecha: {$fecha}");
    $job = new ResumenTemperaturasJob($fecha);
    $job->handle();
    $this->info('✅ Cálculo completado exitosamente');
})->purpose('Calcular desglose de temperaturas para una fecha específica');

// Comando manual para procesar alertas de enfermedades
Artisan::command('procesar:alertas-enfermedades', function () {
    $this->info("Procesando alertas de enfermedades...");
    $job = new ProcessDiseaseAlertsJob();
    $job->handle();
    $this->info('✅ Procesamiento de alertas de enfermedades completado exitosamente');
})->purpose('Procesar alertas de enfermedades manualmente');

Schedule::job(new UpdateWeatherForecastJob())->everyFourHours();
Schedule::job(new CalcularIndicadoresEstresJob())->dailyAt('08:00');
Schedule::job(new CalcularUnidadesFrioJob())->dailyAt('09:00');
Schedule::job(new CalcularUnidadesCalorJob())->dailyAt('10:00');
Schedule::command('precipitacion:sync')->hourly();
Schedule::command('viento:sincronizar')->everyTwoHours();
Schedule::command('presion:sync')->hourly();
Schedule::job(new ProcessDiseaseAlertsJob())->hourly();

// Programación: enviar alertas de enfermedades cada hora
Schedule::job(new SendDiseaseAlertsJob())->hourly();

// Programación: procesar temperaturas diariamente a la 01:00 (hora de México)
Schedule::command('temperatura:procesar')->dailyAt('07:00'); // 01:00 México = 07:00 UTC
