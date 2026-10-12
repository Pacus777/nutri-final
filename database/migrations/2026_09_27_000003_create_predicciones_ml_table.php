<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predicciones_ml', function (Blueprint $table) {
            $table->id('cod_predicciones_ml');
            
            // Relaciones
            $table->foreignId('cod_pacientes_pml')
                ->constrained('pacientes', 'cod_pacientes')
                ->cascadeOnDelete();
            
            // Restricción: No permitir eliminación en cascada de modelos con predicciones históricas
            $table->foreignId('cod_modelos_ml_pml')
                ->constrained('modelos_ml', 'cod_modelos_ml')
                ->restrictOnDelete();
                
            $table->foreignId('cod_features_nutricionales_pml')
                ->nullable()
                ->constrained('features_nutricionales', 'cod_features_nutricionales')
                ->nullOnDelete();

            $table->timestamp('fec_prediccion_pml');
            
            // Salida algorítmica (Inmutable)
            $table->string('resultado_principal_pml', 100); // Salida original del algoritmo
            $table->decimal('score_confianza_pml', 5, 4)->nullable(); // Nullable para K-Means
            $table->json('probabilidades_clases_pml')->nullable();    // Para clasificadores supervisados
            $table->json('explicabilidad_pml')->nullable();           // Distancias a centroides, silhouette o SHAP
            
            // Validación médica / Human-in-the-loop
            $table->string('resultado_validado_pml', 100)->nullable(); // Corrección del nutricionista si difiere
            $table->foreignId('cod_nutricionistas_validador_pml')
                ->nullable()
                ->constrained('nutricionistas', 'cod_nutricionistas')
                ->nullOnDelete();
            $table->string('estado_validacion_pml', 30)->default('pendiente'); // 'pendiente', 'validado_correcto', 'corregido', 'descartado'
            $table->timestamp('fec_validacion_pml')->nullable();
            $table->text('obs_validacion_pml')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['cod_pacientes_pml', 'fec_prediccion_pml'], 'idx_pml_paciente_fecha');
            $table->index('cod_modelos_ml_pml', 'idx_pml_modelo');
            $table->index('estado_validacion_pml', 'idx_pml_estado_val');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predicciones_ml');
    }
};
