<?php

namespace App\Console\Commands;

use App\Models\TipoCultivosEnfermedad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTipoCultivosEnfermedades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'php';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las relaciones entre tipos de cultivo y enfermedades desde la base de datos norman_prod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de relaciones entre tipos de cultivo y enfermedades...');

        // Obtener el total de registros para la barra de progreso
        $total = DB::connection('norman_prod')
            ->table('especie_enfermedad')
            ->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $created = 0;
        $updated = 0;

        DB::connection('norman_prod')
            ->table('especie_enfermedad')
            ->orderBy('id')
            ->chunk(1000, function ($registros) use ($bar, &$created, &$updated) {
                foreach ($registros as $registro) {
                    try {
                        $existe = TipoCultivosEnfermedad::where('id', $registro->id)->first();

                        if ($existe) {
                            // Actualizar registro existente
                            $existe->update([
                                'id' => $registro->id,
                                'tipo_cultivo_id' => $registro->especie_id,
                                'enfermedad_id' => $registro->enfermedad_id,
                                'riesgo_humedad' => $registro->riesgo_humedad,
                                'riesgo_humedad_max' => $registro->riesgo_humedad_max,
                                'riesgo_temperatura' => $registro->riesgo_temperatura,
                                'riesgo_temperatura_max' => $registro->riesgo_temperatura_max,
                                'riesgo_medio' => $registro->riesgo_medio,
                                'riesgo_mediciones' => $registro->riesgo_mediciones,
                            ]);
                            $updated++;
                        } else {
                            // Crear nuevo registro
                            TipoCultivosEnfermedad::create([
                                'id' => $registro->id,
                                'tipo_cultivo_id' => $registro->especie_id,
                                'enfermedad_id' => $registro->enfermedad_id,
                                'riesgo_humedad' => $registro->riesgo_humedad,
                                'riesgo_humedad_max' => $registro->riesgo_humedad_max,
                                'riesgo_temperatura' => $registro->riesgo_temperatura,
                                'riesgo_temperatura_max' => $registro->riesgo_temperatura_max,
                                'riesgo_medio' => $registro->riesgo_medio,
                                'riesgo_mediciones' => $registro->riesgo_mediciones,
                            ]);
                            $created++;
                        }
                    } catch (\Exception $e) {
                        $this->error("Error al procesar registro ID {$registro->id}: " . $e->getMessage());
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info('Sincronización completada.');
        $this->info("Relaciones creadas: {$created}");
        $this->info("Relaciones actualizadas: {$updated}");
        $this->info("Total de registros procesados: {$total}");
    }
}
