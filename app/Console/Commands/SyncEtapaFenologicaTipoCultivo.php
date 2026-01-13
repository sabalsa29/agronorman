<?php

namespace App\Console\Commands;

use App\Models\EtapaFenologicaTipoCultivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEtapaFenologicaTipoCultivo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-etapa-fenologica-tipo-cultivo';

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
        DB::connection('norman_prod')->table('especie_etapa')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                $existe = EtapaFenologicaTipoCultivo::where('id', $registro->id)->exists();
                $tipoCultivoExiste = DB::table('tipo_cultivos')->where('id', $registro->especie_id)->exists();
                $etapaExiste = DB::table('etapa_fenologicas')->where('id', $registro->etapa_id)->exists();

                if (!$existe && $tipoCultivoExiste && $etapaExiste) {
                    $nuevo = new EtapaFenologicaTipoCultivo();
                    $nuevo->id = $registro->id;
                    $nuevo->tipo_cultivo_id = $registro->especie_id;
                    $nuevo->etapa_fenologica_id = $registro->etapa_id;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
