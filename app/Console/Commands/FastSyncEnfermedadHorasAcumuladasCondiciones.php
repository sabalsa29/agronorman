<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FastSyncEnfermedadHorasAcumuladasCondiciones extends Command
{
    protected $signature = 'fast-sync:enfermedad-horas-acumuladas {--truncate : Limpiar tabla antes de sincronizar}';
    protected $description = 'Sincronización rápida usando SQL directo';

    public function handle()
    {
        $this->info('Iniciando sincronización rápida...');

        try {
            // Verificar conexiones
            $piaDevCount = DB::connection('pia_dev')
                ->table('enfermedad_horas_acumuladas_condiciones')
                ->count();

            $awsCount = DB::connection('aws')
                ->table('enfermedad_horas_acumuladas_condiciones')
                ->count();

            $this->info("Registros en pia_dev: {$piaDevCount}");
            $this->info("Registros en aws: {$awsCount}");

            if ($this->option('truncate')) {
                $this->info('Limpiando tabla en AWS...');
                DB::connection('aws')
                    ->table('enfermedad_horas_acumuladas_condiciones')
                    ->truncate();
            }

            $this->info('Ejecutando sincronización optimizada...');

            $chunkSize = 5000;
            $totalInsertados = 0;
            $chunkCount = 0;

            // Procesar en chunks para evitar problemas de memoria
            DB::connection('pia_dev')
                ->table('enfermedad_horas_acumuladas_condiciones')
                ->orderBy('id')
                ->chunk($chunkSize, function ($registros) use (&$totalInsertados, &$chunkCount) {
                    $datosParaInsertar = [];

                    foreach ($registros as $registro) {
                        $datosParaInsertar[] = [
                            'id' => $registro->id,
                            'fecha' => $registro->fecha,
                            'minutos' => $registro->minutos,
                            'tipo_cultivo_id' => $registro->especie_id,
                            'enfermedad_id' => $registro->enfermedad_id,
                            'estacion_id' => $registro->estacion_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }

                    // Insertar chunk en AWS (ignorar duplicados)
                    try {
                        $inserted = DB::connection('aws')
                            ->table('enfermedad_horas_acumuladas_condiciones')
                            ->insertOrIgnore($datosParaInsertar);

                        $this->info("Chunk {$chunkCount}: Intentados " . count($datosParaInsertar) . ", Insertados: {$inserted}");
                        $totalInsertados += $inserted;
                    } catch (\Exception $e) {
                        $this->warn("Error en chunk {$chunkCount}: " . $e->getMessage());
                        // Continuar con el siguiente chunk
                    }

                    $chunkCount++;

                    // Limpiar memoria
                    unset($datosParaInsertar);
                    gc_collect_cycles();
                });

            $newAwsCount = DB::connection('aws')
                ->table('enfermedad_horas_acumuladas_condiciones')
                ->count();

            $this->info("Sincronización completada!");
            $this->info("Nuevos registros en AWS: {$newAwsCount}");

            Log::info('FastSyncEnfermedadHorasAcumuladasCondiciones completado', [
                'registros_sincronizados' => $newAwsCount - $awsCount,
                'total_aws' => $newAwsCount
            ]);
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Error en FastSyncEnfermedadHorasAcumuladasCondiciones', [
                'error' => $e->getMessage()
            ]);
            return 1;
        }

        return 0;
    }
}
