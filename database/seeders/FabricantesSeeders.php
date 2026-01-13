<?php

namespace Database\Seeders;

use App\Models\Fabricante;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FabricantesSeeders extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Fabricante::create([
            'nombre' => 'Agrosphere',
            'status' => false,
        ]);

        Fabricante::create([
            'nombre' => 'S4IoT',
            'status' => true,
        ]);
    }
}
