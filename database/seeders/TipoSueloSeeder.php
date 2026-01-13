<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoSueloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_suelos')->insert([
            [
                'tipo_suelo'  => 'Arcilla (%)',
                'bajo'      => 28,
                'optimo_min'  => 38,
                'optimo_max'  => 43,
                'alto'      => 46,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Arcillo-Limoso (%)',
                'bajo'      => 41,
                'optimo_min'  => 25.9,
                'optimo_max'  => 38,
                'alto'      => 41,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Arcillo-Arenoso (%)',
                'bajo'      => 23,
                'optimo_min'  => 27,
                'optimo_max'  => 36,
                'alto'      => 38,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Franco Arcillo Arenoso (%)',
                'bajo'      => 23,
                'optimo_min'  => 29,
                'optimo_max'  => 37,
                'alto'      => 40,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Franco Arcillo Limoso (%)',
                'bajo'      => 23,
                'optimo_min'  => 30,
                'optimo_max'  => 41,
                'alto'      => 43,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Franco Arcilloso (%)',
                'bajo'      => 22,
                'optimo_min'  => 28,
                'optimo_max'  => 33,
                'alto'      => 35,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Franco Limoso (%)',
                'bajo'      => 18,
                'optimo_min'  => 20,
                'optimo_max'  => 26,
                'alto'      => 32,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Franco Arenoso (%)',
                'bajo'      => 12,
                'optimo_min'  => 16,
                'optimo_max'  => 23,
                'alto'      => 30,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Limo (%)',
                'bajo'      => 13,
                'optimo_min'  => 18,
                'optimo_max'  => 26,
                'alto'      => 28,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Franco (%)',
                'bajo'      => 11,
                'optimo_min'  => 23,
                'optimo_max'  => 26,
                'alto'      => 26,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Areno Franco (%)',
                'bajo'      => 12,
                'optimo_min'  => 14.4,
                'optimo_max'  => 18.6,
                'alto'      => 23,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'tipo_suelo'  => 'Arena (%)',
                'bajo'      => 9.5,
                'optimo_min'  => 11.6,
                'optimo_max'  => 15.2,
                'alto'      => 19,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
