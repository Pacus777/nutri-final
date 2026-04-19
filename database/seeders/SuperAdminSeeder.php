<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\Rol;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::firstOrCreate(
            ['cor_usu' => 'superadmin@proyecto.local'],
            [
                'nom_usu' => 'Super Administrador',
                'cla_usu' => Hash::make('Admin12345*'),
                'est_usu' => 'activo',
            ]
        );

        $rol = Rol::where('nom_rol', 'SuperAdmin')->first();

        if ($rol && ! $usuario->roles()->where('roles.cod_roles', $rol->cod_roles)->exists()) {
            $usuario->roles()->attach($rol->cod_roles);
        }
    }
}