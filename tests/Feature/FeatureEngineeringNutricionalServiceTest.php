<?php

namespace Tests\Feature;

use App\Models\Alimento;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\EvaluacionInicial;
use App\Models\FeatureNutricional;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use App\Models\TablaComposicionNutricional;
use App\Models\Usuario;
use App\Models\ValidacionInformacion;
use App\Services\CalculoNutricionalRegistroService;
use App\Services\FeatureEngineeringNutricionalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeatureEngineeringNutricionalServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'nutricional-dilan-sistema-final',
            'database.connections.mysql.port' => 3360,
        ]);
        DB::purge('mysql');
    }

    public function test_extraccion_vector_features_con_datos_controlados_en_ventana_7_dias(): void
    {
        $uniq = Str::random(6);

        // 1. Crear Paciente y Usuario con fecha de nacimiento conocida (Edad = 30)
        $usuarioPac = Usuario::create([
            'nom_usu' => 'Carlos',
            'ape_usu' => 'Analitico ' . $uniq,
            'ci_usu' => '9988' . rand(100, 999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1996-05-15',
            'cor_usu' => 'carlos_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $paciente = Paciente::create([
            'cod_usuarios_pac' => $usuarioPac->cod_usuarios,
            'sex_pac' => 'masculino',
            'pes_pac' => 80.00,
            'estatura_pac' => 1.80, // IMC = 80 / (1.8^2) = 24.69
            'est_pac' => 'activo',
        ]);

        // 2. Crear Alimento y Composición Nutricional controlada (por 100g)
        $alimentoA = Alimento::create([
            'nom_ali' => 'Pechuga de Pollo Test ' . $uniq,
            'tip_ali' => 'ingrediente',
            'src_ali' => 'manual',
            'est_ali' => 'activo',
        ]);

        TablaComposicionNutricional::create([
            'cod_alimentos_tcn' => $alimentoA->cod_alimentos,
            'cant_ref_tcn' => 100.00,
            'uni_ref_tcn' => 'gramos',
            'cal_tcn' => 200.00, // 200 kcal por 100g
            'pro_tcn' => 30.00,  // 30g proteína
            'car_tcn' => 0.00,   // 0g carbohidratos
            'gra_tcn' => 5.00,   // 5g grasas
            'fib_tcn' => 0.00,
            'azu_tcn' => 0.00,
            'sod_tcn' => 70.00,  // 70mg sodio
            'fec_tcn' => '2026-01-01',
            'est_tcn' => 'vigente',
        ]);

        $alimentoB = Alimento::create([
            'nom_ali' => 'Avena con Fruta Test ' . $uniq,
            'tip_ali' => 'ingrediente',
            'src_ali' => 'manual',
            'est_ali' => 'activo',
        ]);

        TablaComposicionNutricional::create([
            'cod_alimentos_tcn' => $alimentoB->cod_alimentos,
            'cant_ref_tcn' => 100.00,
            'uni_ref_tcn' => 'gramos',
            'cal_tcn' => 300.00, // 300 kcal por 100g
            'pro_tcn' => 10.00,  // 10g proteína
            'car_tcn' => 50.00,  // 50g carbohidratos
            'gra_tcn' => 4.00,   // 4g grasas
            'fib_tcn' => 8.00,   // 8g fibra
            'azu_tcn' => 12.00,  // 12g azúcar
            'sod_tcn' => 10.00,  // 10mg sodio
            'fec_tcn' => '2026-01-01',
            'est_tcn' => 'vigente',
        ]);

        // 3. Crear Nutricionista para validar
        $usuarioNut = Usuario::create([
            'nom_usu' => 'Dra',
            'ape_usu' => 'Nutri ' . $uniq,
            'ci_usu' => '7766' . rand(100, 999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1985-01-01',
            'cor_usu' => 'nutri_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $nutricionista = Nutricionista::create([
            'cod_usuarios_nut' => $usuarioNut->cod_usuarios,
            'esp_nut' => 'Clínica',
            'est_nut' => 'activo',
        ]);

        // 4. Crear ventana de 7 días: 2026-05-12 a 2026-05-18
        // Registraremos 3 días de los 7 (adherencia = 3/7 = 0.4286)
        // Día 1: 2026-05-12 (200g Pollo almuerzo 12:30 + 100g Avena desayuno 08:00) -> Cal: 400+300=700, Pro: 60+10=70, Car: 50, Gra: 10+4=14, Fib: 8, Azu: 12, Sod: 140+10=150
        $reg1 = RegistroAlimenticio::create([
            'cod_pacientes_ral' => $paciente->cod_pacientes,
            'fec_ral' => '2026-05-12',
            'est_ral' => 'cerrado',
        ]);
        DetalleRegistroAlimenticio::create([
            'cod_registros_alimenticios_dra' => $reg1->cod_registros_alimenticios,
            'cod_alimentos_dra' => $alimentoB->cod_alimentos,
            'tip_dra' => 'desayuno',
            'can_dra' => 100.00,
            'uni_dra' => 'gramos',
            'hor_dra' => '08:00:00', // 480 min
        ]);
        DetalleRegistroAlimenticio::create([
            'cod_registros_alimenticios_dra' => $reg1->cod_registros_alimenticios,
            'cod_alimentos_dra' => $alimentoA->cod_alimentos,
            'tip_dra' => 'almuerzo',
            'can_dra' => 200.00,
            'uni_dra' => 'gramos',
            'hor_dra' => '12:30:00', // 750 min
        ]);

        ValidacionInformacion::create([
            'cod_registros_alimenticios_vin' => $reg1->cod_registros_alimenticios,
            'cod_nutricionistas_vin' => $nutricionista->cod_nutricionistas,
            'fec_vin' => '2026-05-13',
            'est_vin' => 'aceptado',
        ]);

        // Día 2: 2026-05-14 (100g Pollo cena 20:00 + 100g Avena snack 16:00) -> Cal: 200+300=500, Pro: 30+10=40, Car: 50, Gra: 5+4=9, Fib: 8, Azu: 12, Sod: 70+10=80
        $reg2 = RegistroAlimenticio::create([
            'cod_pacientes_ral' => $paciente->cod_pacientes,
            'fec_ral' => '2026-05-14',
            'est_ral' => 'cerrado',
        ]);
        DetalleRegistroAlimenticio::create([
            'cod_registros_alimenticios_dra' => $reg2->cod_registros_alimenticios,
            'cod_alimentos_dra' => $alimentoA->cod_alimentos,
            'tip_dra' => 'cena',
            'can_dra' => 100.00,
            'uni_dra' => 'gramos',
            'hor_dra' => '20:00:00',
        ]);
        DetalleRegistroAlimenticio::create([
            'cod_registros_alimenticios_dra' => $reg2->cod_registros_alimenticios,
            'cod_alimentos_dra' => $alimentoB->cod_alimentos,
            'tip_dra' => 'merienda',
            'can_dra' => 100.00,
            'uni_dra' => 'gramos',
            'hor_dra' => '16:00:00',
        ]);

        ValidacionInformacion::create([
            'cod_registros_alimenticios_vin' => $reg2->cod_registros_alimenticios,
            'cod_nutricionistas_vin' => $nutricionista->cod_nutricionistas,
            'fec_vin' => '2026-05-15',
            'est_vin' => 'aceptado',
        ]);

        // Día 3: 2026-05-16 (100g Avena desayuno 08:30 + 100g Pollo almuerzo 13:00) -> Cal: 300+200=500, Pro: 10+30=40, Car: 50, Gra: 4+5=9, Fib: 8, Azu: 12, Sod: 10+70=80
        $reg3 = RegistroAlimenticio::create([
            'cod_pacientes_ral' => $paciente->cod_pacientes,
            'fec_ral' => '2026-05-16',
            'est_ral' => 'cerrado',
        ]);
        DetalleRegistroAlimenticio::create([
            'cod_registros_alimenticios_dra' => $reg3->cod_registros_alimenticios,
            'cod_alimentos_dra' => $alimentoB->cod_alimentos,
            'tip_dra' => 'desayuno',
            'can_dra' => 100.00,
            'uni_dra' => 'gramos',
            'hor_dra' => '08:30:00', // 510 min
        ]);
        DetalleRegistroAlimenticio::create([
            'cod_registros_alimenticios_dra' => $reg3->cod_registros_alimenticios,
            'cod_alimentos_dra' => $alimentoA->cod_alimentos,
            'tip_dra' => 'almuerzo',
            'can_dra' => 100.00,
            'uni_dra' => 'gramos',
            'hor_dra' => '13:00:00', // 780 min
        ]);
        // Sin validación para probar ratio_validacion = 2/3 = 0.6667

        // 5. Ejecutar FeatureEngineeringNutricionalService
        $service = app(FeatureEngineeringNutricionalService::class);
        $vector = $service->extraerVector($paciente, '2026-05-18', 7);

        // Verificaciones Matemáticas Controladas:
        // Ventana inclusiva:
        $this->assertEquals('2026-05-12', $vector['fec_inicio_fnu']);
        $this->assertEquals('2026-05-18', $vector['fec_fin_fnu']);
        $this->assertEquals(7, $vector['ventana_dias_fnu']);
        $this->assertEquals(3, $vector['dias_registrados_fnu']);

        // Adherencia y validación:
        $this->assertEquals(0.4286, $vector['adherencia_registro_fnu']); // 3 / 7 = 0.42857 -> 0.4286
        $this->assertEquals(0.6667, $vector['ratio_validacion_fnu']);    // 2 / 3 = 0.66666 -> 0.6667

        // Promedios calóricos y nutricionales sobre los 3 días:
        // Calorías día: [700, 500, 500] -> Media = 1700 / 3 = 566.67
        $this->assertEquals(566.67, $vector['promedio_calorias_fnu']);
        // Desviación estándar muestral de [700, 500, 500] = sqrt(((700-566.67)^2 + 2*(500-566.67)^2)/2) = 115.47
        $this->assertEquals(115.47, $vector['std_calorias_fnu']);

        // Proteínas día: [70, 40, 40] -> Media = 150 / 3 = 50.00g
        $this->assertEquals(50.00, $vector['promedio_proteina_g_fnu']);
        // Carbohidratos día: [50, 50, 50] -> Media = 50.00g
        $this->assertEquals(50.00, $vector['promedio_carbohidratos_g_fnu']);
        // Grasas día: [14, 9, 9] -> Media = 32 / 3 = 10.67g
        $this->assertEquals(10.67, $vector['promedio_grasas_g_fnu']);
        // Fibra día: [8, 8, 8] -> Media = 8.00g
        $this->assertEquals(8.00, $vector['promedio_fibra_g_fnu']);
        // Azúcar día: [12, 12, 12] -> Media = 12.00g
        $this->assertEquals(12.00, $vector['promedio_azucar_g_fnu']);
        // Sodio día: [150, 80, 80] -> Media = 310 / 3 = 103.33mg
        $this->assertEquals(103.33, $vector['promedio_sodio_mg_fnu']);

        // Porcentajes de macronutrientes derivados de energía:
        // E_pro = 50 * 4 = 200 kcal
        // E_car = 50 * 4 = 200 kcal
        // E_gra = 10.67 * 9 = 96.03 kcal
        // E_total = 496.03 kcal
        // % Pro = 200 / 496.03 = 40.32%
        // % Car = 200 / 496.03 = 40.32%
        // % Gra = 96.03 / 496.03 = 19.36%
        $this->assertEquals(40.32, $vector['pct_proteina_fnu']);
        $this->assertEquals(40.32, $vector['pct_carbohidratos_fnu']);
        $this->assertEquals(19.36, $vector['pct_grasa_fnu']);

        // Frecuencias de momentos de comida:
        // Desayuno en días 1 y 3 (2 de 3) -> 2/3 = 0.6667
        $this->assertEquals(0.6667, $vector['frecuencia_desayuno_fnu']);
        // Cena en día 2 (1 de 3) -> 1/3 = 0.3333
        $this->assertEquals(0.3333, $vector['frecuencia_cena_fnu']);
        // Snack (merienda en día 2) (1 de 3) -> 1/3 = 0.3333
        $this->assertEquals(0.3333, $vector['frecuencia_snack_fnu']);
        // Promedio de momentos por día: Día 1: 2, Día 2: 2, Día 3: 2 -> Media = 2.00
        $this->assertEquals(2.00, $vector['promedio_comidas_dia_fnu']);

        // Variabilidad horaria:
        // Desayunos a las 08:00 (480 min) y 08:30 (510 min) -> std = 21.21 min
        // Almuerzos a las 12:30 (750 min) y 13:00 (780 min) -> std = 21.21 min
        // Variabilidad media = (21.21 + 21.21) / 2 = 21.21 min
        $this->assertEquals(21.21, $vector['variabilidad_horaria_min_fnu']);

        // Contexto antropométrico:
        $this->assertEquals(24.69, $vector['imc_fnu']); // 80 / (1.8^2)
        $this->assertEquals(30, $vector['edad_fnu']);   // 1996 a 2026 = 30
        $this->assertEquals('masculino', $vector['sexo_fnu']);

        // Banderas en NULL en v1.0:
        $this->assertNull($vector['dias_calorias_altas_fnu']);
        $this->assertNull($vector['dias_proteina_baja_fnu']);
        $this->assertNull($vector['dias_fibra_baja_fnu']);
        $this->assertNull($vector['dias_azucar_alta_fnu']);
        $this->assertNull($vector['cambio_peso_fnu']);

        // Version y vector JSON:
        $this->assertEquals('v1.0', $vector['version_features_fnu']);
        $this->assertIsArray($vector['vector_json_fnu']);

        // 6. Probar persistencia del Snapshot
        $snapshot = $service->guardarSnapshot($paciente, '2026-05-18', 7);
        $this->assertInstanceOf(FeatureNutricional::class, $snapshot);
        $this->assertEquals(566.67, (float) $snapshot->promedio_calorias_fnu);

        // Probar no sobreescritura
        $snapshot2 = $service->guardarSnapshot($paciente, '2026-05-18', 7);
        $this->assertEquals($snapshot->cod_features_nutricionales, $snapshot2->cod_features_nutricionales);
        $this->assertEquals(1, FeatureNutricional::where('cod_pacientes_fnu', $paciente->cod_pacientes)->count());

        // Limpieza
        $snapshot->delete();
        $reg1->detalles()->delete();
        $reg1->validacionesInformacion()->delete();
        $reg1->delete();
        $reg2->detalles()->delete();
        $reg2->validacionesInformacion()->delete();
        $reg2->delete();
        $reg3->detalles()->delete();
        $reg3->delete();
        $alimentoA->composicionesNutricionales()->delete();
        $alimentoA->delete();
        $alimentoB->composicionesNutricionales()->delete();
        $alimentoB->delete();
        $paciente->delete();
        $nutricionista->delete();
        $usuarioPac->delete();
        $usuarioNut->delete();
    }
}
