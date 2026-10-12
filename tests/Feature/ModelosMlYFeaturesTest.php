<?php

namespace Tests\Feature;

use App\Models\ClusterModelo;
use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\PatronAlimenticio;
use App\Models\PrediccionMl;
use App\Models\RiesgoNutricional;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModelosMlYFeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'nutricional-dilan-sistema-final',
            'database.connections.mysql.port' => 3360,
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
    }

    public function test_creacion_y_relaciones_del_ecosistema_ml(): void
    {
        // 1. Crear usuario y paciente
        $uniq = Str::random(6);
        $usuarioPac = Usuario::create([
            'nom_usu' => 'Paciente',
            'ape_usu' => 'Prueba ' . $uniq,
            'ci_usu' => '12345' . rand(100, 999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1995-05-15',
            'cor_usu' => 'paciente_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $paciente = Paciente::create([
            'cod_usuarios_pac' => $usuarioPac->cod_usuarios,
            'sex_pac' => 'masculino',
            'pes_pac' => 75.50,
            'estatura_pac' => 1.75,
            'est_pac' => 'activo',
        ]);

        // 2. Crear usuario y nutricionista
        $uniqNut = Str::random(6);
        $usuarioNut = Usuario::create([
            'nom_usu' => 'Nutri',
            'ape_usu' => 'Especialista ' . $uniqNut,
            'ci_usu' => '87654' . rand(100, 999),
            'ext_ci_usu' => 'CB',
            'fec_nac_usu' => '1988-08-20',
            'cor_usu' => 'nutri_' . $uniqNut . '@test.com',
            'cla_usu' => Hash::make('password123'),
            'est_usu' => 'activo',
        ]);

        $nutricionista = Nutricionista::create([
            'cod_usuarios_nut' => $usuarioNut->cod_usuarios,
            'esp_nut' => 'Nutrición Deportiva',
            'est_nut' => 'activo',
        ]);

        // 3. Crear snapshot inmutable de FeatureNutricional
        $feature = FeatureNutricional::create([
            'cod_pacientes_fnu' => $paciente->cod_pacientes,
            'version_features_fnu' => 'v1.0',
            'fec_inicio_fnu' => now()->subDays(30)->toDateString(),
            'fec_fin_fnu' => now()->toDateString(),
            'ventana_dias_fnu' => 30,
            'dias_registrados_fnu' => 25,
            'adherencia_registro_fnu' => 0.8333,
            'ratio_validacion_fnu' => 0.9200,
            'promedio_calorias_fnu' => 2100.50,
            'std_calorias_fnu' => 180.25,
            'promedio_proteina_g_fnu' => 125.00,
            'promedio_carbohidratos_g_fnu' => 240.00,
            'promedio_grasas_g_fnu' => 65.00,
            'promedio_fibra_g_fnu' => 28.50,
            'promedio_azucar_g_fnu' => 35.00,
            'promedio_sodio_mg_fnu' => 1800.00,
            'pct_carbohidratos_fnu' => 45.70,
            'pct_proteina_fnu' => 23.80,
            'pct_grasa_fnu' => 27.80,
            'frecuencia_desayuno_fnu' => 0.9000,
            'frecuencia_cena_fnu' => 0.8500,
            'frecuencia_snack_fnu' => 0.4000,
            'promedio_comidas_dia_fnu' => 3.50,
            'variabilidad_horaria_min_fnu' => 25.50,
            'cobertura_composicion_pct_fnu' => 95.00,
            'dias_calorias_altas_fnu' => 3,
            'dias_proteina_baja_fnu' => 1,
            'dias_fibra_baja_fnu' => 2,
            'dias_azucar_alta_fnu' => 1,
            'imc_fnu' => 24.65,
            'cambio_peso_fnu' => -1.20,
            'edad_fnu' => 30,
            'sexo_fnu' => 'masculino',
            'vector_json_fnu' => ['cal' => 2100.5, 'pro' => 125.0],
        ]);

        $this->assertInstanceOf(FeatureNutricional::class, $feature);
        $this->assertEquals($paciente->cod_pacientes, $feature->paciente->cod_pacientes);

        // 4. Crear Modelo ML en estado borrador
        $modelo = ModeloMl::create([
            'nombre_mml' => 'K-Means Clustering Patrones ' . $uniq,
            'tipo_algoritmo_mml' => 'kmeans',
            'tarea_mml' => 'clustering',
            'version_mml' => 'v1.0.0',
            'version_features_mml' => 'v1.0',
            'origen_dataset_mml' => 'clinico_real',
            'numero_muestras_mml' => 100,
            'numero_pacientes_mml' => 20,
            'parametros_mml' => ['n_clusters' => 4, 'random_state' => 42],
            'metricas_rendimiento_mml' => ['silhouette_score' => 0.62],
            'est_mml' => ModeloMl::ESTADO_BORRADOR,
        ]);

        $this->assertEquals(ModeloMl::ESTADO_BORRADOR, $modelo->est_mml);

        // 5. Crear Patrón y Cluster del Modelo
        $patron = PatronAlimenticio::create([
            'nom_pat' => 'Patrón Proteico Alto ' . $uniq,
            'des_pat' => 'Dieta con alto porcentaje proteico y horario consistente',
        ]);

        $cluster = ClusterModelo::create([
            'cod_modelos_ml_clm' => $modelo->cod_modelos_ml,
            'numero_cluster_clm' => 0,
            'cod_patrones_alimenticios_clm' => $patron->cod_patrones_alimenticios,
            'centroide_json_clm' => ['cal' => 2200, 'pro' => 130],
            'descripcion_automatica_clm' => 'Consumo calórico adecuado con proteína elevada',
            'estado_validacion_clm' => ClusterModelo::ESTADO_PENDIENTE,
        ]);

        $this->assertEquals(ClusterModelo::ESTADO_PENDIENTE, $cluster->estado_validacion_clm);

        // 6. Crear Predicción ML
        $prediccion = PrediccionMl::create([
            'cod_pacientes_pml' => $paciente->cod_pacientes,
            'cod_modelos_ml_pml' => $modelo->cod_modelos_ml,
            'cod_features_nutricionales_pml' => $feature->cod_features_nutricionales,
            'fec_prediccion_pml' => now(),
            'resultado_principal_pml' => 'Cluster 0',
            'score_confianza_pml' => null, // K-Means
            'explicabilidad_pml' => ['distancia_centroide' => 12.4, 'silhouette_individual' => 0.68],
            'estado_validacion_pml' => PrediccionMl::ESTADO_PENDIENTE,
        ]);

        $this->assertEquals('Cluster 0', $prediccion->resultado_principal_pml);
        $this->assertEquals(PrediccionMl::ESTADO_PENDIENTE, $prediccion->estado_validacion_pml);

        // 7. Crear Riesgo Nutricional vinculado a la predicción
        $riesgo = RiesgoNutricional::create([
            'cod_pacientes_rnu' => $paciente->cod_pacientes,
            'fec_rnu' => now()->toDateString(),
            'tip_rnu' => 'exceso_sodio',
            'niv_rnu' => RiesgoNutricional::NIVEL_MODERADO,
            'des_rnu' => 'Consumo de sodio ligeramente sobre el umbral recomendado',
            'origen_rnu' => RiesgoNutricional::ORIGEN_ALGORITMO_ML,
            'cod_predicciones_ml_rnu' => $prediccion->cod_predicciones_ml,
            'confianza_rnu' => 0.8500,
            'estado_validacion_rnu' => RiesgoNutricional::ESTADO_PENDIENTE,
        ]);

        $this->assertEquals(RiesgoNutricional::ORIGEN_ALGORITMO_ML, $riesgo->origen_rnu);
        $this->assertEquals(RiesgoNutricional::ESTADO_PENDIENTE, $riesgo->estado_validacion_rnu);
        $this->assertEquals($prediccion->cod_predicciones_ml, $riesgo->prediccionMl->cod_predicciones_ml);

        // 8. Validar relaciones inversas en Paciente y Nutricionista
        $this->assertTrue($paciente->featuresNutricionales()->exists());
        $this->assertTrue($paciente->prediccionesMl()->exists());
        $this->assertTrue($paciente->riesgosNutricionales()->exists());

        // 9. Simular validación del nutricionista sin sobreescribir la salida algorítmica
        $prediccion->update([
            'resultado_validado_pml' => 'Cluster 0 - Confirmado',
            'cod_nutricionistas_validador_pml' => $nutricionista->cod_nutricionistas,
            'estado_validacion_pml' => PrediccionMl::ESTADO_VALIDADO_CORRECTO,
            'fec_validacion_pml' => now(),
            'obs_validacion_pml' => 'El paciente cumple el patrón de forma consistente.',
        ]);

        $this->assertEquals('Cluster 0', $prediccion->resultado_principal_pml);
        $this->assertEquals('Cluster 0 - Confirmado', $prediccion->resultado_validado_pml);
        $this->assertEquals(PrediccionMl::ESTADO_VALIDADO_CORRECTO, $prediccion->estado_validacion_pml);

        // 10. Limpiar datos de prueba
        $riesgo->delete();
        $prediccion->delete();
        $cluster->delete();
        $patron->delete();
        $modelo->delete();
        $feature->delete();
        $paciente->delete();
        $nutricionista->delete();
        $usuarioPac->delete();
        $usuarioNut->delete();
    }
}
