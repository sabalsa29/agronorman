<?php

namespace App\Console\Commands;

use App\Models\Fabricante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncFabricantes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-fabricantes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los fabricantes desde la base de datos norman_prod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de fabricantes...');

        // Obtener registros de la base de datos norman_prod
        $fabricantes = DB::connection('norman_prod')
            ->table('fabricante')
            ->get();

        $bar = $this->output->createProgressBar(count($fabricantes));
        $bar->start();

        foreach ($fabricantes as $fabricante) {
            // Verificar si el fabricante ya existe
            $fabricanteExistente = Fabricante::where('nombre', $fabricante->nombre)->first();

            if (!$fabricanteExistente) {
                // Crear nuevo fabricante
                Fabricante::create([
                    'id' => $fabricante->id,
                    'nombre' => $fabricante->nombre,
                    'status' => $fabricante->estatus,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sincronización de fabricantes completada.');
    }
}
