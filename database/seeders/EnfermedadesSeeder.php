<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EnfermedadesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            ['nombre' => 'Antracnosis', 'status' => 1],
            ['nombre' => 'Prueba General', 'status' => 1],
            ['nombre' => 'Roya', 'status' => 1],
            ['nombre' => 'Tizón Foliar', 'status' => 1],
            ['nombre' => 'Carbón del Maíz', 'status' => 1],
            ['nombre' => 'Marchitamiento del Maíz', 'status' => 1],
            ['nombre' => 'Podredumbre del tallo', 'status' => 1],
            ['nombre' => 'Botrytis Cinerea', 'status' => 1],
            ['nombre' => 'Xanthomonas Campestris / Mancha angular o bacterial angular', 'status' => 1],
            ['nombre' => 'Oídio o powdery mildew', 'status' => 1],
            ['nombre' => 'Pudrición de la Raíz del Agave', 'status' => 1],
            ['nombre' => 'Marchitez Bacteriana del Agave', 'status' => 1],
            ['nombre' => 'Fusariosis', 'status' => 1],
            ['nombre' => 'Fusariosis', 'status' => 0],
            ['nombre' => 'Manchas Foliares del agave', 'status' => 1],
            ['nombre' => 'Sigatoka Negra', 'status' => 1],
            ['nombre' => 'Mal de Panamá', 'status' => 1],
            ['nombre' => 'Marchitez bacterial / Bacteria Ralstonia solanacearum', 'status' => 1],
            ['nombre' => 'Moko', 'status' => 1],
        ];

        foreach ($data as &$item) {
            $item['slug'] = Str::slug($item['nombre']);
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
        }

        DB::table('enfermedades')->insert($data);
    }
}
