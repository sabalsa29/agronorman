<?php

namespace App\Console\Commands;

use App\Models\NutricionEtapaFenologicaTipoCultivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncNutricionEtapaFenologicaTipoCultivo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-nutricion-etapa-fenologica-tipo-cultivo';

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
        DB::connection('norman_prod')->table('nutricion_etapa_especie')->where('created_at', '>', '2025-03-27 00:00:00')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = NutricionEtapaFenologicaTipoCultivo::where('id', $registro->id)->exists();
                $tipoCultivoExiste = DB::table('tipo_cultivos')->where('id', $registro->especie_id)->exists();

                if (!$existe && $tipoCultivoExiste) {
                    $nuevo = new NutricionEtapaFenologicaTipoCultivo();
                    $nuevo->tipo_cultivo_id = $registro->especie_id;
                    $nuevo->etapa_fenologica_tipo_cultivo_id = $registro->especie_etapa_id;
                    $nuevo->variable = $registro->variable;
                    $nuevo->min_val = $registro->min_val;
                    $nuevo->max_val = $registro->max_val;
                    $nuevo->bajo = $registro->muy_bajo;
                    $nuevo->optimo_min = $registro->optimo;
                    $nuevo->optimo_max = $registro->optimo_max;
                    $nuevo->alto = $registro->muy_alto;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
