<?php

namespace App\Console\Commands;

use App\Models\TipoSuelo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTipoSuelos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-tipo-suelos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los tipos de suelo desde la base de datos norman_prod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de tipos de suelo...');

        // Obtener registros de la base de datos norman_prod
        $tiposSuelo = DB::connection('norman_prod')
            ->table('tipos_suelo')
            ->get();

        $bar = $this->output->createProgressBar(count($tiposSuelo));
        $bar->start();

        foreach ($tiposSuelo as $tipoSuelo) {
            // Verificar si el tipo de suelo ya existe
            $tipoSueloExistente = TipoSuelo::where('tipo_suelo', $tipoSuelo->tipo_suelo)->first();

            if (!$tipoSueloExistente) {
                // Crear nuevo tipo de suelo
                TipoSuelo::create([
                    'id' => $tipoSuelo->id,
                    'tipo_suelo' => $tipoSuelo->tipo_suelo,
                    'bajo' => $tipoSuelo->bajo,
                    'optimo_min' => $tipoSuelo->optimo,
                    'optimo_max' => $tipoSuelo->optimo_max,
                    'alto' => $tipoSuelo->alto,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sincronización de tipos de suelo completada.');
    }
}
