<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\AsignacionNutricionistaPaciente;
use App\Models\EvaluacionInicial;
use App\Models\PlanNutricional;
use App\Models\ObjetivoNutricional;
use App\Models\ContextoPaciente;
use App\Models\Alimento;
use App\Models\TablaComposicionNutricional;
use App\Models\RegistroAlimenticio;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\ValidacionInformacion;
use App\Models\HistorialNutricional;
use App\Models\SeguimientoNutricional;
use App\Services\CalculoNutricionalRegistroService;

class RobustTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener Roles
        $rolSuperAdmin = Rol::where('nom_rol', 'SuperAdmin')->first();
        $rolAdmin = Rol::where('nom_rol', 'Administrador')->first();
        $rolNutri = Rol::where('nom_rol', 'Nutricionista')->first();
        $rolPaciente = Rol::where('nom_rol', 'Paciente')->first();

        // 2. SuperAdmin: dilanjpacogarcia@gmail.com
        $superAdminUser = Usuario::updateOrCreate(
            ['cor_usu' => 'dilanjpacogarcia@gmail.com'],
            [
                'nom_usu' => 'Dilan',
                'ape_usu' => 'Paco García',
                'ci_usu' => '7777777',
                'ext_ci_usu' => 'LP',
                'fec_nac_usu' => '1995-03-24',
                'cla_usu' => Hash::make('123456789'),
                'est_usu' => 'activo',
            ]
        );
        if ($rolSuperAdmin && !$superAdminUser->roles()->where('roles.cod_roles', $rolSuperAdmin->cod_roles)->exists()) {
            $superAdminUser->roles()->attach($rolSuperAdmin->cod_roles);
        }

        // 3. Administrador: admin@example.test
        $adminUser = Usuario::updateOrCreate(
            ['cor_usu' => 'admin@example.test'],
            [
                'nom_usu' => 'Andrea',
                'ape_usu' => 'Salvatierra',
                'ci_usu' => '8888888',
                'ext_ci_usu' => 'LP',
                'fec_nac_usu' => '1988-10-15',
                'cla_usu' => Hash::make('123456789'),
                'est_usu' => 'activo',
            ]
        );
        if ($rolAdmin && !$adminUser->roles()->where('roles.cod_roles', $rolAdmin->cod_roles)->exists()) {
            $adminUser->roles()->attach($rolAdmin->cod_roles);
        }

        // 4. Nutricionista: laura.mendez@test.com
        $nutriUser = Usuario::updateOrCreate(
            ['cor_usu' => 'laura.mendez@test.com'],
            [
                'nom_usu' => 'Laura',
                'ape_usu' => 'Méndez Vargas',
                'ci_usu' => '4512001',
                'ext_ci_usu' => 'LP',
                'fec_nac_usu' => '1987-03-14',
                'cla_usu' => Hash::make('dilanpaco'), // Contraseña específica requerida
                'est_usu' => 'activo',
            ]
        );
        if ($rolNutri && !$nutriUser->roles()->where('roles.cod_roles', $rolNutri->cod_roles)->exists()) {
            $nutriUser->roles()->attach($rolNutri->cod_roles);
        }

        $nutricionista = Nutricionista::updateOrCreate(
            ['cod_usuarios_nut' => $nutriUser->cod_usuarios],
            [
                'esp_nut' => 'Nutrición Clínica y Diabetología',
                'est_nut' => 'activo',
            ]
        );

        // 5. Paciente: ana.lopez@test.com
        $pacienteUser = Usuario::updateOrCreate(
            ['cor_usu' => 'ana.lopez@test.com'],
            [
                'nom_usu' => 'Ana',
                'ape_usu' => 'López Salazar',
                'ci_usu' => '5201001',
                'ext_ci_usu' => 'CB',
                'fec_nac_usu' => '1996-01-18',
                'cla_usu' => Hash::make('123456789'),
                'est_usu' => 'activo',
            ]
        );
        if ($rolPaciente && !$pacienteUser->roles()->where('roles.cod_roles', $rolPaciente->cod_roles)->exists()) {
            $pacienteUser->roles()->attach($rolPaciente->cod_roles);
        }

        $paciente = Paciente::updateOrCreate(
            ['cod_usuarios_pac' => $pacienteUser->cod_usuarios],
            [
                'sex_pac' => 'F',
                'pes_pac' => 62.4,
                'estatura_pac' => 1.63,
                'tip_san_pac' => 'O+',
                'ale_pac' => 'Lácteos enteros',
                'est_pac' => 'activo',
            ]
        );

        // 6. Asignación Activa Nutricionista-Paciente
        $asignacion = AsignacionNutricionistaPaciente::updateOrCreate(
            [
                'cod_nutricionistas_anp' => $nutricionista->cod_nutricionistas,
                'cod_pacientes_anp' => $paciente->cod_pacientes,
            ],
            [
                'fec_anp' => now()->subDays(30)->toDateString(),
                'est_anp' => 'activa',
            ]
        );

        // 7. Evaluación Inicial
        $evaluacion = EvaluacionInicial::updateOrCreate(
            [
                'cod_pacientes_evi' => $paciente->cod_pacientes,
                'cod_nutricionistas_evi' => $nutricionista->cod_nutricionistas,
            ],
            [
                'fec_evi' => now()->subDays(28)->toDateString(),
                'est_evi' => EvaluacionInicial::ESTADO_ACTIVA,
                'peso_evi' => 63.5,
                'estatura_evi' => 1.63,
                'imc_evi' => round(63.5 / (1.63 * 1.63), 2),
                'objetivo_inicial_evi' => 'Disminución de masa grasa corporal y control de picos glucémicos.',
                'restricciones_evi' => 'Evitar carbohidratos refinados por la noche y lácteos enteros.',
                'contexto_general_evi' => 'Paciente presenta cansancio postprandial y antecedentes de diabetes tipo 2 en línea materna directa.',
                'obs_evi' => 'Se sugiere monitoreo estricto de horarios alimenticios.',
            ]
        );

        // 8. Plan Nutricional
        $plan = PlanNutricional::updateOrCreate(
            [
                'cod_pacientes_pan' => $paciente->cod_pacientes,
                'cod_nutricionistas_pan' => $nutricionista->cod_nutricionistas,
                'est_pan' => 'vigente',
            ],
            [
                'fec_pan' => now()->subDays(28)->toDateString(),
                'des_pan' => 'Dieta controlada en carbohidratos complejos (50-55% del total de calorías), aporte de proteína de 1.2g/kg (pollo, pavo) y abundantes vegetales verdes. Restringir lácteos enteros. Fraccionar comidas en 4 tomas diarias con desayuno obligatorio.',
            ]
        );

        // 9. Objetivos Nutricionales
        $objetivos = [
            'Reducir grasa visceral y porcentaje graso total manteniendo masa muscular.',
            'Regularizar los horarios de comidas principales, especialmente el desayuno antes de las 9:00 AM.',
            'Aumentar el consumo de verdura verde y fibra diaria (mínimo 25g al día).',
        ];

        foreach ($objetivos as $des_obe) {
            ObjetivoNutricional::updateOrCreate(
                [
                    'cod_pacientes_obe' => $paciente->cod_pacientes,
                    'cod_nutricionistas_obe' => $nutricionista->cod_nutricionistas,
                    'des_obe' => $des_obe,
                ],
                [
                    'fec_obe' => now()->subDays(28)->toDateString(),
                    'est_obe' => ObjetivoNutricional::ESTADO_ACTIVO,
                ]
            );
        }

        // 10. Contextos Clínicos
        ContextoPaciente::updateOrCreate(
            [
                'cod_pacientes_cxp' => $paciente->cod_pacientes,
                'des_cxp' => 'Antecedentes familiares de diabetes mellitus tipo 2 (Madre y Abuela materna). Resistencia periférica leve a la insulina.',
            ],
            [
                'tip_cxp' => 'patologia',
                'pri_cxp' => 'alta',
                'fec_cxp' => now()->subDays(28)->toDateString(),
                'est_cxp' => 'activo',
            ]
        );

        ContextoPaciente::updateOrCreate(
            [
                'cod_pacientes_cxp' => $paciente->cod_pacientes,
                'des_cxp' => 'Leve intolerancia clínica a los lácteos enteros. Produce distensión abdominal.',
            ],
            [
                'tip_cxp' => 'alergia',
                'pri_cxp' => 'media',
                'fec_cxp' => now()->subDays(28)->toDateString(),
                'est_cxp' => 'activo',
            ]
        );

        // 11. Registrar Alimentos y Composiciones Nutricionales
        $alimentosNutrientes = [
            'Manzana' => [
                'tip' => 'Fruta',
                'des' => 'Manzana roja fresca con cáscara',
                'cal' => 52.0, 'pro' => 0.26, 'gra' => 0.17, 'car' => 13.81, 'fib' => 2.4, 'azu' => 10.39,
                'sod' => 1.0, 'pot' => 107.0, 'col' => 0.0, 'calcio' => 6.0, 'hierro' => 0.12,
                'vit_a' => 54.0, 'vit_c' => 4.6, 'vit_d' => 0.0
            ],
            'Pechuga de Pollo' => [
                'tip' => 'Carne',
                'des' => 'Pechuga de pollo a la plancha sin piel ni grasa',
                'cal' => 165.0, 'pro' => 31.02, 'gra' => 3.57, 'car' => 0.0, 'fib' => 0.0, 'azu' => 0.0,
                'sod' => 74.0, 'pot' => 256.0, 'col' => 85.0, 'calcio' => 15.0, 'hierro' => 1.0,
                'vit_a' => 0.0, 'vit_c' => 0.0, 'vit_d' => 0.2
            ],
            'Arroz Blanco' => [
                'tip' => 'Cereal',
                'des' => 'Arroz blanco cocido',
                'cal' => 130.0, 'pro' => 2.69, 'gra' => 0.28, 'car' => 28.17, 'fib' => 0.4, 'azu' => 0.08,
                'sod' => 1.0, 'pot' => 35.0, 'col' => 0.0, 'calcio' => 10.0, 'hierro' => 1.2,
                'vit_a' => 0.0, 'vit_c' => 0.0, 'vit_d' => 0.0
            ],
            'Brócoli' => [
                'tip' => 'Verdura',
                'des' => 'Brócoli cocido al vapor sin sal',
                'cal' => 34.0, 'pro' => 2.82, 'gra' => 0.37, 'car' => 6.64, 'fib' => 2.6, 'azu' => 1.7,
                'sod' => 33.0, 'pot' => 293.0, 'col' => 0.0, 'calcio' => 47.0, 'hierro' => 0.73,
                'vit_a' => 3000.0, 'vit_c' => 89.2, 'vit_d' => 0.0
            ]
        ];

        $alimentosModelos = [];
        foreach ($alimentosNutrientes as $nombre => $nutri) {
            $alimento = Alimento::updateOrCreate(
                ['nom_ali' => $nombre],
                [
                    'nom_ext_ali' => $nombre,
                    'tip_ali' => $nutri['tip'],
                    'des_ali' => $nutri['des'],
                    'mar_ali' => 'Genérico',
                    'src_ali' => 'manual',
                    'est_ali' => 'activo',
                ]
            );

            $alimentosModelos[$nombre] = $alimento;

            // Asegurar que tiene la composición vigente de referencia
            TablaComposicionNutricional::updateOrCreate(
                [
                    'cod_alimentos_tcn' => $alimento->cod_alimentos,
                    'est_tcn' => 'vigente'
                ],
                [
                    'por_ref_tcn' => '100g de porción comestible',
                    'cant_ref_tcn' => 100.0,
                    'uni_ref_tcn' => 'gramos',
                    'cal_tcn' => $nutri['cal'],
                    'pro_tcn' => $nutri['pro'],
                    'gra_tcn' => $nutri['gra'],
                    'car_tcn' => $nutri['car'],
                    'fib_tcn' => $nutri['fib'],
                    'azu_tcn' => $nutri['azu'],
                    'sod_tcn' => $nutri['sod'],
                    'pot_tcn' => $nutri['pot'],
                    'col_tcn' => $nutri['col'],
                    'calcio_tcn' => $nutri['calcio'],
                    'hierro_tcn' => $nutri['hierro'],
                    'vit_a_tcn' => $nutri['vit_a'],
                    'vit_c_tcn' => $nutri['vit_c'],
                    'vit_d_tcn' => $nutri['vit_d'],
                    'fec_tcn' => now()->toDateString(),
                ]
            );
        }

        // 12. Simular 18 días de registros alimenticios en los últimos 28 días
        // Para forzar la IA a clasificarlo en "Perfil B" (Alto Carbohidratos, irregularidad horaria):
        // - Saltaremos el desayuno en 11 de los 18 días (Desayuno regularidad: 7/18, menor a 4/7).
        // - Pondremos alta cantidad de Arroz Blanco (carbohidratos > 55% de calorías).
        // - Pondremos alta variabilidad horaria en las comidas.
        $calculador = app(CalculoNutricionalRegistroService::class);
        $hoy = Carbon::today();
        
        $diasSimulados = 0;
        for ($i = 25; $i >= 1; $i--) {
            // Seedear 18 días en total
            if ($diasSimulados >= 18) {
                break;
            }

            // Omitir algunos días de forma aleatoria para simular inconsistencia de registro
            if (in_array($i, [24, 21, 17, 14, 11, 7, 3], true)) {
                continue;
            }

            $fechaRegistro = $hoy->copy()->subDays($i);
            
            $registro = RegistroAlimenticio::updateOrCreate(
                [
                    'cod_pacientes_ral' => $paciente->cod_pacientes,
                    'fec_ral' => $fechaRegistro->toDateString(),
                ],
                [
                    'est_ral' => RegistroAlimenticio::ESTADO_CERRADO
                ]
            );

            // Determinar si desayuna hoy (Saltamos desayuno en 11 de los 18 días para activar la regla del árbol)
            $desayunaHoy = ($diasSimulados % 3 === 0); // Desayuna en días index 0, 3, 6, 9, 12, 15, 18 (aprox. 6 días de 18)
            
            // Variabilidad horaria:
            // Almuerza temprano (12:00) o tarde (15:30)
            $horaAlmuerzo = ($diasSimulados % 2 === 0) ? '12:00:00' : '15:30:00';
            // Cena temprano (18:15) o muy tarde (22:45)
            $horaCena = ($diasSimulados % 3 === 0) ? '18:15:00' : '22:45:00';

            // Detalles del registro
            if ($desayunaHoy) {
                DetalleRegistroAlimenticio::updateOrCreate(
                    [
                        'cod_registros_alimenticios_dra' => $registro->cod_registros_alimenticios,
                        'tip_dra' => DetalleRegistroAlimenticio::TIPO_DESAYUNO,
                    ],
                    [
                        'cod_alimentos_dra' => $alimentosModelos['Manzana']->cod_alimentos,
                        'can_dra' => 150.0,
                        'uni_dra' => 'gramos',
                        'hor_dra' => '08:45:00',
                    ]
                );
            }

            // Almuerzo: Alto carbohidrato (300g Arroz Blanco, 200g Pollo, 100g Brócoli)
            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registro->cod_registros_alimenticios,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_ALMUERZO,
                ],
                [
                    'cod_alimentos_dra' => $alimentosModelos['Pechuga de Pollo']->cod_alimentos,
                    'can_dra' => 200.0,
                    'uni_dra' => 'gramos',
                    'hor_dra' => $horaAlmuerzo,
                ]
            );

            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registro->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $alimentosModelos['Arroz Blanco']->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_ALMUERZO,
                ],
                [
                    'can_dra' => 320.0, // Alta cantidad de arroz -> carbohidratos altos
                    'uni_dra' => 'gramos',
                    'hor_dra' => $horaAlmuerzo,
                ]
            );

            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registro->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $alimentosModelos['Brócoli']->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_ALMUERZO,
                ],
                [
                    'can_dra' => 120.0,
                    'uni_dra' => 'gramos',
                    'hor_dra' => $horaAlmuerzo,
                ]
            );

            // Cena: (150g Pollo, 250g Arroz Blanco)
            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registro->cod_registros_alimenticios,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_CENA,
                ],
                [
                    'cod_alimentos_dra' => $alimentosModelos['Pechuga de Pollo']->cod_alimentos,
                    'can_dra' => 150.0,
                    'uni_dra' => 'gramos',
                    'hor_dra' => $horaCena,
                ]
            );

            DetalleRegistroAlimenticio::updateOrCreate(
                [
                    'cod_registros_alimenticios_dra' => $registro->cod_registros_alimenticios,
                    'cod_alimentos_dra' => $alimentosModelos['Arroz Blanco']->cod_alimentos,
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_CENA,
                ],
                [
                    'can_dra' => 250.0, // Sigue sumando arroz blanco
                    'uni_dra' => 'gramos',
                    'hor_dra' => $horaCena,
                ]
            );

            // Calcular y guardar composición consolidada del registro en la tabla composiciones_nutricionales_registradas
            $calculador->calcularYGuardar($registro, false);

            // 13. Validar los registros (16 Aceptados, 2 Observados)
            $estadoValidacion = ValidacionInformacion::ESTADO_ACEPTADO;
            $observacion = null;
            if ($diasSimulados === 5 || $diasSimulados === 12) {
                $estadoValidacion = ValidacionInformacion::ESTADO_OBSERVADO;
                $observacion = 'Se observa un consumo muy alto de carbohidratos refinados (arroz blanco) en horarios tardíos de la cena. Moderar porciones.';
            }

            ValidacionInformacion::updateOrCreate(
                [
                    'cod_registros_alimenticios_vin' => $registro->cod_registros_alimenticios,
                    'cod_nutricionistas_vin' => $nutricionista->cod_nutricionistas,
                ],
                [
                    'fec_vin' => $fechaRegistro->copy()->addDay()->toDateString(),
                    'est_vin' => $estadoValidacion,
                    'obs_vin' => $observacion,
                ]
            );

            $diasSimulados++;
        }

        // Agregar además 1 registro rechazado para mostrar que funciona la exclusión
        $registroRechazado = RegistroAlimenticio::updateOrCreate(
            [
                'cod_pacientes_ral' => $paciente->cod_pacientes,
                'fec_ral' => $hoy->copy()->subDays(27)->toDateString(),
            ],
            ['est_ral' => RegistroAlimenticio::ESTADO_CERRADO]
        );
        DetalleRegistroAlimenticio::updateOrCreate(
            [
                'cod_registros_alimenticios_dra' => $registroRechazado->cod_registros_alimenticios,
                'tip_dra' => DetalleRegistroAlimenticio::TIPO_CENA,
            ],
            [
                'cod_alimentos_dra' => $alimentosModelos['Manzana']->cod_alimentos,
                'can_dra' => 500.0,
                'uni_dra' => 'gramos',
                'hor_dra' => '23:59:00',
            ]
        );
        $calculador->calcularYGuardar($registroRechazado, false);
        ValidacionInformacion::updateOrCreate(
            [
                'cod_registros_alimenticios_vin' => $registroRechazado->cod_registros_alimenticios,
                'cod_nutricionistas_vin' => $nutricionista->cod_nutricionistas,
            ],
            [
                'fec_vin' => $hoy->copy()->subDays(26)->toDateString(),
                'est_vin' => ValidacionInformacion::ESTADO_RECHAZADO,
                'obs_vin' => 'Registro duplicado e incoherente con el plan nutricional nocturno.',
            ]
        );

        // 14. Historial Nutricional
        $historiales = [
            28 => 'Ingreso de paciente Ana López Salazar. Peso inicial reportado: 63.5 kg. Estatura: 1.63 m. IMC de base: 23.90. Se inicia planificación.',
            21 => 'Evaluación de control clínico 1. Paciente refiere adaptación a las metas de proteínas, pero con dificultades debido a horarios laborales variables. Peso registrado: 63.1 kg.',
            14 => 'Evaluación de control clínico 2. Descenso de peso a 62.8 kg. Aceptación buena del plan normocalórico, intolerancia a lácteos controlada tras suspender leche entera.',
            7  => 'Evaluación de control clínico 3. Peso actual: 62.4 kg. Se observa tendencia a saltar el desayuno que altera el ritmo circadiano. Se ajusta plan.',
        ];

        foreach ($historiales as $dias => $descripcion) {
            HistorialNutricional::updateOrCreate(
                [
                    'cod_pacientes_hin' => $paciente->cod_pacientes,
                    'fec_hin' => $hoy->copy()->subDays($dias)->toDateString(),
                ],
                [
                    'des_hin' => $descripcion,
                ]
            );
        }

        // 15. Seguimientos Clínicos Nutricionales
        SeguimientoNutricional::updateOrCreate(
            [
                'cod_pacientes_seg' => $paciente->cod_pacientes,
                'cod_nutricionistas_seg' => $nutricionista->cod_nutricionistas,
                'fec_seg' => $hoy->copy()->subDays(21)->toDateString(),
            ],
            [
                'res_seg' => 'Primer control de seguimiento. La paciente reporta cumplimiento del 80% de registros en la primera semana. Se evidencia un patrón de omisión de desayuno durante los días de oficina.',
                'cam_seg' => 'Se aconseja implementar alarmas para desayunar porciones portátiles de fruta y almendras.'
            ]
        );

        SeguimientoNutricional::updateOrCreate(
            [
                'cod_pacientes_seg' => $paciente->cod_pacientes,
                'cod_nutricionistas_seg' => $nutricionista->cod_nutricionistas,
                'fec_seg' => $hoy->copy()->subDays(14)->toDateString(),
            ],
            [
                'res_seg' => 'Segundo control de seguimiento. Se valora positivamente la regularización de la ingesta de proteína. El peso disminuye consistentemente. Se mantiene regularidad horaria del almuerzo muy baja.',
                'cam_seg' => 'Se instruye preparar viandas con arroz integral en lugar de arroz blanco para atenuar picos glucémicos vespertinos.'
            ]
        );

        SeguimientoNutricional::updateOrCreate(
            [
                'cod_pacientes_seg' => $paciente->cod_pacientes,
                'cod_nutricionistas_seg' => $nutricionista->cod_nutricionistas,
                'fec_seg' => $hoy->copy()->subDays(7)->toDateString(),
            ],
            [
                'res_seg' => 'Tercer control de seguimiento. Se analiza el panel predictivo de IA. Paciente califica en Perfil B (Consumo alto en carbohidratos con horarios altamente variables). La variabilidad horaria es alta con desviación estándar superior a los 140 minutos. La probabilidad de desequilibrio es moderada.',
                'cam_seg' => 'Se inyecta el análisis de IA para acordar con el paciente la regularización del desayuno obligatoriamente los fines de semana y limitar la porción de carbohidratos en la cena.'
            ]
        );
    }
}
