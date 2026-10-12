<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_ml', function (Blueprint $table) {
            $table->id('cod_modelos_ml');
            
            // Identificación y tipo
            $table->string('nombre_mml', 100);              // ej: 'Clustering de Patrones Alimenticios'
            $table->string('tipo_algoritmo_mml', 50);        // 'kmeans', 'decision_tree', 'random_forest', 'heuristico'
            $table->string('tarea_mml', 50);                 // 'clustering', 'clasificacion_riesgo', 'deteccion_anomalias'
            $table->string('version_mml', 30);               // ej: 'v1.0.0', 'v2026.1'
            $table->string('version_features_mml', 30);      // Versión de pipeline de features esperada (ej: 'v1.0')
            $table->string('ruta_artefacto_mml', 255)->nullable(); // Path local a artefacto serializado (.joblib/.json)

            // Linaje y procedencia del dataset de entrenamiento
            $table->string('origen_dataset_mml', 100);       // 'sintetico_controlado', 'clinico_real', 'hibrido_aumentado'
            $table->string('hash_dataset_mml', 64)->nullable(); // SHA256 del dataset para reproducibilidad
            $table->unsignedInteger('numero_muestras_mml');  // Total de filas/vectores utilizados
            $table->unsignedInteger('numero_pacientes_mml'); // Total de pacientes únicos incluidos
            $table->date('fec_inicio_dataset_mml')->nullable();
            $table->date('fec_fin_dataset_mml')->nullable();
            $table->integer('random_seed_mml')->nullable();  // Semilla utilizada (ej: 42)

            // Configuración técnica y evaluación
            $table->json('parametros_mml')->nullable();      // Hiperparámetros ({n_clusters: 4, max_depth: 5, ...})
            $table->json('metricas_rendimiento_mml')->nullable(); // {silhouette_score: 0.58, accuracy: 0.89, f1_macro: 0.86}
            $table->json('features_requeridas_mml')->nullable();  // Lista de columnas que componen la entrada

            // Estado del ciclo de vida (borrador, en_entrenamiento, validacion, activo, deprecado)
            $table->string('est_mml', 20)->default('borrador');
            $table->timestamp('entrenado_en_mml')->nullable();
            $table->timestamps();

            // Restricciones e índices
            $table->unique(['nombre_mml', 'version_mml'], 'uq_mml_nombre_version');
            $table->index(['tipo_algoritmo_mml', 'est_mml'], 'idx_mml_tipo_estado');
            $table->index('tarea_mml', 'idx_mml_tarea');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_ml');
    }
};
