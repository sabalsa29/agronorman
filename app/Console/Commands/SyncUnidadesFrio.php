<?php

namespace App\Console\Commands;

use App\Models\UnidadesFrio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUnidadesFrio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-unidades-frio';

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
        DB::connection('norman_prod')->table('unidades_frio')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = UnidadesFrio::where('id', $registro->id)->exists();

                if (!$existe) {
                    UnidadesFrio::unsetEventDispatcher();
                    $nuevo = new UnidadesFrio();
                    $nuevo->id = $registro->id;
                    $nuevo->zona_manejo_id = $registro->zona_id;
                    $nuevo->fecha = $registro->fecha;
                    $nuevo->unidades = $registro->unidades;
                    $nuevo->created_at = $registro->created_at;
                    $nuevo->updated_at = $registro->updated_at;
                    $nuevo->timestamps = false;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
