<?php

namespace App\Console\Commands;

use App\Models\Cultivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCultivos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-cultivos';

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
        DB::connection('norman_prod')->table('especie')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = Cultivo::where('id', $registro->id)->exists();

                if (!$existe) {
                    $nuevo = new Cultivo();
                    $nuevo->id = $registro->id;
                    $nuevo->nombre = $registro->nombre;
                    $nuevo->descripcion = $registro->descripcion;
                    $nuevo->imagen = $registro->imagen;
                    $nuevo->icono = $registro->icono;
                    $nuevo->temp_base_calor = $registro->temp_base_calor;
                    $nuevo->tipo_vida = $registro->tipo_vida;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
