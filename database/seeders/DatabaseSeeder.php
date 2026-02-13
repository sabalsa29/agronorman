<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ClienteSeeder::class,
            ParcelaSeeder::class,
            FabricantesSeeder::class,
            VariablesMedicionSeeder::class,
            RolesSeeder::class,
            EtapaFenologicaSeeder::class,
            UserSeeder::class,
            TipoEstacionSeeder::class,
            FabricantesSeeders::class,
            CultivoSeeder::class,
            PlagaSeeder::class,
            CultivoPlagaSeeder::class,
            EnfermedadesSeeder::class,
            TipoSueloSeeder::class,
            ZonaManejosSeeder::class,
            EstacionesSeeder::class,
            ZonaManejosEstacionesSeeder::class,
            ZonaManejosUserSeeder::class,
            GrupoGenesisSeeder::class,
            ZonaManejoLoteExternoSeeder::class,
        ]);
    }
}
