<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZonaManejoLoteExternoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // VALIDAR QUE NO EXISTA ANTES DE INSERTAR, O LIMPIAR LA TABLA SI ES NECESARIO
        DB::table('zona_manejo_lote_externo')->truncate();
        DB::table('zona_manejo_lote_externo')->insert([
            // Ajusta estos campos a los nombres reales de tus columnas:
            'zona_manejo_id'   => 20,   // <- ID existente en tu tabla zonas
            'externo_lote_id'  => 770,  // <- ID existente del lote externo (o el campo correcto)
            'name'             => 'Zona Manejo 20 - Lote Externo 770', // <- Ajusta este campo si es necesario
            // Si tu tabla maneja timestamps:
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear tabla zona_manejos_tipo_cultivos con las columnas zona_manejo_id, tipo_cultivo_id, created_at, updated_at
         DB::table('zona_manejos_tipo_cultivos')->truncate();
         DB::table('zona_manejos_tipo_cultivos')->insert([
             'zona_manejo_id'   => 20,   // <- ID existente en tu tabla zonas
             'tipo_cultivo_id'  => 1,    // <- ID existente del tipo de cultivo (o el campo correcto)
             'created_at' => now(),
             'updated_at' => now(),
         ]);

         // Crear registros en tipo_cultvos cultivo_id, nombre, status
            DB::table('tipo_cultivos')->truncate();
            DB::table('tipo_cultivos')->insert([
                'cultivo_id' => 7, // <- ID del cultivo
                'nombre' => 'Maiz Premium', // <- Nombre del cultivo
                'status' => 1, // <- Status del cultivo
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // Crear registros en lote_correctivos lote_id, correctivo_id, fecha, cantidad_sugerida, created_at, updated_at
        DB::table('lote_correctivo')->truncate();
        DB::table('lote_correctivo')->insert([
            'lote_id' => 770, // <- ID del lote
            'correctivo_id' => 1, // <- ID del correctivo
            'fecha_aplicacion' => now(), // <- Fecha del correctivo
            'cantidad_sugerida' => 6924.600, // <- Cantidad sugerida del correctivo
            'created_at' => now(),
            'updated_at' => now(),
        ]);
         DB::table('lote_correctivo')->insert([
            'lote_id' => 770, // <- ID del lote
            'correctivo_id' => 2, // <- ID del correctivo
            'fecha_aplicacion' => now(), // <- Fecha del correctivo
            'cantidad_sugerida' => 3068.860, // <- Cantidad sugerida del correctivo
            'created_at' => now(),
            'updated_at' => now(),
        ]);
         DB::table('lote_correctivo')->insert([
            'lote_id' => 770, // <- ID del lote
            'correctivo_id' => 3, // <- ID del correctivo
            'fecha_aplicacion' => now(), // <- Fecha del correctivo
            'cantidad_sugerida' => 12.250, // <- Cantidad sugerida del correctivo
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Crear registros en correctivos , nombre, unidad_medida, efecto_esperado,created_at, updated_at
        DB::table('correctivos')->truncate();
        DB::table('correctivos')->insert([
            'nombre' => 'Sulfato de Magnesio', // <- Nombre del correctivo
            'unidad_medida' => 'kg/ha', // <- Unidad de medida del correctivo
            'efecto_esperado' => 'Mayor produccion de clorofila y mejorar la absorción de otros nutrientes como el fósforo', // <- Efecto esperado del correctivo
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('correctivos')->insert([
            'nombre' => 'Sulfato de Potasio', // <- Nombre del correctivo
            'unidad_medida' => 'kg/ha', // <- Unidad de medida del correctivo
            'efecto_esperado' => 'Proporciona potasio para el desarrollo y azufre para la síntesis de proteínas y enzimas.', // <- Efecto esperado del correctivo
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('correctivos')->insert([
            'nombre' => 'Estiércol vacuno', // <- Nombre del correctivo
            'unidad_medida' => 'ton/ha', // <- Unidad de medida del correctivo
            'efecto_esperado' => 'Mayor resistencia a enfermedades', // <- Efecto esperado del correctivo
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('correctivos')->insert([
            'nombre' => 'Azufre', // <- Nombre del correctivo
            'unidad_medida' => 'kg/ha', // <- Unidad de medida del correctivo
            'efecto_esperado' => 'Mejora de la absorción de nutrientes', // <- Efecto esperado del correctivo
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('correctivos')->insert([
            'nombre' => 'Calcio', // <- Nombre del correctivo
            'unidad_medida' => 'kg/ha', // <- Unidad de medida del correctivo
            'efecto_esperado' => 'Mejora de la estructura del suelo', // <- Efecto esperado del correctivo
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
