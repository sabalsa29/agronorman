<?php

namespace App\Console\Commands;

use App\Models\Estaciones;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEstaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-estaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las estaciones desde la base de datos norman_prod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de estaciones...');

        // Obtener registros de la base de datos norman_prod
        $estaciones = DB::connection('norman_prod')
            ->table('inventario_estacion')
            ->get();

        $bar = $this->output->createProgressBar(count($estaciones));
        $bar->start();

        foreach ($estaciones as $estacion) {
            // Verificar si la estación ya existe
            $estacionExistente = Estaciones::where('uuid', $estacion->uuid)->first();

            if (!$estacionExistente) {
                // Crear nueva estación
                Estaciones::create([
                    'id' => $estacion->id,
                    'uuid' => $estacion->uuid,
                    'tipo_estacion_id' => $estacion->tipo_estacion_id,
                    'cliente_id' => $estacion->cliente_id,
                    'fabricante_id' => $estacion->fabricante_id,
                    'almacen_id' => $estacion->almacen_id,
                    'celular' => $estacion->celular,
                    'caracteristicas' => $estacion->caracteristicas,
                    'status' => $estacion->status,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sincronización de estaciones completada.');
    }
}
