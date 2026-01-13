<?php

namespace Database\Seeders;

use App\Models\VariablesMedicion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VariablesMedicionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VariablesMedicion::create([
            'nombre' => 'Temperatura atmosférica',
            'slug' => 'temperatura',
            'unidad' => 'ºC',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Humedad relativa atmosférica',
            'slug' => 'humedad_relativa',
            'unidad' => '%',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Precipitación acumulada del día',
            'slug' => 'precipitacion_acumulada',
            'unidad' => 'mm',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Velocidad del viento',
            'slug' => 'velocidad_viento',
            'unidad' => 'm/s',
        ]);
        VariablesMedicion::create([
            'nombre' => 'CO2 atmosférico',
            'slug' => 'co2',
            'unidad' => 'ppm',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Temperatura del suelo',
            'slug' => 'temperatura_suelo',
            'unidad' => '°C',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Humedad del suelo',
            'slug' => 'humedad_15',
            'unidad' => '%',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Conductividad Eléctrica',
            'slug' => 'conductividad_electrica',
            'unidad' => 'Ds/m',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Potencial de hidrógeno',
            'slug' => 'ph',
            'unidad' => 'pH',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Nitrógeno',
            'slug' => 'nit',
            'unidad' => 'ppm',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Fósforo',
            'slug' => 'phos',
            'unidad' => 'ppm',
        ]);
        VariablesMedicion::create([
            'nombre' => 'Potasio',
            'slug' => 'pot',
            'unidad' => 'ppm',
        ]);
    }
}
