<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\AsignacionNutricionistaPaciente;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Administrador
        $admin = Usuario::firstOrCreate(
            ['cor_usu' => 'admin@example.test'],
            [
                'nom_usu' => 'Andrea',
                'ape_usu' => 'Salvatierra',
                'ci_usu' => '0000001',
                'ext_ci_usu' => 'LP',
                'fec_nac_usu' => '1986-02-12',
                'fot_usu' => null,
                'cla_usu' => Hash::make('123456789'),
                'est_usu' => 'activo',
            ]
        );

        $rolAdmin = Rol::where('nom_rol', 'Administrador')->first();
        if ($rolAdmin && ! $admin->roles()->where('roles.cod_roles', $rolAdmin->cod_roles)->exists()) {
            $admin->roles()->attach($rolAdmin->cod_roles);
        }

        // Nutricionista
        $nut = Usuario::firstOrCreate(
            ['cor_usu' => 'maria.lopez@example.test'],
            [
                'nom_usu' => 'María',
                'ape_usu' => 'López',
                'ci_usu' => '0000002',
                'ext_ci_usu' => 'LP',
                'fec_nac_usu' => '1989-09-04',
                'fot_usu' => null,
                'cla_usu' => Hash::make('123456789'),
                'est_usu' => 'activo',
            ]
        );

        $rolNut = Rol::where('nom_rol', 'Nutricionista')->first();
        if ($rolNut && ! $nut->roles()->where('roles.cod_roles', $rolNut->cod_roles)->exists()) {
            $nut->roles()->attach($rolNut->cod_roles);
        }

        // Crear perfil de nutricionista si no existe
        Nutricionista::firstOrCreate(
            ['cod_usuarios_nut' => $nut->cod_usuarios],
            ['esp_nut' => null, 'est_nut' => 'activo']
        );

        // Paciente
        $pac = Usuario::firstOrCreate(
            ['cor_usu' => 'jose.arteaga@example.test'],
            [
                'nom_usu' => 'José',
                'ape_usu' => 'Arteaga',
                'ci_usu' => '0000003',
                'ext_ci_usu' => 'LP',
                'fec_nac_usu' => '1995-06-27',
                'fot_usu' => null,
                'cla_usu' => Hash::make('123456789'),
                'est_usu' => 'activo',
            ]
        );

        $rolPac = Rol::where('nom_rol', 'Paciente')->first();
        if ($rolPac && ! $pac->roles()->where('roles.cod_roles', $rolPac->cod_roles)->exists()) {
            $pac->roles()->attach($rolPac->cod_roles);
        }

        // Crear perfil de paciente si no existe
        $paciente = Paciente::firstOrCreate(
            ['cod_usuarios_pac' => $pac->cod_usuarios],
            ['sex_pac' => 'M', 'pes_pac' => 70.0, 'estatura_pac' => 170.0, 'tip_san_pac' => 'SIN_ESPECIFICAR', 'ale_pac' => null, 'est_pac' => 'activo']
        );

        // Crear asignacion activa entre nutricionista y paciente
        $nutModel = Nutricionista::where('cod_usuarios_nut', $nut->cod_usuarios)->first();
        if ($nutModel && $paciente) {
            AsignacionNutricionistaPaciente::firstOrCreate(
                [
                    'cod_nutricionistas_anp' => $nutModel->cod_nutricionistas,
                    'cod_pacientes_anp' => $paciente->cod_pacientes,
                ],
                [
                    'fec_anp' => now()->toDateString(),
                    'est_anp' => 'activa',
                ]
            );
        }
    }
}
