<?php

namespace App\Console\Commands;

use App\Models\Parcelas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncParcelas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-parcelas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Carga los registros de parcelas desde la base de datos remota a la base de datos local';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::connection('norman_prod')->table('parcela')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = Parcelas::where('id', $registro->id)->exists();

                if (!$existe) {
                    $nuevo = new Parcelas();
                    $nuevo->id          = $registro->id;
                    $nuevo->cliente_id  = $registro->cliente_id;
                    $nuevo->nombre      = $registro->nombre;
                    $nuevo->superficie  = $registro->superficie;
                    $nuevo->lat         = $registro->lat;
                    $nuevo->lon         = $registro->lon;
                    $nuevo->status      = $registro->estatus;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
