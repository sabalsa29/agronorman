<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\VariablesMedicion;
use App\Models\EstacionVariable;

class SyncEstacionVariablesMedicion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-estacion-variables-medicion';

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
        DB::connection('norman_prod')->table('inventario_estacion')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {
                // Procesar lista de nombres separados por comas
                if (isset($registro->variables)) {
                    $names = explode(',', $registro->variables);
                    foreach ($names as $name) {
                        $name = trim($name);
                        if (empty($name)) {
                            continue;
                        }
                        $var = VariablesMedicion::where('slug', $name)->first();
                        if ($var) {
                            EstacionVariable::firstOrCreate([
                                'estacion_id' => $registro->id,
                                'variables_medicion_id' => $var->id,
                            ]);
                        } else {
                            $this->warn("Variable no encontrada: {$name}");
                        }
                    }
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
