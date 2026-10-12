<?php

namespace Tests\Feature;

use App\Models\AsignacionPatronPaciente;
use App\Models\ClusterModelo;
use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\PatronAlimenticio;
use App\Models\PrediccionMl;
use App\Models\Usuario;
use App\Services\MlInferenceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MlInferenceAndValidationTest extends TestCase
{
    use DatabaseTransactions;

    private function crearEcosistemaNutricionistaPaciente(): array
    {
        $uniq = Str::random(6);

        $usuarioNut = Usuario::create([
            'nom_usu' => 'Dra Nutricionista',
            'ape_usu' => 'Validadora ' . $uniq,
            'ci_usu' => '99' . rand(10000, 99999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1988-03-20',
            'cor_usu' => 'nutri_ml_' . $uniq . '@test.com',
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
            'ape_usu' => 'Evaluado ' . $uniq,
            'ci_usu' => '88' . rand(10000, 99999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1996-07-12',
            'cor_usu' => 'pac_ml_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $paciente = Paciente::create([
            'cod_usuarios_pac' => $usuarioPac->cod_usuarios,
            'sex_pac' => 'masculino',
            'pes_pac' => 74.5,
            'estatura_pac' => 1.76,
            'est_pac' => 'activo',
        ]);

        $snapshot = FeatureNutricional::create([
            'cod_pacientes_fnu' => $paciente->cod_pacientes,
            'version_features_fnu' => 'v1.0',
            'fec_inicio_fnu' => now()->subDays(7)->format('Y-m-d'),
            'fec_fin_fnu' => now()->subDay()->format('Y-m-d'),
            'ventana_dias_fnu' => 7,
            'dias_registrados_fnu' => 6,
            'adherencia_registro_fnu' => 0.8571,
            'promedio_calorias_fnu' => 2150.0,
            'pct_proteina_fnu' => 18.5,
            'pct_carbohidratos_fnu' => 52.0,
            'pct_grasa_fnu' => 29.5,
            'promedio_fibra_g_fnu' => 24.0,
            'promedio_azucar_g_fnu' => 45.0,
            'promedio_sodio_mg_fnu' => 2100.0,
            'std_calorias_fnu' => 120.0,
            'frecuencia_desayuno_fnu' => 0.85,
            'frecuencia_cena_fnu' => 0.85,
            'frecuencia_snack_fnu' => 0.40,
            'promedio_comidas_dia_fnu' => 3.5,
            'cobertura_composicion_pct_fnu' => 100.0,
            'calidad_datos_fnu' => 'alta',
        ]);

        return compact('usuarioNut', 'nutricionista', 'usuarioPac', 'paciente', 'snapshot');
    }

    public function test_candidate_model_and_scaler_artifacts_exist_and_in_validation_state(): void
    {
        $modelo = ModeloMl::where('tipo_algoritmo_mml', 'kmeans')
            ->where('est_mml', 'validacion')
            ->first();

        $this->assertNotNull($modelo, 'Debe existir un modelo K-Means candidato registrado en estado validacion.');
        $this->assertEquals('sintetico_controlado', $modelo->origen_dataset_mml);
        $this->assertEquals('v1.0', $modelo->version_features_mml);

        $scalerPath = base_path('nutrition-analytics/artifacts/scaler_v1.joblib');
        $modelPath = Str::startsWith($modelo->ruta_artefacto_mml, ['/', '\\', 'C:', 'c:']) 
            ? $modelo->ruta_artefacto_mml 
            : base_path($modelo->ruta_artefacto_mml);

        $this->assertFileExists($scalerPath, 'El scaler_v1.joblib debe existir en artifacts.');
        $this->assertFileExists($modelPath, 'El archivo joblib del modelo debe existir.');
    }

    public function test_kmeans_inference_service_reproducibility_and_metrics(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $service = app(MlInferenceService::class);

        $pred1 = $service->guardarPrediccion($eco['snapshot']);
        $pred2 = $service->guardarPrediccion($eco['snapshot']);

        // Reproducibilidad
        $this->assertEquals($pred1->resultado_principal_pml, $pred2->resultado_principal_pml);
        $this->assertEquals($pred1->cluster_asignado_pml, $pred2->cluster_asignado_pml);

        // Sin métricas probabilísticas falsas en K-Means
        $this->assertNull($pred1->score_confianza_pml, 'score_confianza_pml debe ser null para KMeans');
        $this->assertNull($pred1->probabilidades_clases_pml, 'probabilidades_clases_pml debe ser null para KMeans');

        // Explicabilidad contiene distancias a centroides
        $explicabilidad = $pred1->explicabilidad_pml;
        $this->assertIsArray($explicabilidad);
        $this->assertArrayHasKey('distancia_centroide_asignado', $explicabilidad);
        $this->assertArrayHasKey('distancias_centroides', $explicabilidad);
        $this->assertArrayHasKey('origen_dataset_modelo', $explicabilidad);
        $this->assertEquals('sintetico_controlado', $explicabilidad['origen_dataset_modelo']);
        $this->assertCount(4, $explicabilidad['distancias_centroides']);

        // La distancia asignada debe coincidir con la distancia mínima
        $distAsignada = (float) $explicabilidad['distancia_centroide_asignado'];
        $distMinima = min(array_values($explicabilidad['distancias_centroides']));
        $this->assertEqualsWithDelta($distMinima, $distAsignada, 0.001);
    }

    public function test_artisan_command_ml_predict_snapshot_persists_prediction(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();

        $this->artisan('ml:predict-snapshot', ['feature_id' => $eco['snapshot']->cod_features_nutricionales])
            ->assertExitCode(0);

        $pred = PrediccionMl::where('cod_features_nutricionales_pml', $eco['snapshot']->cod_features_nutricionales)->first();
        $this->assertNotNull($pred);
        $this->assertStringStartsWith('Cluster ', $pred->resultado_principal_pml);
        $this->assertNull($pred->score_confianza_pml);
    }

    public function test_cluster_without_pattern_does_not_create_asignacion_patron_paciente(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $service = app(MlInferenceService::class);

        // Asegurar que el cluster asignado no tiene patrón alimenticio vinculado
        $candidateModel = ModeloMl::where('tipo_algoritmo_mml', 'kmeans')->where('est_mml', 'validacion')->first();
        ClusterModelo::where('cod_modelos_ml_clm', $candidateModel->cod_modelos_ml)->update([
            'cod_patrones_alimenticios_clm' => null,
        ]);

        $pred = $service->guardarPrediccion($eco['snapshot']);

        $asignacion = AsignacionPatronPaciente::where('cod_pacientes_app', $eco['paciente']->cod_pacientes)->first();
        $this->assertNull($asignacion, 'No debe crearse asignacion_patron_paciente si el cluster no tiene patrón asociado.');
    }

    public function test_nutritionist_can_validate_cluster_and_assign_food_pattern(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $candidateModel = ModeloMl::where('tipo_algoritmo_mml', 'kmeans')->where('est_mml', 'validacion')->first();
        $cluster = ClusterModelo::where('cod_modelos_ml_clm', $candidateModel->cod_modelos_ml)->first();

        // Crear patrón alimenticio
        $patron = PatronAlimenticio::firstOrCreate(
            ['nom_pat' => 'Patrón Equilibrado Mediterráneo'],
            ['des_pat' => 'Patrón controlado rico en fibra y grasas insaturadas', 'est_pat' => 'activo']
        );

        $response = $this->actingAs($eco['usuarioNut'])
            ->withSession(['nom_rol_activo' => 'Nutricionista', 'rol_activo' => 'Nutricionista'])
            ->post(route('nutricionista.ml.clusters.validar', $cluster->cod_clusters_modelo), [
                'cod_patrones_alimenticios_clm' => $patron->cod_patrones_alimenticios,
                'descripcion_profesional_clm' => 'Validado clínicamente como ingesta equilibrada con adecuada densidad de fibra.',
                'estado_validacion_clm' => 'validado',
            ]);

        $response->assertSessionHasNoErrors();
        $cluster->refresh();

        $this->assertEquals($patron->cod_patrones_alimenticios, $cluster->cod_patrones_alimenticios_clm);
        $this->assertEquals('validado', $cluster->estado_validacion_clm);
        $this->assertStringContainsString('Validado clínicamente', $cluster->descripcion_profesional_clm);
    }

    public function test_nutritionist_can_validate_individual_patient_prediction(): void
    {
        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $service = app(MlInferenceService::class);
        $pred = $service->guardarPrediccion($eco['snapshot']);

        $response = $this->actingAs($eco['usuarioNut'])
            ->withSession(['nom_rol_activo' => 'Nutricionista', 'rol_activo' => 'Nutricionista'])
            ->post(route('nutricionista.ml.predicciones.validar', $pred->cod_predicciones_ml), [
                'estado_validacion_pml' => 'validado_correcto',
                'obs_validacion_pml' => 'Corresponde con el registro dietético reportado de 7 días.',
            ]);

        $response->assertSessionHasNoErrors();
        $pred->refresh();

        $this->assertEquals('validado_correcto', $pred->estado_validacion_pml);
        $this->assertEquals('Corresponde con el registro dietético reportado de 7 días.', $pred->obs_validacion_pml);
        $this->assertEquals($eco['nutricionista']->cod_nutricionistas, $pred->cod_nutricionistas_validador_pml);
    }

    public function test_joblib_centroids_match_database_clusters_with_numerical_tolerance(): void
    {
        $candidateModel = ModeloMl::where('tipo_algoritmo_mml', 'kmeans')
            ->where('est_mml', 'validacion')
            ->first();
        $this->assertNotNull($candidateModel);

        $scalerPath = base_path('nutrition-analytics/artifacts/scaler_v1.joblib');
        $modelPath = base_path('nutrition-analytics/artifacts/kmeans_v1_k4_candidate.joblib');

        $this->assertFileExists($scalerPath);
        $this->assertFileExists($modelPath);

        // Extraer centroides del .joblib vía Python
        $pythonScript = base_path('nutrition-analytics/scripts/audit_centroids.py');
        if (!file_exists($pythonScript)) {
            // Script de un sólo uso / prueba
            file_put_contents($pythonScript, '
import sys, os, joblib, json
sys.path.insert(0, os.path.abspath("nutrition-analytics"))
scaler_dict = joblib.load("nutrition-analytics/artifacts/scaler_v1.joblib")
scaler = scaler_dict["scaler"]
model_dict = joblib.load("nutrition-analytics/artifacts/kmeans_v1_k4_candidate.joblib")
kmeans = model_dict["kmeans_model"]
feat_names = model_dict["feature_names"]
inv = scaler.inverse_transform(kmeans.cluster_centers_)
out = {}
for i, c in enumerate(inv):
    out[i] = {k: float(v) for k, v in zip(feat_names, c)}
print(json.dumps(out))
');
        }

        $pythonBinary = 'C:/Users/THINK PAD/AppData/Local/Programs/Python/Python312/python.exe';
        $output = shell_exec("\"{$pythonBinary}\" \"{$pythonScript}\"");
        $joblibCentroids = json_decode(trim($output), true);
        $this->assertIsArray($joblibCentroids, 'Los centroides deben poder extraerse del .joblib');

        $clustersBD = $candidateModel->clusters()->orderBy('numero_cluster_clm')->get();
        $this->assertCount(4, $clustersBD);

        foreach ($clustersBD as $cl) {
            $clNum = $cl->numero_cluster_clm;
            $this->assertArrayHasKey($clNum, $joblibCentroids);
            $joblibC = $joblibCentroids[$clNum];
            $bdC = $cl->centroide_json_clm;

            foreach ($joblibC as $featureName => $joblibVal) {
                $this->assertArrayHasKey($featureName, $bdC);
                $bdVal = (float) $bdC[$featureName];
                $diff = abs($joblibVal - $bdVal);
                $this->assertLessThan(0.001, $diff, "Diferencia en {$featureName} para Cluster {$clNum} supera tolerancia: {$diff}");
            }
        }
    }

    public function test_model_identity_version_and_artifact_coherence(): void
    {
        $service = app(MlInferenceService::class);
        $modelo = $service->getModeloKMeansCandidato();

        $this->assertNotNull($modelo);
        $this->assertEquals('v1.0.0-k4', $modelo->version_mml);
        $this->assertEquals('sintetico_controlado', $modelo->origen_dataset_mml);
        $this->assertEquals(0.4462, (float) $modelo->metricas_rendimiento_mml['silhouette_score']);

        $eco = $this->crearEcosistemaNutricionistaPaciente();
        $pred = $service->guardarPrediccion($eco['snapshot'], $modelo);

        $this->assertEquals('v1.0.0-k4', $pred->explicabilidad_pml['version_modelo']);
        $this->assertEquals('sintetico_controlado', $pred->explicabilidad_pml['origen_dataset_modelo']);
    }
}
