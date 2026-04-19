<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nom_rol' => 'SuperAdmin',
                'des_rol' => 'Acceso técnico y administrativo total del sistema.',
            ],
            [
                'nom_rol' => 'Administrador',
                'des_rol' => 'Administra la operación del negocio de nutrición.',
            ],
            [
                'nom_rol' => 'Nutricionista',
                'des_rol' => 'Atiende pacientes y gestiona el proceso nutricional.',
            ],
            [
                'nom_rol' => 'Paciente',
                'des_rol' => 'Usuario atendido dentro del sistema.',
            ],
        ];

        foreach ($roles as $rol) {
            Rol::firstOrCreate(
                ['nom_rol' => $rol['nom_rol']],
                ['des_rol' => $rol['des_rol']]
            );
        }
    }
}