<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\ZonaManejos;
use App\Models\EstacionDato;
use App\Models\Forecast;

class VerificarDatosTemperatura extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verificar-datos-temperatura {--fecha= : Fecha específica (YYYY-MM-DD)} {--zona= : ID de zona de manejo específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica el estado de los datos de temperatura para debugging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fecha = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))->format('Y-m-d')
            : Carbon::yesterday()->format('Y-m-d');

        $zonaId = $this->option('zona');

        $this->info("Verificando datos de temperatura para: {$fecha}");

        if ($zonaId) {
            $zonasManejo = ZonaManejos::where('id', $zonaId)->get();
        } else {
            $zonasManejo = ZonaManejos::all();
        }

        $this->table(
            ['ID', 'Nombre', 'Parcela', 'Estaciones', 'Datos Temp', 'Forecast', 'Estado'],
            $this->verificarZonas($zonasManejo, $fecha)
        );
    }

    private function verificarZonas($zonasManejo, $fecha)
    {
        $resultados = [];

        foreach ($zonasManejo as $zona) {
            $estaciones = $zona->estaciones;
            $estacionIds = $estaciones->pluck('id')->toArray();

            // Verificar datos de temperatura
            $datosTemp = EstacionDato::whereIn('estacion_id', $estacionIds)
                ->whereDate('created_at', $fecha)
                ->count();

            // Verificar forecast
            $forecast = Forecast::where('parcela_id', $zona->parcela_id)
                ->where('fecha_prediccion', $fecha)
                ->where('fecha_solicita', $fecha)
                ->count();

            // Determinar estado
            $estado = 'OK';
            if (empty($estacionIds)) {
                $estado = 'Sin estaciones';
            } elseif ($datosTemp == 0) {
                $estado = 'Sin datos temp';
            } elseif ($forecast == 0) {
                $estado = 'Sin forecast';
            }

            $resultados[] = [
                $zona->id,
                $zona->nombre ?? 'Sin nombre',
                $zona->parcela->nombre ?? 'Sin parcela',
                count($estacionIds),
                $datosTemp,
                $forecast,
                $estado
            ];
        }

        return $resultados;
    }
}
