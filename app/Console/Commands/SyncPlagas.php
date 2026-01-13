<?php

namespace App\Console\Commands;

use App\Models\Plaga;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPlagas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-plagas';

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
        DB::connection('norman_prod')->table('plaga')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = Plaga::where('id', $registro->id)->exists();

                if (!$existe) {
                    $dato = new Plaga();
                    $dato->id = $registro->id;
                    $dato->nombre = $registro->nombre;
                    $dato->descripcion = $registro->descripcion;
                    $dato->slug = $registro->slug;
                    $dato->imagen = $registro->imagen;
                    $dato->posicion1 = $registro->posicion1;
                    $dato->posicion2 = $registro->posicion2;
                    $dato->posicion3 = $registro->posicion3;
                    $dato->posicion4 = $registro->posicion4;
                    $dato->posicion5 = $registro->posicion5;
                    $dato->posicion6 = $registro->posicion6;
                    $dato->umbral_min = $registro->umbral_min;
                    $dato->umbral_max = $registro->umbral_max;
                    $dato->unidades_calor_ciclo = $registro->unidades_calor_ciclo;
                    $dato->created_at = $registro->created_at;
                    $dato->updated_at = $registro->updated_at;
                    $dato->timestamps = false;
                    $dato->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
