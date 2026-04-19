<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::firstOrCreate(
            ['cor_usu' => 'dilanjpacogarcia@gmail.com'],
            [
                'nom_usu' => 'Super Administrador',
                'cla_usu' => 'Admin12345*',
                'est_usu' => 'activo',
            ]
        );

        $rol = Rol::where('nom_rol', 'SuperAdmin')->first();

        if ($rol && ! $usuario->roles()->where('roles.cod_roles', $rol->cod_roles)->exists()) {
            $usuario->roles()->attach($rol->cod_roles);
        }
    }
}