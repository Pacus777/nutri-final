<?php

namespace Tests\Feature;

use App\Models\ClusterModelo;
use App\Models\ModeloMl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class MlRegisterCandidateModelTest extends TestCase
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

    public function test_artisan_register_candidate_model_from_json_metadata(): void
    {
        $uniq = Str::random(6);
        $tempJson = storage_path('framework/testing/test_metadata_' . $uniq . '.json');

        $mockPayload = [
            'modelo' => [
                'nombre_mml' => 'Test Clustering Patrones ' . $uniq,
                'tipo_algoritmo_mml' => 'kmeans',
                'tarea_mml' => 'clustering',
                'version_mml' => 'v1.0-test',
                'version_features_mml' => 'v1.0',
                'ruta_artefacto_mml' => 'nutrition-analytics/artifacts/kmeans_test.joblib',
                'origen_dataset_mml' => 'sintetico_controlado',
                'hash_dataset_mml' => hash('sha256', 'mock_dataset_content'),
                'numero_muestras_mml' => 300,
                'numero_pacientes_mml' => 300,
                'fec_inicio_dataset_mml' => '2026-09-01',
                'fec_fin_dataset_mml' => '2026-09-07',
                'random_seed_mml' => 42,
                'parametros_mml' => [
                    'n_clusters' => 3,
                    'random_state' => 42,
                ],
                'metricas_rendimiento_mml' => [
                    'silhouette_score' => 0.4850,
                    'inertia' => 1250.50,
                ],
                'features_requeridas_mml' => [
                    'promedio_calorias_fnu',
                    'pct_proteina_fnu',
                    'pct_carbohidratos_fnu',
                    'pct_grasa_fnu',
                ],
                'est_mml' => 'validacion',
                'entrenado_en_mml' => now()->format('Y-m-d H:i:s'),
            ],
            'clusters' => [
                [
                    'numero_cluster' => 0,
                    'centroide' => ['promedio_calorias_fnu' => 2000.0, 'pct_proteina_fnu' => 20.0],
                    'descripcion_automatica' => 'Cluster 0 estadístico controlado',
                ],
                [
                    'numero_cluster' => 1,
                    'centroide' => ['promedio_calorias_fnu' => 2500.0, 'pct_proteina_fnu' => 15.0],
                    'descripcion_automatica' => 'Cluster 1 estadístico controlado',
                ],
                [
                    'numero_cluster' => 2,
                    'centroide' => ['promedio_calorias_fnu' => 1500.0, 'pct_proteina_fnu' => 25.0],
                    'descripcion_automatica' => 'Cluster 2 estadístico controlado',
                ],
            ],
        ];

        File::put($tempJson, json_encode($mockPayload, JSON_PRETTY_PRINT));

        // Ejecutar comando Artisan
        $this->artisan('ml:register-candidate', [
            '--file' => $tempJson,
        ])->assertSuccessful();

        // Validar registro en base de datos
        $modelo = ModeloMl::where('nombre_mml', 'Test Clustering Patrones ' . $uniq)->first();
        $this->assertNotNull($modelo);
        $this->assertEquals('kmeans', $modelo->tipo_algoritmo_mml);
        $this->assertEquals('sintetico_controlado', $modelo->origen_dataset_mml);
        $this->assertEquals(ModeloMl::ESTADO_VALIDACION, $modelo->est_mml);
        $this->assertEquals(300, $modelo->numero_muestras_mml);
        $this->assertEquals(300, $modelo->numero_pacientes_mml);
        $this->assertEquals(42, $modelo->random_seed_mml);

        // Validar clusters asociados
        $clusters = ClusterModelo::where('cod_modelos_ml_clm', $modelo->cod_modelos_ml)->get();
        $this->assertCount(3, $clusters);
        $this->assertEquals(ClusterModelo::ESTADO_PENDIENTE, $clusters[0]->estado_validacion_clm);
        $this->assertNull($clusters[0]->cod_patrones_alimenticios_clm); // Sin mapeo clínico definitivo aún
        $this->assertIsArray($clusters[0]->centroide_json_clm);

        // Limpieza
        if (File::exists($tempJson)) {
            File::delete($tempJson);
        }
        $modelo->clusters()->delete();
        $modelo->delete();
    }
}
