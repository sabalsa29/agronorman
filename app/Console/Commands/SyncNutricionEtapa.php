<?php

namespace App\Console\Commands;

use App\Models\NutricionEtapa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncNutricionEtapa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-nutricion-etapa';

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
        DB::connection('norman_prod')->table('nutricion_etapa')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = NutricionEtapa::where('id', $registro->id)->exists();

                if (!$existe) {
                    $nuevo = new NutricionEtapa();
                    $nuevo->variable = $registro->variable;
                    $nuevo->etapa_fenologica_id = $registro->etapa_id;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
