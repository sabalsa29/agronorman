<?php

namespace App\Console\Commands;

use App\Models\ZonaManejos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncZonaManejos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-zona-manejos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las zonas de manejo desde la base de datos norman_prod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de zonas de manejo...');

        // Obtener registros de la base de datos norman_prod
        $zonasManejo = DB::connection('norman_prod')
            ->table('estacion_virtual')
            ->get();

        $total = count($zonasManejo);
        $this->info("Total de zonas de manejo a sincronizar: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->start();

        $creados = 0;
        $actualizados = 0;

        foreach ($zonasManejo as $zonaManejo) {
            // Verificar si la zona de manejo ya existe
            $zonaManejoExistente = ZonaManejos::where('id', $zonaManejo->id)->first();

            if (!$zonaManejoExistente) {
                // Crear nueva zona de manejo
                ZonaManejos::create([
                    'id' => $zonaManejo->id,
                    'parcela_id' => $zonaManejo->parcela_id,
                    'tipo_suelo_id' => $zonaManejo->tipo_suelo_id,
                    'nombre' => $zonaManejo->nombre,
                    'fecha_inicial_uca' => $zonaManejo->fecha_inicial_uca,
                    'temp_base_calor' => $zonaManejo->temp_base_calor,
                    'edad_cultivo' => $zonaManejo->edad_cultivo,
                    'fecha_siembra' => $zonaManejo->fecha_siembra,
                    'objetivo_produccion' => $zonaManejo->objetivo_produccion,
                    'status' => $zonaManejo->estatus,
                ]);
                $creados++;
            } else {
                // Actualizar zona de manejo existente
                $zonaManejoExistente->update([
                    'parcela_id' => $zonaManejo->parcela_id,
                    'tipo_suelo_id' => $zonaManejo->tipo_suelo_id,
                    'nombre' => $zonaManejo->nombre,
                    'fecha_inicial_uca' => $zonaManejo->fecha_inicial_uca,
                    'temp_base_calor' => $zonaManejo->temp_base_calor,
                    'edad_cultivo' => $zonaManejo->edad_cultivo,
                    'fecha_siembra' => $zonaManejo->fecha_siembra,
                    'objetivo_produccion' => $zonaManejo->objetivo_produccion,
                    'status' => $zonaManejo->estatus,
                ]);
                $actualizados++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Resumen de la sincronización:');
        $this->info("- Zonas de manejo creadas: {$creados}");
        $this->info("- Zonas de manejo actualizadas: {$actualizados}");
        $this->info("- Total procesado: {$total}");
        $this->newLine();
        $this->info('Sincronización de zonas de manejo completada.');
    }
}
