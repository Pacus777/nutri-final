<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\AsignacionNutricionistaPaciente;
use App\Models\RegistroAlimenticio;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\Alimento;
use App\Services\CalculoNutricionalRegistroService;
use Illuminate\Support\Carbon;

class RegistrosPendientesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener Nutricionista Laura Méndez
        $usuarioNutri = Usuario::where('cor_usu', 'laura.mendez@test.com')->first();
        if (!$usuarioNutri) {
            $this->command->warn("Nutricionista laura.mendez@test.com no encontrado.");
            return;
        }
        $nutricionista = Nutricionista::where('cod_usuarios_nut', $usuarioNutri->cod_usuarios)->first();
        if (!$nutricionista) {
            $this->command->warn("Perfil de nutricionista no encontrado para Laura.");
            return;
        }

        // 2. Obtener Paciente Camila Vásquez Roca
        $usuarioCamila = Usuario::where('cor_usu', 'camila.vasquez@test.com')->first();
        if (!$usuarioCamila) {
            $this->command->warn("Paciente Camila Vásquez Roca no encontrado.");
            return;
        }
        $pacienteCamila = Paciente::where('cod_usuarios_pac', $usuarioCamila->cod_usuarios)->first();

        // 3. Obtener Paciente Bruno Torrez Aguilar
        $usuarioBruno = Usuario::where('cor_usu', 'bruno.torrez@test.com')->first();
        if (!$usuarioBruno) {
            $this->command->warn("Paciente Bruno Torrez Aguilar no encontrado.");
            return;
        }
        $pacienteBruno = Paciente::where('cod_usuarios_pac', $usuarioBruno->cod_usuarios)->first();

        // 4. Asegurar asignación activa con la nutricionista
        AsignacionNutricionistaPaciente::updateOrCreate(
            [
                'cod_nutricionistas_anp' => $nutricionista->cod_nutricionistas,
                'cod_pacientes_anp' => $pacienteCamila->cod_pacientes,
            ],
            [
                'fec_anp' => now()->subDays(15)->toDateString(),
                'est_anp' => 'activa',
            ]
        );

        AsignacionNutricionistaPaciente::updateOrCreate(
            [
                'cod_nutricionistas_anp' => $nutricionista->cod_nutricionistas,
                'cod_pacientes_anp' => $pacienteBruno->cod_pacientes,
            ],
            [
                'fec_anp' => now()->subDays(15)->toDateString(),
                'est_anp' => 'activa',
            ]
        );

        // 5. Obtener Alimentos para los registros
        $manzana = Alimento::where('nom_ali', 'Manzana')->first();
        $pollo = Alimento::where('nom_ali', 'Pechuga de Pollo')->first();
        $arroz = Alimento::where('nom_ali', 'Arroz Blanco')->first();
        $brocoli = Alimento::where('nom_ali', 'Brócoli')->first();

        $calculador = app(CalculoNutricionalRegistroService::class);
        $fechaRegistro = Carbon::today()->toDateString();

        // 6. Registro Alimenticio para Camila Vásquez Roca (cerrado/enviado para validar, sin validación previa)
        $registroCamila = RegistroAlimenticio::updateOrCreate(
            [
                'cod_pacientes_ral' => $pacienteCamila->cod_pacientes,
                'fec_ral' => $fechaRegistro,
            ],
            [
                'est_ral' => RegistroAlimenticio::ESTADO_CERRADO,
            ]
        );

        // Detalles para Camila
        if ($manzana) {
            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registroCamila->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $manzana->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_DESAYUNO,
                ],
                [
                    'can_dra' => 150.00,
                    'uni_dra' => DetalleRegistroAlimenticio::UNIDAD_GRAMOS,
                    'hor_dra' => '08:30:00',
                ]
            );
        }

        if ($pollo) {
            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registroCamila->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $pollo->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_ALMUERZO,
                ],
                [
                    'can_dra' => 200.00,
                    'uni_dra' => DetalleRegistroAlimenticio::UNIDAD_GRAMOS,
                    'hor_dra' => '12:45:00',
                ]
            );
        }

        if ($arroz) {
            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registroCamila->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $arroz->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_ALMUERZO,
                ],
                [
                    'can_dra' => 150.00,
                    'uni_dra' => DetalleRegistroAlimenticio::UNIDAD_GRAMOS,
                    'hor_dra' => '12:45:00',
                ]
            );
        }

        // Calcular aporte nutricional para Camila
        $calculador->calcularYGuardar($registroCamila, false);

        // 7. Registro Alimenticio para Bruno Torrez Aguilar (cerrado/enviado para validar, sin validación previa)
        $registroBruno = RegistroAlimenticio::updateOrCreate(
            [
                'cod_pacientes_ral' => $pacienteBruno->cod_pacientes,
                'fec_ral' => $fechaRegistro,
            ],
            [
                'est_ral' => RegistroAlimenticio::ESTADO_CERRADO,
            ]
        );

        // Detalles para Bruno
        if ($pollo) {
            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registroBruno->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $pollo->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_ALMUERZO,
                ],
                [
                    'can_dra' => 250.00,
                    'uni_dra' => DetalleRegistroAlimenticio::UNIDAD_GRAMOS,
                    'hor_dra' => '13:15:00',
                ]
            );
        }

        if ($brocoli) {
            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registroBruno->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $brocoli->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_ALMUERZO,
                ],
                [
                    'can_dra' => 120.00,
                    'uni_dra' => DetalleRegistroAlimenticio::UNIDAD_GRAMOS,
                    'hor_dra' => '13:15:00',
                ]
            );
        }

        if ($arroz) {
            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registroBruno->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $arroz->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_CENA,
                ],
                [
                    'can_dra' => 180.00,
                    'uni_dra' => DetalleRegistroAlimenticio::UNIDAD_GRAMOS,
                    'hor_dra' => '20:10:00',
                ]
            );
        }

        // Calcular aporte nutricional para Bruno
        $calculador->calcularYGuardar($registroBruno, false);

        $this->command->info("Registros alimenticios creados exitosamente para Camila Vásquez y Bruno Torrez.");
    }
}
