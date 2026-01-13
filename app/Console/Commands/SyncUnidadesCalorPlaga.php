<?php

namespace App\Console\Commands;

use App\Models\UnidadesCalorPlaga;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUnidadesCalorPlaga extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-unidades-calor-plaga';

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
        DB::connection('norman_prod')
            ->table('unidades_calor_plaga')
            ->orderBy('id')
            ->chunk(1000, function ($registros) {
                foreach ($registros as $registro) {
                    // 1) Verificar si el registro ya existe en la base de datos local
                    $existeLocal = UnidadesCalorPlaga::where('id', $registro->id)->exists();
                    if ($existeLocal) {
                        continue;
                    }

                    // 2) Verificar que la plaga referenciada exista en la tabla 'plagas'
                    $plagaExiste = DB::table('plagas')
                        ->where('id', $registro->plaga_id)
                        ->exists();
                    if (! $plagaExiste) {
                        // Si no existe la plaga, lo omitimos
                        continue;
                    }

                    // 3) Insertar solamente si la plaga sí existe
                    $dato = new UnidadesCalorPlaga();
                    $dato->id             = $registro->id;
                    $dato->zona_manejo_id = $registro->zonamanejo_id;
                    $dato->plaga_id       = $registro->plaga_id;
                    $dato->uc             = $registro->uc;
                    $dato->fecha          = $registro->fecha;
                    $dato->created_at     = $registro->created_at;
                    $dato->updated_at     = $registro->updated_at;
                    $dato->timestamps     = false;
                    $dato->save();
                }
            });
        $this->info('Sincronización completada.');
    }
}
