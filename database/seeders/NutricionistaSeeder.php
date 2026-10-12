<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Nutricionista;
use Illuminate\Support\Facades\Hash;

class NutricionistaSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Rol::where('nom_rol', 'Nutricionista')->first();

        $nutricionistas = [
            [
                'cor_usu' => 'laura.mendez@test.com',
                'nom_usu' => 'Laura',
                'ape_usu' => 'Méndez Vargas',
                'ci_usu' => '4512001',
                'fec_nac_usu' => '1987-03-14',
                'esp_nut' => 'Nutrición clínica',
            ],
            [
                'cor_usu' => 'javier.paz@test.com',
                'nom_usu' => 'Javier',
                'ape_usu' => 'Paz Rojas',
                'ci_usu' => '4512002',
                'fec_nac_usu' => '1984-11-02',
                'esp_nut' => 'Nutrición deportiva',
            ],
            [
                'cor_usu' => 'carolina.suarez@test.com',
                'nom_usu' => 'Carolina',
                'ape_usu' => 'Suárez Pinto',
                'ci_usu' => '4512003',
                'fec_nac_usu' => '1990-07-21',
                'esp_nut' => 'Educación alimentaria',
            ],
        ];

        foreach ($nutricionistas as $index => $data) {
            $usuario = Usuario::firstOrCreate(
                ['cor_usu' => $data['cor_usu']],
                [
                    'nom_usu' => $data['nom_usu'],
                    'ape_usu' => $data['ape_usu'],
                    'ci_usu' => $data['ci_usu'],
                    'ext_ci_usu' => 'LP',
                    'fec_nac_usu' => $data['fec_nac_usu'],
                    'cla_usu' => Hash::make('123456789'),
                    'est_usu' => 'activo',
                ]
            );

            if ($rol && ! $usuario->roles()->where('roles.cod_roles', $rol->cod_roles)->exists()) {
                $usuario->roles()->attach($rol->cod_roles);
            }

            Nutricionista::firstOrCreate(
                ['cod_usuarios_nut' => $usuario->cod_usuarios],
                [
                    'esp_nut' => $data['esp_nut'],
                    'est_nut' => 'activo',
                ]
            );
        }
    }
}
