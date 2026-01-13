<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente; // Asegúrate de que la ruta del modelo esté correcta

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            [
                'id'        => 4,
                'nombre'    => 'Saúl Ramos Berries',
                'empresa'   => 'Saúl Ramos',
                'ubicacion' => 'Jocotepec',
                'telefono'  => '3331569573',
                'status'    => 0,
            ],
            [
                'id'        => 5,
                'nombre'    => 'DTM CIVAT',
                'empresa'   => 'CIVAT',
                'ubicacion' => 'Ciudad Guzman',
                'telefono'  => '3336161646',
                'status'    => 0,
            ],
            [
                'id'        => 6,
                'nombre'    => 'Grupo Cerritos',
                'empresa'   => 'Cerritos',
                'ubicacion' => 'Ciudad Guzmán',
                'telefono'  => '3336161646',
                'status'    => 0,
            ],
            [
                'id'        => 7,
                'nombre'    => 'Sammu',
                'empresa'   => 'Jesus Francisco Elvira Barragan',
                'ubicacion' => 'Nuevo San Juan Parangaricutiro',
                'telefono'  => '4521491897',
                'status'    => 1,
            ],
            [
                'id'        => 8,
                'nombre'    => 'SADER',
                'empresa'   => 'Raúl M. Trejo Luna',
                'ubicacion' => 'Jardín Raul',
                'telefono'  => '',
                'status'    => 0,
            ],
            [
                'id'        => 10,
                'nombre'    => 'Prueba 1',
                'empresa'   => 'Nombre prueba 1',
                'ubicacion' => 'Prueba ubicacion 1',
                'telefono'  => '123456789',
                'status'    => 0,
            ],
            [
                'id'        => 11,
                'nombre'    => 'Cycasa',
                'empresa'   => 'Miguél Trejo',
                'ubicacion' => 'Planeta 2630',
                'telefono'  => '123456789',
                'status'    => 0,
            ],
            [
                'id'        => 12,
                'nombre'    => 'Demo Norman',
                'empresa'   => 'Demo',
                'ubicacion' => 'N/A',
                'telefono'  => '333333333',
                'status'    => 0,
            ],
            [
                'id'        => 13,
                'nombre'    => 'Proan',
                'empresa'   => 'Proan',
                'ubicacion' => 'Por recibir',
                'telefono'  => '333333333',
                'status'    => 1,
            ],
            [
                'id'        => 14,
                'nombre'    => 'Cooperativa El Fresno',
                'empresa'   => 'Cooperativa El Fresno',
                'ubicacion' => 'Arandas',
                'telefono'  => '3318504387',
                'status'    => 0,
            ],
            [
                'id'        => 15,
                'nombre'    => 'djfnsdajfnaskjf',
                'empresa'   => 'dfasdfasdfasd',
                'ubicacion' => 'dsfasdfasfasf',
                'telefono'  => '3223452345',
                'status'    => 0,
            ],
            [
                'id'        => 16,
                'nombre'    => 'Proyecto Innovación',
                'empresa'   => 'Proyecto Jalisco',
                'ubicacion' => 'San Juan de los Lagos',
                'telefono'  => '333',
                'status'    => 1,
            ],
            [
                'id'        => 17,
                'nombre'    => 'Rancho San José',
                'empresa'   => 'Rancho San José',
                'ubicacion' => '20.448255 - 103.6462280',
                'telefono'  => '3313320997',
                'status'    => 1,
            ],
            [
                'id'        => 18,
                'nombre'    => 'Aguacates Azteca',
                'empresa'   => 'Aguacates Azteca',
                'ubicacion' => 'Sayula',
                'telefono'  => '3312645478',
                'status'    => 0,
            ],
            [
                'id'        => 19,
                'nombre'    => 'NORMAN',
                'empresa'   => 'Prueba de Estaciones',
                'ubicacion' => 'Avansys',
                'telefono'  => '3331256832',
                'status'    => 1,
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::create(array_merge($cliente, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
