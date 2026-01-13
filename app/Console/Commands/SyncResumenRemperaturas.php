<?php

namespace App\Console\Commands;

use App\Models\ResumenTemperaturas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncResumenRemperaturas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-resumen-remperaturas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza el resumen de temperaturas desde la base de datos pia_dev';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de resumen de temperaturas...');

        $total = DB::connection('pia_dev')
            ->table('resumen_temperaturas')
            ->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $batch = [];
        $batchSize = 1000;

        DB::connection('pia_dev')
            ->table('resumen_temperaturas')
            ->orderBy('id')
            ->chunk(1000, function ($registros) use (&$batch, $batchSize, $bar) {
                foreach ($registros as $registro) {
                    $batch[] = [
                        'id' => $registro->id,
                        'zona_manejo_id' => $registro->zonamanejo_id,
                        'fecha' => $registro->fecha,
                        'max_nocturna' => $registro->max_nocturna,
                        'min_nocturna' => $registro->min_nocturna,
                        'amp_nocturna' => $registro->amp_nocturna,
                        'max_diurna' => $registro->max_diurna,
                        'min_diurna' => $registro->min_diurna,
                        'amp_diurna' => $registro->amp_diurna,
                        'max' => $registro->max,
                        'min' => $registro->min,
                        'amp' => $registro->amp,
                        'uc' => $registro->uc,
                        'uf' => $registro->uf,
                    ];
                    $bar->advance();

                    if (count($batch) >= $batchSize) {
                        DB::table('resumen_temperaturas')->upsert(
                            $batch,
                            ['id'],
                            [
                                'zona_manejo_id',
                                'fecha',
                                'max_nocturna',
                                'min_nocturna',
                                'amp_nocturna',
                                'max_diurna',
                                'min_diurna',
                                'amp_diurna',
                                'max',
                                'min',
                                'amp',
                                'uc',
                                'uf'
                            ]
                        );
                        $batch = [];
                    }
                }
            });

        // Inserta/actualiza cualquier remanente
        if (count($batch) > 0) {
            DB::table('resumen_temperaturas')->upsert(
                $batch,
                ['id'],
                [
                    'zona_manejo_id',
                    'fecha',
                    'max_nocturna',
                    'min_nocturna',
                    'amp_nocturna',
                    'max_diurna',
                    'min_diurna',
                    'amp_diurna',
                    'max',
                    'min',
                    'amp',
                    'uc',
                    'uf'
                ]
            );
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Sincronización completada.');
        $this->info("Total de registros procesados: {$total}");
    }
}
