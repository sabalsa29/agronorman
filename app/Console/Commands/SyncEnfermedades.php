<?php

namespace App\Console\Commands;

use App\Models\Enfermedades;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEnfermedades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-enfermedades';

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
        DB::connection('norman_prod')->table('enfermedad')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = Enfermedades::where('id', $registro->id)->exists();

                if (!$existe) {
                    $dato = new Enfermedades();
                    $dato->id = $registro->id;
                    $dato->nombre = $registro->nombre;
                    $dato->slug = $registro->slug;
                    $dato->status = $registro->estatus;
                    $dato->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
