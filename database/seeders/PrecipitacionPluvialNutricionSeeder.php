<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EtapaFenologicaTipoCultivo;
use App\Models\NutricionEtapaFenologicaTipoCultivo;

class PrecipitacionPluvialNutricionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todas las etapas fenológicas de tipos de cultivo
        $etapasFenologicasTipoCultivo = EtapaFenologicaTipoCultivo::all();

        foreach ($etapasFenologicasTipoCultivo as $etapa) {
            // Crear parámetros nutricionales para precipitación pluvial
            // Los rangos son en mm/hora
            NutricionEtapaFenologicaTipoCultivo::updateOrCreate(
                [
                    'etapa_fenologica_tipo_cultivo_id' => $etapa->id,
                    'variable' => 'precipitacion_pluvial'
                ],
                [
                    'min' => 0,           // Muy bajo: 0 mm/h
                    'optimo_min' => 0.1,  // Bajo: 0.1-2 mm/h
                    'optimo_max' => 2,    // Óptimo: 2-5 mm/h
                    'max' => 5            // Alto: 5-10 mm/h, Muy alto: >10 mm/h
                ]
            );
        }

        $this->command->info('Parámetros nutricionales de precipitación pluvial creados para todas las etapas fenológicas.');
    }
}
