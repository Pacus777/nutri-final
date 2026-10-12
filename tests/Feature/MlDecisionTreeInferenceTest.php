<?php

namespace Tests\Feature;

use App\Models\EtiquetaRiesgoProfesional;
use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\PrediccionMl;
use App\Models\Usuario;
use App\Services\MlInferenceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MlDecisionTreeInferenceTest extends TestCase
{
    use DatabaseTransactions;

    private function crearEcosistemaNutricionistaPaciente(): array
    {
        $uniq = Str::random(6);

        $usuarioNut = Usuario::create([
            'nom_usu' => 'Dra Nutricionista',
            'ape_usu' => 'DT Test ' . $uniq,
            'ci_usu' => '99' . rand(10000, 99999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1988-03-20',
            'cor_usu' => 'nutri_dt_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $rolNutri = \App\Models\Rol::firstOrCreate(
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
            'ape_usu' => 'DT ' . $uniq,
            'ci_usu' => '88' . rand(10000, 99999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1996-07-12',
            'cor_usu' => 'pac_dt_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $paciente = Paciente::create([
            'cod_usuarios_pac' => $usuarioPac->cod_usuarios,
            'sex_pac' => 'F',
            'pes_pac' => 58.5,
            'estatura_pac' => 1.62,
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
            'promedio_calorias_fnu' => 2050.0,
            'std_calorias_fnu' => 120.0,
            'pct_proteina_fnu' => 16.5,
            'pct_carbohidratos_fnu' => 53.5,
            'pct_grasa_fnu' => 30.0,
            'promedio_fibra_g_fnu' => 18.0,
            'promedio_azucar_g_fnu' => 32.0,
            'promedio_sodio_mg_fnu' => 1350.0,
            'frecuencia_desayuno_fnu' => 0.85,
            'frecuencia_cena_fnu' => 0.90,
            'frecuencia_snack_fnu' => 0.40,
            'promedio_comidas_dia_fnu' => 3.2,
            'variabilidad_horaria_min_fnu' => 35.0,
            'imc_fnu' => 22.3,
            'version_features_fnu' => 'v1.0',
            'origen_datos_fnu' => FeatureNutricional::ORIGEN_CLINICO_REAL,
            'patient_group_id_fnu' => 'PG-' . $paciente->cod_pacientes,
        ], $overrides));
    }

    private function obtenerOCrearModeloDecisionTree(): ModeloMl
    {
        return ModeloMl::firstOrCreate(
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
    }

    public function test_carga_correcta_del_artefacto_y_compatibilidad_features(): void
    {
        $modelo = $this->obtenerOCrearModeloDecisionTree();

        $this->assertNotNull($modelo);
        $this->assertEquals('decision_tree', $modelo->tipo_algoritmo_mml);
        $this->assertEquals('clasificacion_riesgo', $modelo->tarea_mml);
        $this->assertEquals('v1.0', $modelo->version_features_mml);
        $this->assertEquals(ModeloMl::ESTADO_VALIDACION, $modelo->est_mml);
        $this->assertEquals('sintetico_controlado', $modelo->origen_dataset_mml);

        $service = app(MlInferenceService::class);
        $candidato = $service->getModeloDecisionTreeCandidato();
        $this->assertNotNull($candidato);
        $this->assertEquals($modelo->cod_modelos_ml, $candidato->cod_modelos_ml);
    }

    public function test_inferencia_decision_tree_reproducible_y_probabilidades_suman_uno(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);
        $modelo = $this->obtenerOCrearModeloDecisionTree();

        $service = app(MlInferenceService::class);
        $inf1 = $service->ejecutarInferenciaDecisionTree($snap, $modelo);
        $inf2 = $service->ejecutarInferenciaDecisionTree($snap, $modelo);

        // Reproducibilidad
        $this->assertEquals($inf1['clase_predicha'], $inf2['clase_predicha']);
        $this->assertEquals($inf1['probabilidad_clase'], $inf2['probabilidad_clase']);
        $this->assertEquals($inf1['probabilidades'], $inf2['probabilidades']);

        // Probabilidades suman 1.0 y clase predicha es la máxima
        $probs = $inf1['probabilidades'];
        $sumaProbs = array_sum($probs);
        $this->assertEqualsWithDelta(1.0, $sumaProbs, 0.01);

        $maxProbClase = array_keys($probs, max($probs))[0];
        $this->assertEquals($maxProbClase, $inf1['clase_predicha']);
        $this->assertEquals($probs[$maxProbClase], $inf1['probabilidad_clase']);

        // Origen sintético visible y advertencia
        $this->assertEquals('sintetico_controlado', $inf1['origen_dataset']);
        $this->assertStringContainsString('orientativa', $inf1['advertencia']);
    }

    public function test_persistencia_en_predicciones_ml_con_explicabilidad_y_camino_decision(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);
        $modelo = $this->obtenerOCrearModeloDecisionTree();

        $service = app(MlInferenceService::class);
        $prediccion = $service->guardarPrediccionDecisionTree($snap, $modelo);

        $this->assertInstanceOf(PrediccionMl::class, $prediccion);
        $this->assertEquals($eco['paciente']->cod_pacientes, $prediccion->cod_pacientes_pml);
        $this->assertEquals($modelo->cod_modelos_ml, $prediccion->cod_modelos_ml_pml);
        $this->assertEquals($snap->cod_features_nutricionales, $prediccion->cod_features_nutricionales_pml);
        $this->assertEquals(PrediccionMl::ESTADO_PENDIENTE, $prediccion->estado_validacion_pml);
        $this->assertNotNull($prediccion->score_confianza_pml);
        $this->assertIsArray($prediccion->probabilidades_clases_pml);

        // Explicabilidad completa
        $exp = $prediccion->explicabilidad_pml;
        $this->assertArrayHasKey('camino_decision', $exp);
        $this->assertArrayHasKey('variables_involucradas', $exp);
        $this->assertArrayHasKey('advertencia', $exp);
        $this->assertArrayHasKey('version_modelo', $exp);
        $this->assertArrayHasKey('origen_dataset_modelo', $exp);
        $this->assertEquals('sintetico_controlado', $exp['origen_dataset_modelo']);
    }

    public function test_no_creacion_automatica_de_etiqueta_profesional(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);
        $modelo = $this->obtenerOCrearModeloDecisionTree();

        $conteoEtiquetasAntes = EtiquetaRiesgoProfesional::where('cod_features_nutricionales_erp', $snap->cod_features_nutricionales)->count();

        $service = app(MlInferenceService::class);
        $service->guardarPrediccionDecisionTree($snap, $modelo);

        $conteoEtiquetasDespues = EtiquetaRiesgoProfesional::where('cod_features_nutricionales_erp', $snap->cod_features_nutricionales)->count();

        // NO debe haberse creado ninguna etiqueta clínica profesional automáticamente
        $this->assertEquals($conteoEtiquetasAntes, $conteoEtiquetasDespues);
        $this->assertEquals(0, $conteoEtiquetasDespues);
    }

    public function test_pantalla_ciega_de_etiquetado_no_expone_prediccion_ml(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);
        $modelo = $this->obtenerOCrearModeloDecisionTree();

        $service = app(MlInferenceService::class);
        $prediccion = $service->guardarPrediccionDecisionTree($snap, $modelo);

        // Visitar pantalla de etiquetado
        $response = $this->actingAs($eco['usuarioNut'])
            ->withSession(['nom_rol_activo' => 'Nutricionista', 'rol_activo' => 'Nutricionista'])
            ->get(route('nutricionista.etiquetado-riesgo.index'));

        $response->assertStatus(200);

        // La vista de etiquetado ciego no debe exponer la predicción ni su explicabilidad
        $response->assertInertia(function ($page) use ($prediccion) {
            $page->component('Nutricionistas/EtiquetadoRiesgo/Index');
            $props = $page->toArray()['props'];
            $jsonProps = json_encode($props);

            // Asegurar que no hay fuga de la predicción del árbol en la lista ciega de etiquetado
            $this->assertStringNotContainsString('"camino_decision"', $jsonProps);
            $this->assertStringNotContainsString($prediccion->resultado_principal_pml, $jsonProps);
        });
    }

    public function test_comando_artisan_ml_predict_risk(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $snap = $this->crearSnapshotFeature($eco['paciente']);
        $this->obtenerOCrearModeloDecisionTree();

        $exitCode = Artisan::call('ml:predict-risk', [
            'feature_id' => $snap->cod_features_nutricionales,
        ]);

        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('INFERENCIA EXPERIMENTAL: DECISION TREE', $output);
        $this->assertStringContainsString('sintetico_controlado', $output);
        $this->assertStringContainsString('Camino de Decisión en el Árbol', $output);
    }
}
