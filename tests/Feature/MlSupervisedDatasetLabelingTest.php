<?php

namespace Tests\Feature;

use App\Models\Alimento;
use App\Models\TablaComposicionNutricional;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\EtiquetaRiesgoProfesional;
use App\Models\FeatureNutricional;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MlSupervisedDatasetLabelingTest extends TestCase
{
    use DatabaseTransactions;

    private function crearNutricionista(): array
    {
        $uniq = Str::random(6);

        $rolNutri = Rol::firstOrCreate(
            ['nom_rol' => 'Nutricionista'],
            ['des_rol' => 'Profesional de Nutrición', 'est_rol' => 'activo']
        );

        $usuario = Usuario::create([
            'nom_usu' => 'Dra. Nutri',
            'ape_usu' => 'Clínica ' . $uniq,
            'ci_usu' => '8888' . rand(100, 999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1985-05-15',
            'cor_usu' => 'nutri_' . $uniq . '@clinica-operativa.com',
            'cla_usu' => Hash::make('secret123'),
            'est_usu' => 'activo',
        ]);

        $usuario->roles()->syncWithoutDetaching([$rolNutri->cod_roles]);

        $nutricionista = Nutricionista::create([
            'cod_usuarios_nut' => $usuario->cod_usuarios,
            'esp_nut' => 'Nutrición Clínica',
            'est_nut' => 'activo',
        ]);

        return compact('usuario', 'nutricionista');
    }

    private function crearPacienteRealConRegistros(int $diasRegistrados = 6): array
    {
        $uniq = Str::random(6);

        $usuario = Usuario::create([
            'nom_usu' => 'Paciente',
            'ape_usu' => 'Real ' . $uniq,
            'ci_usu' => '7777' . rand(100, 999),
            'ext_ci_usu' => 'CB',
            'fec_nac_usu' => '1990-03-20',
            'cor_usu' => 'paciente_real_' . $uniq . '@salud-paciente.org',
            'cla_usu' => Hash::make('secret123'),
            'est_usu' => 'activo',
        ]);

        $paciente = Paciente::create([
            'cod_usuarios_pac' => $usuario->cod_usuarios,
            'sex_pac' => 'femenino',
            'pes_pac' => 60.0,
            'estatura_pac' => 1.62,
            'est_pac' => 'activo',
        ]);

        // Crear un alimento con composición si no existe
        $alimento = Alimento::firstOrCreate(
            ['nom_ali' => 'Avena con manzana test'],
            ['tip_ali' => 'alimento', 'des_ali' => 'cereales', 'est_ali' => 'activo']
        );

        TablaComposicionNutricional::firstOrCreate(
            ['cod_alimentos_tcn' => $alimento->cod_alimentos],
            [
                'por_ref_tcn' => '100g',
                'cant_ref_tcn' => 100.0,
                'uni_ref_tcn' => 'g',
                'cal_tcn' => 250.0,
                'pro_tcn' => 8.0,
                'car_tcn' => 45.0,
                'gra_tcn' => 4.0,
                'fib_tcn' => 6.0,
                'azu_tcn' => 10.0,
                'sod_tcn' => 50.0,
                'fec_tcn' => now()->toDateString(),
                'est_tcn' => TablaComposicionNutricional::ESTADO_VIGENTE,
            ]
        );

        // Crear registros de 6 días en una ventana de 7 días
        $startDate = Carbon::now()->subDays(14)->startOfDay();
        for ($i = 0; $i < $diasRegistrados; $i++) {
            $fec = $startDate->copy()->addDays($i);
            $reg = RegistroAlimenticio::create([
                'cod_pacientes_ral' => $paciente->cod_pacientes,
                'fec_ral' => $fec->toDateString(),
                'tip_com_ral' => 'desayuno',
                'hor_ral' => '08:30:00',
                'est_ral' => 'completo',
            ]);

            DetalleRegistroAlimenticio::create([
                'cod_registros_alimenticios_dra' => $reg->cod_registros_alimenticios,
                'cod_alimentos_dra' => $alimento->cod_alimentos,
                'tip_dra' => DetalleRegistroAlimenticio::TIPO_DESAYUNO,
                'can_dra' => 150.0,
                'uni_dra' => DetalleRegistroAlimenticio::UNIDAD_GRAMOS,
                'hor_dra' => '08:30:00',
            ]);
        }

        return compact('usuario', 'paciente');
    }

    public function test_generacion_exclusiva_de_snapshots_clinicos_reales()
    {
        $real = $this->crearPacienteRealConRegistros(6);

        // Crear paciente de prueba con correo @test.com
        $testUser = Usuario::create([
            'nom_usu' => 'Test',
            'ape_usu' => 'Automated ' . Str::random(4),
            'ci_usu' => '9999' . rand(100, 999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1995-01-01',
            'cor_usu' => 'bot_' . Str::random(4) . '@test.com',
            'cla_usu' => Hash::make('secret123'),
            'est_usu' => 'activo',
        ]);
        $testPaciente = Paciente::create([
            'cod_usuarios_pac' => $testUser->cod_usuarios,
            'sex_pac' => 'masculino',
            'pes_pac' => 80.0,
            'estatura_pac' => 1.80,
            'est_pac' => 'activo',
        ]);

        $this->artisan('ml:generate-clinical-snapshots', [
            '--window' => 7,
            '--patient' => $real['paciente']->cod_pacientes,
        ])->assertSuccessful();

        $snapshotReal = FeatureNutricional::where('cod_pacientes_fnu', $real['paciente']->cod_pacientes)
            ->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)
            ->first();

        $this->assertNotNull($snapshotReal);
        $this->assertEquals(FeatureNutricional::ORIGEN_CLINICO_REAL, $snapshotReal->origen_datos_fnu);
        $this->assertStringStartsWith('ml:generate-clinical-snapshots', $snapshotReal->referencia_origen_fnu);

        // Verificar que el paciente test no tenga snapshots generados como clinico_real
        $snapshotTest = FeatureNutricional::where('cod_pacientes_fnu', $testPaciente->cod_pacientes)->first();
        $this->assertNull($snapshotTest);
    }

    public function test_ausencia_de_duplicados_en_generacion_snapshots()
    {
        $real = $this->crearPacienteRealConRegistros(6);

        // Primera ejecución
        $this->artisan('ml:generate-clinical-snapshots', [
            '--window' => 7,
            '--patient' => $real['paciente']->cod_pacientes,
        ])->assertSuccessful();

        $countInitial = FeatureNutricional::where('cod_pacientes_fnu', $real['paciente']->cod_pacientes)->count();
        $this->assertGreaterThan(0, $countInitial);

        // Segunda ejecución sin --force
        $this->artisan('ml:generate-clinical-snapshots', [
            '--window' => 7,
            '--patient' => $real['paciente']->cod_pacientes,
        ])->assertSuccessful();

        $countAfter = FeatureNutricional::where('cod_pacientes_fnu', $real['paciente']->cod_pacientes)->count();
        $this->assertEquals($countInitial, $countAfter);
    }

    public function test_etiquetado_valido_y_persistencia_profesional()
    {
        $nutri = $this->crearNutricionista();
        $real = $this->crearPacienteRealConRegistros(6);

        $this->artisan('ml:generate-clinical-snapshots', [
            '--window' => 7,
            '--patient' => $real['paciente']->cod_pacientes,
        ])->assertSuccessful();

        $snapshot = FeatureNutricional::where('cod_pacientes_fnu', $real['paciente']->cod_pacientes)->firstOrFail();

        $response = $this->actingAs($nutri['usuario'])
            ->withSession(['nom_rol_activo' => 'Nutricionista', 'rol_activo' => 'Nutricionista'])
            ->post(route('nutricionista.etiquetado-riesgo.store', $snapshot->cod_features_nutricionales), [
                'opcion_riesgo' => 'moderado',
                'tipo_riesgo_erp' => 'deficit_fibra_moderado',
                'observacion_profesional_erp' => 'Aporte calórico aceptable pero bajo en fibra.',
                'version_criterio_erp' => 'v1.0',
            ]);

        $response->assertRedirect(route('nutricionista.etiquetado-riesgo.index'));

        $this->assertDatabaseHas('etiquetas_riesgo_profesional', [
            'cod_features_nutricionales_erp' => $snapshot->cod_features_nutricionales,
            'cod_nutricionistas_erp' => $nutri['nutricionista']->cod_nutricionistas,
            'nivel_riesgo_erp' => 'moderado',
            'estado_etiqueta_erp' => 'valida',
            'tipo_riesgo_erp' => 'deficit_fibra_moderado',
        ]);
    }

    public function test_informacion_insuficiente_produce_nivel_riesgo_null()
    {
        $nutri = $this->crearNutricionista();
        $real = $this->crearPacienteRealConRegistros(6);

        $this->artisan('ml:generate-clinical-snapshots', [
            '--window' => 7,
            '--patient' => $real['paciente']->cod_pacientes,
        ])->assertSuccessful();

        $snapshot = FeatureNutricional::where('cod_pacientes_fnu', $real['paciente']->cod_pacientes)->firstOrFail();

        $response = $this->actingAs($nutri['usuario'])
            ->withSession(['nom_rol_activo' => 'Nutricionista', 'rol_activo' => 'Nutricionista'])
            ->post(route('nutricionista.etiquetado-riesgo.store', $snapshot->cod_features_nutricionales), [
                'opcion_riesgo' => 'insuficiente',
                'observacion_profesional_erp' => 'Variabilidad atípica y sin suficientes registros validados.',
            ]);

        $response->assertRedirect(route('nutricionista.etiquetado-riesgo.index'));

        $etiqueta = EtiquetaRiesgoProfesional::where('cod_features_nutricionales_erp', $snapshot->cod_features_nutricionales)
            ->where('cod_nutricionistas_erp', $nutri['nutricionista']->cod_nutricionistas)
            ->firstOrFail();

        $this->assertEquals('insuficiente', $etiqueta->estado_etiqueta_erp);
        $this->assertNull($etiqueta->nivel_riesgo_erp);
    }

    public function test_multiples_nutricionistas_pueden_valorar_mismo_snapshot_sin_sobrescribir()
    {
        $nutriA = $this->crearNutricionista();
        $nutriB = $this->crearNutricionista();
        $real = $this->crearPacienteRealConRegistros(6);

        $this->artisan('ml:generate-clinical-snapshots', [
            '--window' => 7,
            '--patient' => $real['paciente']->cod_pacientes,
        ])->assertSuccessful();

        $snapshot = FeatureNutricional::where('cod_pacientes_fnu', $real['paciente']->cod_pacientes)->firstOrFail();

        // Nutricionista A evalúa como 'bajo'
        $this->actingAs($nutriA['usuario'])
            ->withSession(['nom_rol_activo' => 'Nutricionista', 'rol_activo' => 'Nutricionista'])
            ->post(route('nutricionista.etiquetado-riesgo.store', $snapshot->cod_features_nutricionales), [
                'opcion_riesgo' => 'bajo',
            ])->assertRedirect(route('nutricionista.etiquetado-riesgo.index'));

        // Nutricionista B evalúa como 'alto'
        $this->actingAs($nutriB['usuario'])
            ->withSession(['nom_rol_activo' => 'Nutricionista', 'rol_activo' => 'Nutricionista'])
            ->post(route('nutricionista.etiquetado-riesgo.store', $snapshot->cod_features_nutricionales), [
                'opcion_riesgo' => 'alto',
            ])->assertRedirect(route('nutricionista.etiquetado-riesgo.index'));

        // Verificar que existan 2 registros independientes
        $this->assertDatabaseHas('etiquetas_riesgo_profesional', [
            'cod_features_nutricionales_erp' => $snapshot->cod_features_nutricionales,
            'cod_nutricionistas_erp' => $nutriA['nutricionista']->cod_nutricionistas,
            'nivel_riesgo_erp' => 'bajo',
        ]);

        $this->assertDatabaseHas('etiquetas_riesgo_profesional', [
            'cod_features_nutricionales_erp' => $snapshot->cod_features_nutricionales,
            'cod_nutricionistas_erp' => $nutriB['nutricionista']->cod_nutricionistas,
            'nivel_riesgo_erp' => 'alto',
        ]);

        $totalEvaluaciones = EtiquetaRiesgoProfesional::where('cod_features_nutricionales_erp', $snapshot->cod_features_nutricionales)->count();
        $this->assertEquals(2, $totalEvaluaciones);
    }

    public function test_aislamiento_de_datos_y_anonimizacion_groupkfold_en_exportacion()
    {
        $nutri = $this->crearNutricionista();
        $real = $this->crearPacienteRealConRegistros(6);

        // Snapshot clínico válido
        $snapValido = FeatureNutricional::create([
            'cod_pacientes_fnu' => $real['paciente']->cod_pacientes,
            'version_features_fnu' => 'v1.0',
            'fec_inicio_fnu' => '2026-09-01',
            'fec_fin_fnu' => '2026-09-07',
            'ventana_dias_fnu' => 7,
            'dias_registrados_fnu' => 6,
            'adherencia_registro_fnu' => 0.8571,
            'promedio_calorias_fnu' => 2000.0,
            'origen_datos_fnu' => FeatureNutricional::ORIGEN_CLINICO_REAL,
        ]);

        EtiquetaRiesgoProfesional::create([
            'cod_features_nutricionales_erp' => $snapValido->cod_features_nutricionales,
            'cod_pacientes_erp' => $real['paciente']->cod_pacientes,
            'cod_nutricionistas_erp' => $nutri['nutricionista']->cod_nutricionistas,
            'nivel_riesgo_erp' => 'bajo',
            'estado_etiqueta_erp' => 'valida',
            'version_criterio_erp' => 'v1.0',
            'fec_evaluacion_erp' => now(),
        ]);

        // Snapshot de prueba automatizada (NO debe exportarse)
        $snapPrueba = FeatureNutricional::create([
            'cod_pacientes_fnu' => $real['paciente']->cod_pacientes,
            'version_features_fnu' => 'v1.0',
            'fec_inicio_fnu' => '2026-08-01',
            'fec_fin_fnu' => '2026-08-07',
            'ventana_dias_fnu' => 7,
            'dias_registrados_fnu' => 7,
            'adherencia_registro_fnu' => 1.0000,
            'promedio_calorias_fnu' => 2200.0,
            'origen_datos_fnu' => FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA,
        ]);

        EtiquetaRiesgoProfesional::create([
            'cod_features_nutricionales_erp' => $snapPrueba->cod_features_nutricionales,
            'cod_pacientes_erp' => $real['paciente']->cod_pacientes,
            'cod_nutricionistas_erp' => $nutri['nutricionista']->cod_nutricionistas,
            'nivel_riesgo_erp' => 'alto',
            'estado_etiqueta_erp' => 'valida',
            'version_criterio_erp' => 'v1.0',
            'fec_evaluacion_erp' => now(),
        ]);

        $outputPath = base_path('nutrition-analytics/datasets/test_supervised_export.csv');
        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('ml:export-supervised-dataset', [
            '--window' => 7,
            '--features-version' => 'v1.0',
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->assertTrue(File::exists($outputPath));
        $lines = file($outputPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Encabezado + 1 registro válido (el de prueba no debe aparecer)
        $this->assertCount(2, $lines);
        $header = $lines[0];
        $row = $lines[1];

        $this->assertStringContainsString('patient_group_id', $header);
        $this->assertStringContainsString('GRP_', $row);
        $this->assertStringContainsString('bajo', $row);
        $this->assertStringNotContainsString('alto', $row); // El de prueba con 'alto' fue excluido
        $this->assertStringNotContainsString($real['usuario']->nom_usu, $row); // No fuga de datos personales

        // Limpieza de archivo de prueba
        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }
    }
}
