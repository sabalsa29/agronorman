<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CultivoPlagaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('cultivo_plaga')->insert([
            ['cultivo_id' => 8, 'plaga_id' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 8, 'plaga_id' => 11, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 7, 'plaga_id' => 13, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 9, 'plaga_id' => 13, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 10, 'plaga_id' => 13, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 11, 'plaga_id' => 13, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 8, 'plaga_id' => 12, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 7, 'plaga_id' => 14, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 9, 'plaga_id' => 14, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 10, 'plaga_id' => 14, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 11, 'plaga_id' => 14, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 12, 'plaga_id' => 17, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 13, 'plaga_id' => 17, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 14, 'plaga_id' => 17, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 15, 'plaga_id' => 17, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 16, 'plaga_id' => 17, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 12, 'plaga_id' => 16, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 13, 'plaga_id' => 16, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 14, 'plaga_id' => 16, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 16, 'plaga_id' => 16, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 15, 'plaga_id' => 19, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 14, 'plaga_id' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 15, 'plaga_id' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 17, 'plaga_id' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 13, 'plaga_id' => 21, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 16, 'plaga_id' => 21, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 7, 'plaga_id' => 23, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 9, 'plaga_id' => 23, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 10, 'plaga_id' => 23, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 11, 'plaga_id' => 23, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 7, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 9, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 10, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 11, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 8, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 8, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 8, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 9, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 10, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 11, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 12, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 8, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 8, 'plaga_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 21, 'plaga_id' => 24, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 21, 'plaga_id' => 23, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 21, 'plaga_id' => 23, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 7, 'plaga_id' => 18, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 9, 'plaga_id' => 18, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 10, 'plaga_id' => 18, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 12, 'plaga_id' => 18, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 7, 'plaga_id' => 22, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 9, 'plaga_id' => 22, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 10, 'plaga_id' => 22, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 11, 'plaga_id' => 22, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 13, 'plaga_id' => 15, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 14, 'plaga_id' => 15, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 17, 'plaga_id' => 15, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 21, 'plaga_id' => 23, 'created_at' => $now, 'updated_at' => $now],
            ['cultivo_id' => 7, 'plaga_id' => 20, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
