<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GrupoGenesisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Se agrega validacion para no duplicar el grupo genesis con updateOrInsert
        //Este grupo es el que tiene permisos de superadministrador universal
        //Siempre debe existir uno solo, con is_root = true.

        DB::table('grupos')->updateOrInsert(
            ['nombre' => 'Norman', 'grupo_id' => null],
            ['status' => true, 'updated_at' => now(), 'created_at' => now(), 'deleted_at' => null, 'is_root' => true]
        );
    }
}
