<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ConfiguracionMqttUsuario;
use Illuminate\Support\Facades\Hash;

class ConfiguracionMqttUsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario por defecto (cambiar la contraseña en producción)
        ConfiguracionMqttUsuario::firstOrCreate(
            ['username' => 'mqtt_admin'],
            [
                'password' => Hash::make('mqtt_config_2024'),
                'activo' => true,
            ]
        );
    }
}
