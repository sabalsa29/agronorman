<?php

namespace Database\Seeders;

use App\Models\Indicador;
use App\Models\TipoCultivoEstres;
use App\Models\VariablesMedicion;
use App\Models\TipoCultivos;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IndicadoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener variables de medición existentes
        $temperatura = VariablesMedicion::where('slug', 'temperatura')->first();
        $humedad = VariablesMedicion::where('slug', 'humedad_relativa')->first();
        $co2 = VariablesMedicion::where('slug', 'co2')->first();

        if (!$temperatura || !$humedad || !$co2) {
            $this->command->error('❌ Variables de medición no encontradas. Ejecuta VariablesMedicionSeeder primero.');
            return;
        }

        // Obtener tipos de cultivo existentes
        $tiposCultivo = TipoCultivos::take(3)->get();
        if ($tiposCultivo->isEmpty()) {
            $this->command->error('❌ Tipos de cultivo no encontrados. Ejecuta TipoCultivosSeeder primero.');
            return;
        }

        $this->command->info('🌱 Creando indicadores...');

        // Crear indicadores
        $indicadores = [
            // Temperatura diurna
            [
                'nombre' => 'Temperatura Diurna',
                'variable_id' => $temperatura->id,
                'momento_dia' => 'DIURNO'
            ],
            // Temperatura nocturna
            [
                'nombre' => 'Temperatura Nocturna',
                'variable_id' => $temperatura->id,
                'momento_dia' => 'NOCTURNO'
            ],
            // Humedad diurna
            [
                'nombre' => 'Humedad Diurna',
                'variable_id' => $humedad->id,
                'momento_dia' => 'DIURNO'
            ],
            // Humedad nocturna
            [
                'nombre' => 'Humedad Nocturna',
                'variable_id' => $humedad->id,
                'momento_dia' => 'NOCTURNO'
            ],
            // CO2 diurno
            [
                'nombre' => 'CO2 Diurno',
                'variable_id' => $co2->id,
                'momento_dia' => 'DIURNO'
            ],
            // CO2 nocturno
            [
                'nombre' => 'CO2 Nocturno',
                'variable_id' => $co2->id,
                'momento_dia' => 'NOCTURNO'
            ]
        ];

        foreach ($indicadores as $indicadorData) {
            Indicador::updateOrCreate(
                [
                    'variable_id' => $indicadorData['variable_id'],
                    'momento_dia' => $indicadorData['momento_dia']
                ],
                $indicadorData
            );
        }

        $this->command->info('✅ Indicadores creados');

        $this->command->info('🌱 Creando parámetros de estrés por especie...');

        // Crear parámetros de estrés para cada tipo de cultivo
        foreach ($tiposCultivo as $tipoCultivo) {
            // Temperatura diurna
            TipoCultivoEstres::updateOrCreate(
                [
                    'tipo_cultivo_id' => $tipoCultivo->id,
                    'variable_id' => $temperatura->id,
                    'tipo' => 'DIURNO'
                ],
                [
                    'muy_bajo' => 10,
                    'bajo_min' => 10,
                    'bajo_max' => 15,
                    'optimo_min' => 15,
                    'optimo_max' => 25,
                    'alto_min' => 25,
                    'alto_max' => 30,
                    'muy_alto' => 30
                ]
            );

            // Temperatura nocturna
            TipoCultivoEstres::updateOrCreate(
                [
                    'tipo_cultivo_id' => $tipoCultivo->id,
                    'variable_id' => $temperatura->id,
                    'tipo' => 'NOCTURNO'
                ],
                [
                    'muy_bajo' => 5,
                    'bajo_min' => 5,
                    'bajo_max' => 10,
                    'optimo_min' => 10,
                    'optimo_max' => 20,
                    'alto_min' => 20,
                    'alto_max' => 25,
                    'muy_alto' => 25
                ]
            );

            // Humedad diurna
            TipoCultivoEstres::updateOrCreate(
                [
                    'tipo_cultivo_id' => $tipoCultivo->id,
                    'variable_id' => $humedad->id,
                    'tipo' => 'DIURNO'
                ],
                [
                    'muy_bajo' => 30,
                    'bajo_min' => 30,
                    'bajo_max' => 50,
                    'optimo_min' => 50,
                    'optimo_max' => 70,
                    'alto_min' => 70,
                    'alto_max' => 85,
                    'muy_alto' => 85
                ]
            );

            // Humedad nocturna
            TipoCultivoEstres::updateOrCreate(
                [
                    'tipo_cultivo_id' => $tipoCultivo->id,
                    'variable_id' => $humedad->id,
                    'tipo' => 'NOCTURNO'
                ],
                [
                    'muy_bajo' => 40,
                    'bajo_min' => 40,
                    'bajo_max' => 60,
                    'optimo_min' => 60,
                    'optimo_max' => 80,
                    'alto_min' => 80,
                    'alto_max' => 90,
                    'muy_alto' => 90
                ]
            );

            // CO2 diurno
            TipoCultivoEstres::updateOrCreate(
                [
                    'tipo_cultivo_id' => $tipoCultivo->id,
                    'variable_id' => $co2->id,
                    'tipo' => 'DIURNO'
                ],
                [
                    'muy_bajo' => 200,
                    'bajo_min' => 200,
                    'bajo_max' => 300,
                    'optimo_min' => 300,
                    'optimo_max' => 600,
                    'alto_min' => 600,
                    'alto_max' => 800,
                    'muy_alto' => 800
                ]
            );

            // CO2 nocturno
            TipoCultivoEstres::updateOrCreate(
                [
                    'tipo_cultivo_id' => $tipoCultivo->id,
                    'variable_id' => $co2->id,
                    'tipo' => 'NOCTURNO'
                ],
                [
                    'muy_bajo' => 300,
                    'bajo_min' => 300,
                    'bajo_max' => 400,
                    'optimo_min' => 400,
                    'optimo_max' => 600,
                    'alto_min' => 600,
                    'alto_max' => 800,
                    'muy_alto' => 800
                ]
            );
        }

        $this->command->info('✅ Parámetros de estrés creados');
        $this->command->info('🎉 Seeder completado exitosamente');
    }
}
