<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ParcelaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('parcelas')->insert([
            ['id' => 1, 'cliente_id' => null, 'nombre' => 'Parcela Norte', 'superficie' => 0, 'status' => 1, 'lat' => null, 'lon' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'cliente_id' => null, 'nombre' => 'Parcela Sur', 'superficie' => 0, 'status' => 1, 'lat' => null, 'lon' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 3, 'cliente_id' => null, 'nombre' => 'Predio de frambuesas', 'superficie' => 100, 'status' => 1, 'lat' => 20.4182573, 'lon' => -103.654543, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 4, 'cliente_id' => null, 'nombre' => 'Predio de zarzamora', 'superficie' => 100, 'status' => 1, 'lat' => null, 'lon' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 5, 'cliente_id' => 5, 'nombre' => 'Invernadero - Estación 1', 'superficie' => 2000, 'status' => 1, 'lat' => 19.761366, 'lon' => -103.52008, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 6, 'cliente_id' => 5, 'nombre' => 'Invernadero - Estación  2', 'superficie' => 2000, 'status' => 1, 'lat' => 19.761366, 'lon' => -103.52008, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 7, 'cliente_id' => 6, 'nombre' => 'Zona dañada', 'superficie' => 1000, 'status' => 0, 'lat' => 20.4182573, 'lon' => -103.654543, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 8, 'cliente_id' => 6, 'nombre' => 'Embalse', 'superficie' => 1000, 'status' => 0, 'lat' => 20.4182573, 'lon' => -103.654543, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 9, 'cliente_id' => 6, 'nombre' => 'Tunas altas', 'superficie' => 1000, 'status' => 1, 'lat' => 20.4182573, 'lon' => -103.65454, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 10, 'cliente_id' => 6, 'nombre' => 'Entrada', 'superficie' => 1000, 'status' => 0, 'lat' => 20.4182573, 'lon' => -103.654543, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 11, 'cliente_id' => 7, 'nombre' => 'Aguacate 5', 'superficie' => 100, 'status' => 1, 'lat' => 19.39277778, 'lon' => 102.14361111, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 12, 'cliente_id' => 7, 'nombre' => 'San Marcos', 'superficie' => 100, 'status' => 1, 'lat' => 19.75055556, 'lon' => 103.69722222, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 13, 'cliente_id' => 8, 'nombre' => 'Jardín Raúl - Maíz', 'superficie' => 100, 'status' => 1, 'lat' => 20.582196, 'lon' => -103.453361, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 14, 'cliente_id' => 8, 'nombre' => 'Jardín Raúl - Arróz', 'superficie' => 100, 'status' => 1, 'lat' => 20.582196, 'lon' => -103.453361, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 15, 'cliente_id' => 8, 'nombre' => 'Jardín Raúl - Frijol', 'superficie' => 100, 'status' => 1, 'lat' => 20.582196, 'lon' => -103.453361, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 16, 'cliente_id' => 8, 'nombre' => 'Jardín Raúl - Trigo', 'superficie' => 100, 'status' => 1, 'lat' => 20.582196, 'lon' => -103.453361, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 17, 'cliente_id' => 4, 'nombre' => 'Prueba predio 1', 'superficie' => 505, 'status' => 1, 'lat' => 20.667250, 'lon' => -103.383081, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 18, 'cliente_id' => null, 'nombre' => 'prueba predio 2', 'superficie' => 60, 'status' => 1, 'lat' => 20.667250, 'lon' => -103.383081, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 19, 'cliente_id' => 10, 'nombre' => 'Casa Miguél', 'superficie' => 5, 'status' => 1, 'lat' => 20.667250, 'lon' => -103.383081, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 20, 'cliente_id' => 10, 'nombre' => 'Santa Anita', 'superficie' => 3, 'status' => 1, 'lat' => 20.563702, 'lon' => -103.464545, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 21, 'cliente_id' => 12, 'nombre' => 'Predio Norman 1', 'superficie' => 1000, 'status' => 1, 'lat' => 20.4182573, 'lon' => -103.65454, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 22, 'cliente_id' => 13, 'nombre' => 'Prueba', 'superficie' => 10, 'status' => 0, 'lat' => 20.629202, 'lon' => -103.9772, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 23, 'cliente_id' => 13, 'nombre' => 'Santo Domingo', 'superficie' => 1000, 'status' => 1, 'lat' => 21.296891, 'lon' => -102.30282, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 24, 'cliente_id' => 13, 'nombre' => 'San Felipe', 'superficie' => 10, 'status' => 1, 'lat' => 21.399679, 'lon' => -102.33832, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 25, 'cliente_id' => 13, 'nombre' => 'Pivote 26', 'superficie' => 10, 'status' => 0, 'lat' => 21.408723, 'lon' => -102.33901, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 26, 'cliente_id' => 7, 'nombre' => 'NPK', 'superficie' => 10, 'status' => 1, 'lat' => 20.789134, 'lon' => -104.20077, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 27, 'cliente_id' => 14, 'nombre' => 'Las Borregas', 'superficie' => 10, 'status' => 0, 'lat' => 20.697090, 'lon' => -102.37254, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 28, 'cliente_id' => 15, 'nombre' => 'dfgfgsgfdg', 'superficie' => 13, 'status' => 1, 'lat' => 21.195671, 'lon' => -104.48498, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 29, 'cliente_id' => 14, 'nombre' => 'Lagunitas', 'superficie' => 2, 'status' => 1, 'lat' => 21.195671, 'lon' => -104.48498, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 30, 'cliente_id' => 14, 'nombre' => 'El Colorín', 'superficie' => 6, 'status' => 1, 'lat' => 20.413430, 'lon' => -102.22417, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 31, 'cliente_id' => 14, 'nombre' => 'El Capulincito', 'superficie' => 30, 'status' => 1, 'lat' => 20.420920, 'lon' => -102.2201, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 32, 'cliente_id' => 14, 'nombre' => 'Pirata', 'superficie' => 10, 'status' => 0, 'lat' => 20.702871, 'lon' => -102.36927, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 33, 'cliente_id' => 13, 'nombre' => 'San Juanico', 'superficie' => 30, 'status' => 1, 'lat' => 21.310765, 'lon' => 102.043891, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 34, 'cliente_id' => 12, 'nombre' => 'Escuela nacional de Lecheria Sustentable', 'superficie' => 5, 'status' => 1, 'lat' => 20.749852, 'lon' => -103.10882, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 35, 'cliente_id' => 12, 'nombre' => 'Alto de Barajas', 'superficie' => 15, 'status' => 1, 'lat' => 21.2103699, 'lon' => -102.39279, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 36, 'cliente_id' => 12, 'nombre' => 'Proan BioAgrofert', 'superficie' => 20, 'status' => 1, 'lat' => 21.2962620, 'lon' => -102.30246, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 37, 'cliente_id' => 12, 'nombre' => 'PROAN Agua Limpia', 'superficie' => 20, 'status' => 1, 'lat' => 21.3929033, 'lon' => -102.34183, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 38, 'cliente_id' => 12, 'nombre' => 'El Bajio PROAN', 'superficie' => 20, 'status' => 0, 'lat' => 21.3929033, 'lon' => -102.34183, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 39, 'cliente_id' => 12, 'nombre' => 'Experimento San Juanico', 'superficie' => 5, 'status' => 1, 'lat' => 21.3080523, 'lon' => -102.0462, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 40, 'cliente_id' => 12, 'nombre' => 'Tepatitlán', 'superficie' => 10, 'status' => 1, 'lat' => 20.8820939, 'lon' => -102.68964, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 41, 'cliente_id' => 12, 'nombre' => 'San Miguel', 'superficie' => 5, 'status' => 1, 'lat' => 20.9872726, 'lon' => -102.36141, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 42, 'cliente_id' => 12, 'nombre' => 'San Julian', 'superficie' => 5, 'status' => 1, 'lat' => 20.9872690, 'lon' => -102.20249, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 43, 'cliente_id' => 17, 'nombre' => 'Rancho San José', 'superficie' => 5, 'status' => 1, 'lat' => 20.4482550, 'lon' => -103.64622, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 44, 'cliente_id' => 14, 'nombre' => 'Loma el Mezquite', 'superficie' => 5, 'status' => 1, 'lat' => 19.836853, 'lon' => -103.59319, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 45, 'cliente_id' => 13, 'nombre' => 'Pivote 19', 'superficie' => 20, 'status' => 1, 'lat' => 21.389789, 'lon' => -102.33988, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 46, 'cliente_id' => 15, 'nombre' => 'Avansys', 'superficie' => 1, 'status' => 1, 'lat' => 20.667211, 'lon' => -103.41378, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 47, 'cliente_id' => 14, 'nombre' => 'Prueba', 'superficie' => 1, 'status' => 1, 'lat' => 22, 'lon' => -102, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
