<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncForecast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-forecast';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $created = 0;
        $batch = [];
        $batchSize = 1000;

        DB::connection('aws')->table('forecast')->orderBy('id')->chunk(1000, function ($registros) use (&$created, &$batch, $batchSize) {
            foreach ($registros as $registro) {
                $batch[] = [
                    'parcela_id' => $registro->parcela_id,
                    'fecha_solicita' => $registro->fecha_solicita,
                    'hora_solicita' => $registro->hora_solicita,
                    'lat' => $registro->lat,
                    'lon' => $registro->lon,
                    'fecha_prediccion' => $registro->fecha_prediccion,
                    'summary' => $registro->summary,
                    'icon' => $registro->icon,
                    'uvindex' => $registro->uvindex,
                    'sunriseTime' => $registro->sunriseTime,
                    'sunsetTime' => $registro->sunsetTime,
                    'temperatureHigh' => $registro->temperatureHigh,
                    'temperatureHighTime' => ($registro->temperatureHighTime && $registro->temperatureHighTime !== '0000-00-00 00:00:00') ? $registro->temperatureHighTime : null,
                    'temperatureLow' => $registro->temperatureLow,
                    'temperatureLowTime' => ($registro->temperatureLowTime && $registro->temperatureLowTime !== '0000-00-00 00:00:00') ? $registro->temperatureLowTime : null,
                    'precipProbability' => $registro->precipProbability,
                    'hourly' => !empty($registro->hourly) ? $registro->hourly : null,
                    'created_at' => $registro->created_at,
                    'updated_at' => $registro->updated_at,
                ];
                // Bulk insert cada $batchSize
                if (count($batch) >= $batchSize) {
                    $inserted = DB::table('forecast')->insertOrIgnore($batch);
                    $created += $inserted;
                    $batch = [];
                }
            }
        });
        // Inserta cualquier remanente
        if (count($batch) > 0) {
            $inserted = DB::table('forecast')->insertOrIgnore($batch);
            $created += $inserted;
        }
        $this->info('Sincronización completada. Registros creados: ' . $created);
    }
}
