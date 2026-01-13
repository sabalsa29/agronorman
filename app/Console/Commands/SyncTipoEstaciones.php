<?php

namespace App\Console\Commands;

use App\Models\TipoEstacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTipoEstaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-tipo-estaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los tipos de estación desde la base de datos norman_prod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de tipos de estación...');

        // Obtener registros de la base de datos norman_prod
        $tiposEstacion = DB::connection('norman_prod')
            ->table('tipo_estacion')
            ->get();

        $bar = $this->output->createProgressBar(count($tiposEstacion));
        $bar->start();

        foreach ($tiposEstacion as $tipoEstacion) {
            // Verificar si el tipo de estación ya existe
            $tipoEstacionExistente = TipoEstacion::where('nombre', $tipoEstacion->nombre)->first();

            if (!$tipoEstacionExistente) {
                // Crear nuevo tipo de estación
                TipoEstacion::create([
                    'id' => $tipoEstacion->id,
                    'nombre' => $tipoEstacion->nombre,
                    'tipo_nasa' => $tipoEstacion->tipo_nasa,
                    'status' => $tipoEstacion->estatus,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sincronización de tipos de estación completada.');
    }
}
