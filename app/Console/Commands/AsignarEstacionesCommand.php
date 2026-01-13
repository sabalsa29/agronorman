<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZonaManejos;
use App\Models\Estacion;
use Illuminate\Support\Facades\DB;

class AsignarEstacionesCommand extends Command
{
    protected $signature = 'estaciones:asignar {--zona=} {--estacion=} {--auto}';
    protected $description = 'Asignar estaciones a zonas de manejo';

    public function handle()
    {
        if ($this->option('auto')) {
            $this->asignacionAutomatica();
        } elseif ($this->option('zona') && $this->option('estacion')) {
            $this->asignacionManual();
        } else {
            $this->mostrarOpciones();
        }
    }

    private function mostrarOpciones()
    {
        $this->info('Opciones disponibles:');
        $this->line('1. Asignación automática: php artisan estaciones:asignar --auto');
        $this->line('2. Asignación manual: php artisan estaciones:asignar --zona=ID --estacion=ID');
        $this->line('');

        $this->info('Zonas sin estaciones:');
        $zonasSinEstaciones = ZonaManejos::whereDoesntHave('estaciones')->get();
        foreach ($zonasSinEstaciones as $zona) {
            $this->line("- Zona {$zona->id}: {$zona->nombre}");
        }

        $this->line('');
        $this->info('Estaciones disponibles:');
        $estacionesDisponibles = Estacion::whereDoesntHave('zonaManejos')->get();
        foreach ($estacionesDisponibles as $estacion) {
            $this->line("- Estación {$estacion->id}: {$estacion->uuid}");
        }
    }

    private function asignacionAutomatica()
    {
        $this->info('Iniciando asignación automática...');

        $zonasSinEstaciones = ZonaManejos::whereDoesntHave('estaciones')->get();
        $estacionesDisponibles = Estacion::whereDoesntHave('zonaManejos')->get();

        if ($zonasSinEstaciones->isEmpty()) {
            $this->info('No hay zonas sin estaciones asignadas.');
            return;
        }

        if ($estacionesDisponibles->isEmpty()) {
            $this->error('No hay estaciones disponibles para asignar.');
            return;
        }

        $asignadas = 0;
        foreach ($zonasSinEstaciones as $zona) {
            if ($estacionesDisponibles->isNotEmpty()) {
                $estacion = $estacionesDisponibles->shift();
                $zona->estaciones()->attach($estacion->id);
                $this->info("Asignada estación {$estacion->id} a zona {$zona->id} ({$zona->nombre})");
                $asignadas++;
            }
        }

        $this->info("Se asignaron {$asignadas} estaciones automáticamente.");
    }

    private function asignacionManual()
    {
        $zonaId = $this->option('zona');
        $estacionId = $this->option('estacion');

        $zona = ZonaManejos::find($zonaId);
        $estacion = Estacion::find($estacionId);

        if (!$zona) {
            $this->error("Zona {$zonaId} no encontrada.");
            return;
        }

        if (!$estacion) {
            $this->error("Estación {$estacionId} no encontrada.");
            return;
        }

        // Verificar si la estación ya está asignada
        if ($estacion->zonaManejos()->exists()) {
            $this->error("La estación {$estacionId} ya está asignada a otra zona.");
            return;
        }

        $zona->estaciones()->attach($estacionId);
        $this->info("Estación {$estacionId} asignada exitosamente a zona {$zonaId} ({$zona->nombre})");
    }
}
