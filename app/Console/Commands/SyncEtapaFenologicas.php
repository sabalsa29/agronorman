<?php

namespace App\Console\Commands;

use App\Models\EtapaFenologica;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEtapaFenologicas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-etapa-fenologicas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las etapas fenológicas desde la base de datos norman_prod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de etapas fenológicas...');

        // Obtener registros de la base de datos norman_prod
        $etapasFenologicas = DB::connection('norman_prod')
            ->table('etapa_fenologica')
            ->get();

        $bar = $this->output->createProgressBar(count($etapasFenologicas));
        $bar->start();

        $created = 0;
        $updated = 0;

        foreach ($etapasFenologicas as $etapa) {
            try {
                $existe = EtapaFenologica::where('id', $etapa->id)->first();

                if ($existe) {
                    // Actualizar registro existente
                    $existe->update([
                        'id' => $etapa->id,
                        'nombre' => $etapa->nombre,
                        'estacionalidad' => $etapa->estacionalidad,
                        'status' => $etapa->estatus,
                    ]);
                    $updated++;
                } else {
                    // Crear nuevo registro
                    EtapaFenologica::create([
                        'id' => $etapa->id,
                        'nombre' => $etapa->nombre,
                        'estacionalidad' => $etapa->estacionalidad,
                        'status' => $etapa->estatus,
                    ]);
                    $created++;
                }
            } catch (\Exception $e) {
                $this->error("Error al procesar etapa fenológica ID {$etapa->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Sincronización completada.');
        $this->info("Etapas fenológicas creadas: {$created}");
        $this->info("Etapas fenológicas actualizadas: {$updated}");
        $this->info("Total de registros procesados: " . count($etapasFenologicas));
    }
}
