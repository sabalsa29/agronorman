<?php

namespace App\Console\Commands;

use App\Models\Cultivo;
use App\Models\TipoCultivos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTipoCultivos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-tipo-cultivos';

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
                $existe = TipoCultivos::where('id', $registro->id)->exists();

                if (!$existe) {
                    $nuevo = new TipoCultivos();
                    $nuevo->id = $registro->id;
                    $nuevo->cultivo_id = Cultivo::where('nombre', $registro->nombre)->first()->id;
                    $nuevo->nombre = $registro->nombre;
                    $nuevo->status = 1;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
