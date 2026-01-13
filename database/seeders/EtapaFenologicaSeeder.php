<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EtapaFenologicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('etapa_fenologicas')->insert([
            ['nombre' => 'Genérica', 'estacionalidad' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Vegetativa-defolación', 'estacionalidad' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Floración', 'estacionalidad' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Cuajado de Fruto', 'estacionalidad' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Crecimiento de Fruto', 'estacionalidad' => 1, 'status' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'V1', 'estacionalidad' => null, 'status' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Vegetativa - inicio de floración', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Inicio de floración - fructificación', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Inicio de floración - fructificación plena', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Fructificación plena - Postcosecha', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Vegetativa-defolación', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Crecimiento de fruto', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Cosecha', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Postcosecha', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Vegetativa (VE - V19)', 'estacionalidad' => null, 'status' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Floración (VT -R1)', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Cuajado de Fruto (R2 -R3)', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Crecimiento de Fruto (R4 -R6)', 'estacionalidad' => null, 'status' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Transplante', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Va1', 'estacionalidad' => null, 'status' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Floración - Inicio Fructificación', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Inicio Fructificación - Cosecha', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Vegetativa - Antes de defoliación', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Después de defolación - Brotación', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Floración - Fructificación', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Vegetativa 10 hojas', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'V6', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'V8', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Va1', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Va2', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Va3', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Va4', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Va5', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Va6', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'V4', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'V12', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Brotes', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Hojas', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Floración', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Cuajado', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Envero', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Vendimia', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Germinación', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Desarrollo Vegetativo', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Floración', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Cuajado del Chile', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Desarrollo del fruto del chile', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Madurez y cosecha', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Plantula', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Crecimiento vegetativo', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Plantación', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'VE-V6', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'V7-V12', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'V13-VT', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'R1-R2', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'R3-R4', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'R5-R6', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Prueba', 'estacionalidad' => null, 'status' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'pruebas', 'estacionalidad' => null, 'status' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Prueba', 'estacionalidad' => null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
