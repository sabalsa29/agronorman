<?php

namespace App\Console\Commands;

use App\Models\Clientes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncClientes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-clientes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los clientes desde la base de datos norman_prod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::connection('norman_prod')->table('cliente')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Verifica si el registro ya existe en la base de datos local
                $existe = Clientes::where('id', $registro->id)->exists();

                if (!$existe) {
                    $nuevo = new Clientes();
                    $nuevo->id = $registro->id;
                    $nuevo->nombre = $registro->nombre;
                    $nuevo->empresa = $registro->empresa;
                    $nuevo->ubicacion = $registro->ubicacion;
                    $nuevo->telefono = $registro->telefono;
                    $nuevo->status = $registro->estatus;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización de clientes completada.');
    }
}
