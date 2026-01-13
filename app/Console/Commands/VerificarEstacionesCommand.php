<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Estacion;
use App\Models\EstacionDato;
use Carbon\Carbon;

class VerificarEstacionesCommand extends Command
{
    protected $signature = 'estaciones:verificar {--dias=7}';
    protected $description = 'Verificar estado y actividad de las estaciones';

    public function handle()
    {
        $dias = $this->option('dias');
        $fechaInicio = Carbon::now()->subDays($dias);

        $this->info("Verificando estaciones (últimos {$dias} días)...");
        $this->line('');

        $estaciones = Estacion::with('zonaManejos')->get();

        $tabla = [];
        $headers = ['ID', 'UUID', 'Zonas', 'Último Dato', 'Registros', 'Estado'];

        foreach ($estaciones as $estacion) {
            // Obtener último dato
            $ultimoDato = EstacionDato::where('estacion_id', $estacion->id)
                ->where('created_at', '>=', $fechaInicio)
                ->orderBy('created_at', 'desc')
                ->first();

            // Contar registros en el período
            $registros = EstacionDato::where('estacion_id', $estacion->id)
                ->where('created_at', '>=', $fechaInicio)
                ->count();

            // Determinar estado
            $estado = 'Inactiva';
            if ($registros > 0) {
                $estado = 'Activa';
            } elseif ($ultimoDato) {
                $estado = 'Reciente';
            }

            // Zonas asignadas
            $zonas = $estacion->zonaManejos->pluck('id')->implode(', ');
            if (empty($zonas)) {
                $zonas = 'Sin asignar';
            }

            $tabla[] = [
                $estacion->id,
                $estacion->uuid,
                $zonas,
                $ultimoDato ? $ultimoDato->created_at->format('Y-m-d H:i') : 'N/A',
                $registros,
                $estado
            ];
        }

        $this->table($headers, $tabla);

        // Resumen
        $activas = collect($tabla)->where('Estado', 'Activa')->count();
        $recientes = collect($tabla)->where('Estado', 'Reciente')->count();
        $inactivas = collect($tabla)->where('Estado', 'Inactiva')->count();
        $sinAsignar = collect($tabla)->where('Zonas', 'Sin asignar')->count();

        $this->line('');
        $this->info('Resumen:');
        $this->line("- Activas: {$activas}");
        $this->line("- Recientes: {$recientes}");
        $this->line("- Inactivas: {$inactivas}");
        $this->line("- Sin asignar: {$sinAsignar}");
    }
}
