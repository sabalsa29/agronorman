<?php

namespace App\Console\Commands;

ini_set('memory_limit', '4G');

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncEnfermedadHorasAcumuladasCondiciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:enfermedad-horas-acumuladas 
                            {--batch-size=1000 : Tamaño del lote para procesamiento}
                            {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza datos de enfermedad_horas_acumuladas_condiciones desde pia_dev a aws';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de enfermedad_horas_acumuladas_condiciones...');

        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: No se realizarán cambios en la base de datos');
        }

        try {
            // Verificar conexiones
            $this->info('Verificando conexiones a bases de datos...');

            // Probar conexión a pia_dev
            $piaDevCount = DB::connection('pia_dev')
                ->table('enfermedad_horas_acumuladas_condiciones')
                ->count();
            $this->info("Conexión a pia_dev OK. Total registros: {$piaDevCount}");

            // Probar conexión a aws
            $awsCount = DB::connection('aws')
                ->table('enfermedad_horas_acumuladas_condiciones')
                ->count();
            $this->info("Conexión a aws OK. Total registros: {$awsCount}");

            // Obtener el total de registros para la barra de progreso
            $total = $piaDevCount;

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $insertados = 0;
            $actualizados = 0;
            $errores = 0;
            $batch = [];
            // Usar el batchSize del parámetro, no hardcodear 1000

            // Usar chunk() en lugar de while para mejor rendimiento
            $this->info("Iniciando procesamiento con chunk de {$batchSize} registros...");
            DB::connection('pia_dev')
                ->table('enfermedad_horas_acumuladas_condiciones')
                ->orderBy('id')
                ->chunk($batchSize, function ($registros) use (&$insertados, &$actualizados, &$errores, &$batch, $batchSize, $bar, $dryRun) {
                    $this->info("Procesando chunk de " . $registros->count() . " registros...");
                    foreach ($registros as $registro) {
                        try {
                            // Preparar para inserción en lote (asumiendo que la tabla en AWS está vacía)
                            $batch[] = [
                                'id' => $registro->id,
                                'fecha' => $registro->fecha,
                                'minutos' => $registro->minutos,
                                'tipo_cultivo_id' => $registro->especie_id,
                                'enfermedad_id' => $registro->enfermedad_id,
                                'estacion_id' => $registro->estacion_id,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            // Bulk insert cada $batchSize
                            if (count($batch) >= $batchSize) {
                                $this->info("Insertando lote de " . count($batch) . " registros...");
                                if (!$dryRun) {
                                    $inserted = DB::connection('aws')
                                        ->table('enfermedad_horas_acumuladas_condiciones')
                                        ->insert($batch);
                                    $insertados += $inserted;
                                    $this->info("Insertados {$inserted} registros en AWS");
                                } else {
                                    $insertados += count($batch);
                                    $this->info("DRY-RUN: Se insertarían " . count($batch) . " registros");
                                }
                                $batch = [];
                            }

                            $bar->advance();
                        } catch (\Exception $e) {
                            $errores++;
                            $this->error("Error procesando registro ID {$registro->id}: " . $e->getMessage());
                            $bar->advance();
                        }
                    }
                });

            // Inserta cualquier remanente
            if (count($batch) > 0) {
                if (!$dryRun) {
                    $inserted = DB::connection('aws')
                        ->table('enfermedad_horas_acumuladas_condiciones')
                        ->insert($batch);
                    $insertados += $inserted;
                } else {
                    $insertados += count($batch);
                }
            }

            $bar->finish();
            $this->newLine(2);

            // Mostrar resumen final
            $this->info('Resumen de sincronización:');
            $this->info("- Total registros procesados: {$total}");
            $this->info("- Registros insertados: {$insertados}");
            $this->info("- Registros actualizados: {$actualizados}");
            $this->info("- Errores: {$errores}");

            if ($dryRun) {
                $this->warn('MODO DRY-RUN: No se realizaron cambios reales en la base de datos');
            } else {
                $this->info('Sincronización completada exitosamente');
            }

            Log::info('SyncEnfermedadHorasAcumuladasCondiciones ejecutado', [
                'insertados' => $insertados,
                'actualizados' => $actualizados,
                'errores' => $errores,
                'total' => $total,
                'timestamp' => now()
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error durante la sincronización: ' . $e->getMessage());
            Log::error('Error en SyncEnfermedadHorasAcumuladasCondiciones', [
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);
            return 1;
        }
    }
}
