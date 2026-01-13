<?php

namespace App\Console\Commands;

use App\Models\TipoCultivosPlaga;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTipoCultivosPlagas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-tipo-cultivos-plagas';

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
            ->table('especie_plaga')
            ->orderBy('id')
            ->chunk(1000, function ($registros) {
                foreach ($registros as $registro) {
                    // 1) Verificar si el registro ya existe en la base de datos local
                    $existeLocal = TipoCultivosPlaga::where('id', $registro->id)->exists();
                    if ($existeLocal) {
                        continue;
                    }
                    // 3) Insertar solamente si la plaga sí existe
                    $dato = new TipoCultivosPlaga();
                    $dato->id             = $registro->id;
                    $dato->tipo_cultivo_id = $registro->especie_id;
                    $dato->plaga_id       = $registro->plaga_id;
                    $dato->created_at     = $registro->created_at;
                    $dato->updated_at     = $registro->updated_at;
                    $dato->timestamps     = false;
                    $dato->save();
                }
            });
        $this->info('Sincronización completada.');
    }
}
