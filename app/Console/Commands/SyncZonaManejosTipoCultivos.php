<?php

namespace App\Console\Commands;

use App\Models\ZonaManejosTipoCultivos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncZonaManejosTipoCultivos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-zona-manejos-tipo-cultivos';

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
        DB::connection('norman_prod')->table('estacion_virtual')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = ZonaManejosTipoCultivos::where('id', $registro->id)->exists();

                if (!$existe) {
                    $nuevo = new ZonaManejosTipoCultivos();
                    $nuevo->zona_manejo_id = $registro->id;
                    $nuevo->tipo_cultivo_id = $registro->especie_id;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
