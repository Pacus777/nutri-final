<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            SuperAdminSeeder::class,
            NutricionistaSeeder::class,
            PacienteSeeder::class,
            AsignacionSeeder::class,
            AlimentoSeeder::class,
            TestAccountsSeeder::class,
            RobustTestDataSeeder::class,
            RegistrosPendientesSeeder::class,
        ]);
    }
}
