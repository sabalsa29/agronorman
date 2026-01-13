<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('roles')->insert([
            [
                'nombre' => 'Super Administrador',
                'descripcion' => 'Acceso completo a todo el sistema',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso a módulos administrativos principales',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Cliente',
                'descripcion' => 'Acceso limitado a su propia información',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
