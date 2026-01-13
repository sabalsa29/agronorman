<?php

namespace Database\Seeders;

use App\Models\TipoEstacion;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TipoEstacionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        TipoEstacion::insert([
            [
                'nombre' => 'Atmosférica',
                'status' => true,
                'tipo_nasa' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Terrestre',
                'status' => true,
                'tipo_nasa' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Nasa',
                'status' => true,
                'tipo_nasa' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'NPK',
                'status' => true,
                'tipo_nasa' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
