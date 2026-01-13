<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZonaManejos;
use App\Models\Forecast;
use App\Models\EstacionDato;
use Carbon\Carbon;

class DiagnosticoZonasTemperaturaCommand extends Command
{
    protected $signature = 'diagnostico:zonas-temperatura {--fecha=}';
    protected $description = 'Diagnostica zonas de manejo problemáticas para el Job de ResumenTemperaturasJob';

    public function handle()
    {
        $fecha = $this->option('fecha') ?: Carbon::now()->subDay()->format('Y-m-d');
        $this->info("Diagnóstico de zonas de manejo para la fecha: $fecha\n");
        $total = 0;
        $ok = 0;
        $sinEstaciones = 0;
        $sinForecast = 0;
        $sinDatos = 0;

        foreach (ZonaManejos::all() as $zona) {
            $total++;
            $msg = "Zona {$zona->id}: ";
            $estaciones = $zona->estaciones;
            if ($estaciones->isEmpty()) {
                $this->warn($msg . "❌ Sin estaciones asociadas");
                $sinEstaciones++;
                continue;
            }
            $parcelaId = $zona->parcela_id;
            $forecast = Forecast::where('parcela_id', $parcelaId)
                ->where('fecha_prediccion', $fecha)
                ->first();
            if (!$forecast) {
                $this->warn($msg . "❌ Sin forecast para la fecha $fecha");
                $sinForecast++;
                continue;
            }
            $estacionIds = $estaciones->pluck('id')->toArray();
            $datos = EstacionDato::whereIn('estacion_id', $estacionIds)
                ->whereBetween('created_at', [
                    Carbon::parse($fecha)->startOfDay(),
                    Carbon::parse($fecha)->endOfDay()
                ])->count();
            if ($datos == 0) {
                $this->warn($msg . "❌ Sin datos de estación para $fecha");
                $sinDatos++;
                continue;
            }
            $this->info($msg . "✅ OK (estaciones, forecast y datos presentes)");
            $ok++;
        }
        $this->line("");
        $this->info("Resumen:");
        $this->info("Total zonas: $total");
        $this->info("OK: $ok");
        $this->info("Sin estaciones: $sinEstaciones");
        $this->info("Sin forecast: $sinForecast");
        $this->info("Sin datos de estación: $sinDatos");
    }
}
