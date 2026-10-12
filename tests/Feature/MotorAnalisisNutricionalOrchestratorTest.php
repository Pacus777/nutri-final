<?php

namespace Tests\Feature;

use App\Models\ClusterModelo;
use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\PatronAlimenticio;
use App\Models\PrediccionMl;
use App\Models\RecomendacionNutricional;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\MlInferenceService;
use App\Services\MotorAnalisisNutricionalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MotorAnalisisNutricionalOrchestratorTest extends TestCase
{
    use DatabaseTransactions;

    private function crearEcosistemaNutricionistaPaciente(): array
    {
        $uniq = Str::random(6);

        $usuarioNut = Usuario::create([
            'nom_usu' => 'Dra Nutricionista',
            'ape_usu' => 'Motor ' . $uniq,
            'ci_usu' => '99' . rand(10000, 99999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1988-03-20',
            'cor_usu' => 'nutri_motor_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $rolNutri = Rol::firstOrCreate(
            ['nom_rol' => 'Nutricionista'],
            ['des_rol' => 'Rol Nutricionista', 'est_rol' => 'activo']
        );
        $usuarioNut->roles()->syncWithoutDetaching([$rolNutri->cod_roles]);

        $nutricionista = Nutricionista::create([
            'cod_usuarios_nut' => $usuarioNut->cod_usuarios,
            'esp_nut' => 'Nutrición Clínica y Analítica',
            'num_col_nut' => 'COL-' . rand(1000, 9999),
            'est_nut' => 'activo',
        ]);

        $usuarioPac = Usuario::create([
            'nom_usu' => 'Paciente',
            'ape_usu' => 'Motor ' . $uniq,
            'ci_usu' => '88' . rand(10000, 99999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1996-07-12',
            'cor_usu' => 'pac_motor_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $paciente = Paciente::create([
            'cod_usuarios_pac' => $usuarioPac->cod_usuarios,
            'sex_pac' => 'F',
            'pes_pac' => 60.0,
            'estatura_pac' => 1.65,
            'est_pac' => 'activo',
        ]);

        return compact('usuarioNut', 'nutricionista', 'usuarioPac', 'paciente');
    }

    private function crearSnapshotFeature(Paciente $paciente, array $overrides = []): FeatureNutricional
    {
        return FeatureNutricional::create(array_merge([
            'cod_pacientes_fnu' => $paciente->cod_pacientes,
            'fec_inicio_fnu' => '2026-05-12',
            'fec_fin_fnu' => '2026-05-18',
            'ventana_dias_fnu' => 7,
            'dias_registrados_fnu' => 6,
            'dias_completos_fnu' => 5,
            'adherencia_registro_fnu' => 0.8571,
            'ratio_validacion_fnu' => 0.80,
            'promedio_calorias_fnu' => 2100.0,
            'std_calorias_fnu' => 120.0,
            'pct_proteina_fnu' => 16.0,
            'pct_carbohidratos_fnu' => 54.0,
            'pct_grasa_fnu' => 30.0,
            'promedio_fibra_g_fnu' => 18.0,
            'promedio_azucar_g_fnu' => 32.0,
            'promedio_sodio_mg_fnu' => 1350.0,
            'frecuencia_desayuno_fnu' => 0.85,
            'frecuencia_cena_fnu' => 0.90,
            'frecuencia_snack_fnu' => 0.40,
            'promedio_comidas_dia_fnu' => 3.2,
            'variabilidad_horaria_min_fnu' => 35.0,
            'imc_fnu' => 22.0,
            'version_features_fnu' => 'v1.0',
            'origen_datos_fnu' => FeatureNutricional::ORIGEN_CLINICO_REAL,
            'patient_group_id_fnu' => 'PG-' . $paciente->cod_pacientes,
        ], $overrides));
    }

    private function registrarModelosCandidatos(): array
    {
        $modeloKMeans = ModeloMl::firstOrCreate(
            [
                'tipo_algoritmo_mml' => 'kmeans',
                'tarea_mml' => 'clustering_patrones',
            ],
            [
                'nombre_mml' => 'K-Means Clustering Nutricional v1',
                'version_mml' => 'v1.0-k4-candidate',
                'version_features_mml' => 'v1.0',
                'origen_dataset_mml' => 'sintetico_controlado',
                'ruta_artefacto_mml' => 'nutrition-analytics/artifacts/kmeans_v1_k4_candidate.joblib',
                'est_mml' => ModeloMl::ESTADO_VALIDACION,
                'numero_muestras_mml' => 300,
                'numero_pacientes_mml' => 300,
                'random_seed_mml' => 42,
            ]
        );

        $modeloDT = ModeloMl::firstOrCreate(
            [
                'tipo_algoritmo_mml' => 'decision_tree',
                'tarea_mml' => 'clasificacion_riesgo',
            ],
            [
                'nombre_mml' => 'Decision Tree Riesgo Nutricional Experimental v1',
                'version_mml' => 'v1.0-dt-candidate',
                'version_features_mml' => 'v1.0',
                'origen_dataset_mml' => 'sintetico_controlado',
                'ruta_artefacto_mml' => 'nutrition-analytics/artifacts/decision_tree_v1_candidate.joblib',
                'est_mml' => ModeloMl::ESTADO_VALIDACION,
                'metricas_rendimiento_mml' => ['accuracy' => 0.9667, 'macro_f1' => 0.9665],
                'features_requeridas_mml' => ['promedio_calorias_fnu', 'pct_proteina_fnu', 'pct_carbohidratos_fnu', 'pct_grasa_fnu', 'fibra_por_1000kcal', 'azucar_por_1000kcal', 'sodio_por_1000kcal', 'frecuencia_desayuno_fnu', 'frecuencia_cena_fnu', 'promedio_comidas_dia_fnu', 'variabilidad_horaria_min_fnu', 'imc_fnu'],
                'numero_muestras_mml' => 300,
                'numero_pacientes_mml' => 300,
                'random_seed_mml' => 42,
            ]
        );

        return compact('modeloKMeans', 'modeloDT');
    }

    public function test_orquestacion_completa_con_datos_suficientes(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);
        $this->registrarModelosCandidatos();

        $motor = app(MotorAnalisisNutricionalService::class);
        $resultado = $motor->orquestarAnalisisCompleto($eco['paciente'], $snap);

        $this->assertTrue($resultado['disponible']);
        $this->assertEquals($eco['paciente']->cod_pacientes, $resultado['paciente_id']);
        $this->assertEquals($snap->cod_features_nutricionales, $resultado['snapshot_id']);

        // Estructura unificada
        $this->assertArrayHasKey('periodo', $resultado);
        $this->assertArrayHasKey('indicadores', $resultado);
        $this->assertArrayHasKey('alertas_nutricionales', $resultado);
        $this->assertArrayHasKey('kmeans', $resultado);
        $this->assertArrayHasKey('decision_tree', $resultado);
        $this->assertArrayHasKey('recomendaciones', $resultado);
        $this->assertArrayHasKey('historial_evolucion', $resultado);
        $this->assertStringContainsString('Orientativo', $resultado['disclaimer']);
    }

    public function test_separacion_conceptual_heuristica_kmeans_y_decision_tree(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);
        $this->registrarModelosCandidatos();

        $motor = app(MotorAnalisisNutricionalService::class);
        $resultado = $motor->orquestarAnalisisCompleto($eco['paciente'], $snap);

        // K-Means: Clustering no supervisado de patrones
        $this->assertNotNull($resultado['kmeans']['cluster_asignado']);
        $this->assertArrayHasKey('distancias_centroides', $resultado['kmeans']);
        $this->assertStringContainsString('Cluster', $resultado['kmeans']['resultado_principal']);

        // Decision Tree: Clasificación supervisada de riesgo
        $this->assertNotNull($resultado['decision_tree']['clase_predicha']);
        $this->assertArrayHasKey('probabilidades', $resultado['decision_tree']);
        $this->assertArrayHasKey('camino_decision', $resultado['decision_tree']);
        $this->assertNotEmpty($resultado['decision_tree']['camino_decision']);

        // Ambos conservan origen sintético y estado validación
        $this->assertEquals('sintetico_controlado', $resultado['kmeans']['origen_dataset']);
        $this->assertEquals('sintetico_controlado', $resultado['decision_tree']['origen_dataset']);
        $this->assertEquals('validacion', $resultado['kmeans']['estado_modelo']);
        $this->assertEquals('validacion', $resultado['decision_tree']['estado_modelo']);
    }

    public function test_falla_controlada_de_python_no_crea_falsa_prediccion(): void
    {
        $this->registrarModelosCandidatos();
        $modeloDT = ModeloMl::where('tipo_algoritmo_mml', 'decision_tree')->firstOrFail();

        $service = app(MlInferenceService::class);
        $respuesta = $service->generarRespuestaDecisionTreeNoDisponible($modeloDT, 'Test Python Offline');

        $this->assertFalse($respuesta['disponible']);
        $this->assertEquals('no_disponible', $respuesta['estado_modelo']);
        $this->assertNull($respuesta['clase_predicha']);
        $this->assertNull($respuesta['probabilidad_clase']);
        $this->assertEmpty($respuesta['camino_decision']);
        $this->assertStringContainsString('no está disponible temporalmente', $respuesta['advertencia']);
    }

    public function test_recomendaciones_asistidas_permanecen_pendientes_y_con_trazabilidad(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        // Snapshot con alta azúcar y alto sodio
        $snap = $this->crearSnapshotFeature($eco['paciente'], [
            'promedio_calorias_fnu' => 2000.0,
            'promedio_azucar_g_fnu' => 100.0,  // 50 g/1000kcal > 44.1
            'promedio_sodio_mg_fnu' => 3200.0,  // 1600 mg/1000kcal > 1465.7
            'promedio_fibra_g_fnu' => 10.0,   // 5 g/1000kcal < 11.95
        ]);

        $motor = app(MotorAnalisisNutricionalService::class);
        $motor->orquestarAnalisisCompleto($eco['paciente'], $snap);

        $recs = RecomendacionNutricional::where('cod_pacientes_rec', $eco['paciente']->cod_pacientes)
            ->where('cod_features_nutricionales_rec', $snap->cod_features_nutricionales)
            ->get();

        $this->assertGreaterThan(0, $recs->count());

        foreach ($recs as $rec) {
            $this->assertEquals(RecomendacionNutricional::ESTADO_PENDIENTE, $rec->estado_rec);
            $this->assertEquals(RecomendacionNutricional::ORIGEN_ASISTIDA_IA, $rec->origen_rec);
            $this->assertNotEmpty($rec->justificacion_rec);
            $this->assertEquals($snap->cod_features_nutricionales, $rec->cod_features_nutricionales_rec);
        }
    }

    public function test_aprobacion_edicion_y_descarte_profesional_de_recomendaciones(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);
        $motor = app(MotorAnalisisNutricionalService::class);

        // 1. Crear recomendación pendiente
        $rec = RecomendacionNutricional::create([
            'cod_pacientes_rec' => $eco['paciente']->cod_pacientes,
            'cod_features_nutricionales_rec' => $snap->cod_features_nutricionales,
            'fec_rec' => now()->toDateString(),
            'des_rec' => 'Sugerencia de prueba para aumentar fibra.',
            'tip_rec' => 'aumento_fibra',
            'origen_rec' => RecomendacionNutricional::ORIGEN_ASISTIDA_IA,
            'estado_rec' => RecomendacionNutricional::ESTADO_PENDIENTE,
        ]);

        // 2. Aprobar
        $motor->aprobarRecomendacion($rec, $eco['nutricionista']);
        $this->assertEquals(RecomendacionNutricional::ESTADO_APROBADA, $rec->fresh()->estado_rec);
        $this->assertEquals($eco['nutricionista']->cod_nutricionistas, $rec->fresh()->cod_nutricionistas_rec);

        // 3. Crear otra para editar
        $rec2 = RecomendacionNutricional::create([
            'cod_pacientes_rec' => $eco['paciente']->cod_pacientes,
            'cod_features_nutricionales_rec' => $snap->cod_features_nutricionales,
            'fec_rec' => now()->toDateString(),
            'des_rec' => 'Texto original sugerido por IA.',
            'tip_rec' => 'reduccion_azucar',
            'origen_rec' => RecomendacionNutricional::ORIGEN_ASISTIDA_IA,
            'estado_rec' => RecomendacionNutricional::ESTADO_PENDIENTE,
        ]);

        $motor->aprobarRecomendacion($rec2, $eco['nutricionista'], 'Texto ajustado por el nutricionista.');
        $this->assertEquals(RecomendacionNutricional::ESTADO_EDITADA, $rec2->fresh()->estado_rec);
        $this->assertEquals(RecomendacionNutricional::ORIGEN_AJUSTE_PROFESIONAL, $rec2->fresh()->origen_rec);
        $this->assertEquals('Texto ajustado por el nutricionista.', $rec2->fresh()->des_rec);

        // 4. Crear otra para descartar
        $rec3 = RecomendacionNutricional::create([
            'cod_pacientes_rec' => $eco['paciente']->cod_pacientes,
            'cod_features_nutricionales_rec' => $snap->cod_features_nutricionales,
            'fec_rec' => now()->toDateString(),
            'des_rec' => 'Sugerencia no aplicable.',
            'tip_rec' => 'horarios',
            'origen_rec' => RecomendacionNutricional::ORIGEN_ASISTIDA_IA,
            'estado_rec' => RecomendacionNutricional::ESTADO_PENDIENTE,
        ]);

        $motor->descartarRecomendacion($rec3, $eco['nutricionista']);
        $this->assertEquals(RecomendacionNutricional::ESTADO_DESCARTADA, $rec3->fresh()->estado_rec);

        // 5. Crear manual directa
        $recManual = $motor->crearRecomendacionManual(
            $eco['paciente'],
            $eco['nutricionista'],
            'Prescripción directa elaborada en consulta.'
        );
        $this->assertEquals(RecomendacionNutricional::ESTADO_APROBADA, $recManual->estado_rec);
        $this->assertEquals(RecomendacionNutricional::ORIGEN_MANUAL, $recManual->origen_rec);
    }

    public function test_orquestacion_sin_datos_suficientes_de_snapshot(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        // Paciente sin ningún snapshot en features_nutricionales
        $motor = app(MotorAnalisisNutricionalService::class);
        $resultado = $motor->orquestarAnalisisCompleto($eco['paciente']);

        $this->assertFalse($resultado['disponible']);
        $this->assertEquals('insuficiente', $resultado['calidad_datos']);
        $this->assertNull($resultado['kmeans']);
        $this->assertNull($resultado['decision_tree']);
        $this->assertStringContainsString('No existen snapshots', $resultado['mensaje']);
    }

    public function test_endpoints_http_recomendaciones_nutricionales(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);

        $rec = RecomendacionNutricional::create([
            'cod_pacientes_rec' => $eco['paciente']->cod_pacientes,
            'cod_features_nutricionales_rec' => $snap->cod_features_nutricionales,
            'fec_rec' => now()->toDateString(),
            'des_rec' => 'Sugerencia para prueba HTTP.',
            'tip_rec' => 'aumento_fibra',
            'origen_rec' => RecomendacionNutricional::ORIGEN_ASISTIDA_IA,
            'estado_rec' => RecomendacionNutricional::ESTADO_PENDIENTE,
        ]);

        $responseAprobar = $this->actingAs($eco['usuarioNut'])
            ->withSession(['nom_rol_activo' => 'Nutricionista', 'rol_activo' => 'Nutricionista'])
            ->post(route('nutricionista.recomendaciones.aprobar', $rec->cod_recomendaciones_nutricionales), [
                'des_rec' => 'Aprobada vía HTTP con ajuste profesional.',
            ]);

        $responseAprobar->assertRedirect();
        $this->assertEquals(RecomendacionNutricional::ESTADO_EDITADA, $rec->fresh()->estado_rec);
        $this->assertEquals('Aprobada vía HTTP con ajuste profesional.', $rec->fresh()->des_rec);
    }
}
