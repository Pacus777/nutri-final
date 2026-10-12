<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::firstOrCreate(
            ['cor_usu' => 'dilanjpacogarcia@gmail.com'],
            [
                'nom_usu' => 'Dilan',
                'ape_usu' => 'Paco García',
                'ci_usu' => '0000000',
                'ext_ci_usu' => 'LP',
                'fec_nac_usu' => '1990-01-01',
                'fot_usu' => null,
                'cla_usu' => Hash::make('123456789'),
                'est_usu' => 'activo',
            ]
        );

        $rol = Rol::where('nom_rol', 'SuperAdmin')->first();

        if ($rol && ! $usuario->roles()->where('roles.cod_roles', $rol->cod_roles)->exists()) {
            $usuario->roles()->attach($rol->cod_roles);
        }
    }
}