<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Paciente;
use Illuminate\Support\Facades\Hash;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Rol::where('nom_rol', 'Paciente')->first();

        $pacientes = [
            ['cor_usu' => 'ana.lopez@test.com', 'nom_usu' => 'Ana', 'ape_usu' => 'López Salazar', 'ci_usu' => '5201001', 'fec_nac_usu' => '1996-01-18', 'sex_pac' => 'F', 'pes_pac' => 62.4, 'estatura_pac' => 1.63, 'tip_san_pac' => 'O+', 'ale_pac' => 'Ninguna'],
            ['cor_usu' => 'bruno.torrez@test.com', 'nom_usu' => 'Bruno', 'ape_usu' => 'Torrez Aguilar', 'ci_usu' => '5201002', 'fec_nac_usu' => '1993-05-09', 'sex_pac' => 'M', 'pes_pac' => 78.1, 'estatura_pac' => 1.74, 'tip_san_pac' => 'A+', 'ale_pac' => 'Lactosa'],
            ['cor_usu' => 'camila.vasquez@test.com', 'nom_usu' => 'Camila', 'ape_usu' => 'Vásquez Roca', 'ci_usu' => '5201003', 'fec_nac_usu' => '1999-08-27', 'sex_pac' => 'F', 'pes_pac' => 58.0, 'estatura_pac' => 1.60, 'tip_san_pac' => 'B+', 'ale_pac' => 'Ninguna'],
            ['cor_usu' => 'diego.murillo@test.com', 'nom_usu' => 'Diego', 'ape_usu' => 'Murillo Flores', 'ci_usu' => '5201004', 'fec_nac_usu' => '1991-12-11', 'sex_pac' => 'M', 'pes_pac' => 84.6, 'estatura_pac' => 1.78, 'tip_san_pac' => 'O-', 'ale_pac' => 'Mariscos'],
            ['cor_usu' => 'elena.quispe@test.com', 'nom_usu' => 'Elena', 'ape_usu' => 'Quispe Mamani', 'ci_usu' => '5201005', 'fec_nac_usu' => '1988-04-23', 'sex_pac' => 'F', 'pes_pac' => 69.2, 'estatura_pac' => 1.66, 'tip_san_pac' => 'AB+', 'ale_pac' => 'Frutos secos'],
            ['cor_usu' => 'fernando.cano@test.com', 'nom_usu' => 'Fernando', 'ape_usu' => 'Cano Rivera', 'ci_usu' => '5201006', 'fec_nac_usu' => '1994-09-30', 'sex_pac' => 'M', 'pes_pac' => 73.5, 'estatura_pac' => 1.72, 'tip_san_pac' => 'A-', 'ale_pac' => 'Ninguna'],
            ['cor_usu' => 'gabriela.ruiz@test.com', 'nom_usu' => 'Gabriela', 'ape_usu' => 'Ruiz Fernández', 'ci_usu' => '5201007', 'fec_nac_usu' => '2000-02-14', 'sex_pac' => 'F', 'pes_pac' => 55.8, 'estatura_pac' => 1.58, 'tip_san_pac' => 'B-', 'ale_pac' => 'Huevos'],
            ['cor_usu' => 'hugo.salas@test.com', 'nom_usu' => 'Hugo', 'ape_usu' => 'Salas Paredes', 'ci_usu' => '5201008', 'fec_nac_usu' => '1985-10-06', 'sex_pac' => 'M', 'pes_pac' => 90.2, 'estatura_pac' => 1.80, 'tip_san_pac' => 'O+', 'ale_pac' => 'Ninguna'],
            ['cor_usu' => 'isabel.morales@test.com', 'nom_usu' => 'Isabel', 'ape_usu' => 'Morales Vaca', 'ci_usu' => '5201009', 'fec_nac_usu' => '1997-06-19', 'sex_pac' => 'F', 'pes_pac' => 60.7, 'estatura_pac' => 1.64, 'tip_san_pac' => 'A+', 'ale_pac' => 'Gluten'],
        ];

        foreach ($pacientes as $data) {
            $usuario = Usuario::firstOrCreate(
                ['cor_usu' => $data['cor_usu']],
                [
                    'nom_usu' => $data['nom_usu'],
                    'ape_usu' => $data['ape_usu'],
                    'ci_usu' => $data['ci_usu'],
                    'ext_ci_usu' => 'CB',
                    'fec_nac_usu' => $data['fec_nac_usu'],
                    'cla_usu' => Hash::make('123456789'),
                    'est_usu' => 'activo',
                ]
            );

            if ($rol && ! $usuario->roles()->where('roles.cod_roles', $rol->cod_roles)->exists()) {
                $usuario->roles()->attach($rol->cod_roles);
            }

            Paciente::firstOrCreate(
                ['cod_usuarios_pac' => $usuario->cod_usuarios],
                [
                    'sex_pac' => $data['sex_pac'],
                    'pes_pac' => $data['pes_pac'],
                    'estatura_pac' => $data['estatura_pac'],
                    'tip_san_pac' => $data['tip_san_pac'],
                    'ale_pac' => $data['ale_pac'],
                    'est_pac' => 'activo',
                ]
            );
        }
    }
}
