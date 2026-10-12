<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clusters_modelo', function (Blueprint $table) {
            $table->id('cod_clusters_modelo');
            
            // Modelo y número de cluster
            $table->foreignId('cod_modelos_ml_clm')
                ->constrained('modelos_ml', 'cod_modelos_ml')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_cluster_clm'); // 0, 1, 2, ...
            
            // Patrón alimenticio de negocio asociado
            $table->foreignId('cod_patrones_alimenticios_clm')
                ->nullable()
                ->constrained('patrones_alimenticios', 'cod_patrones_alimenticios')
                ->nullOnDelete();

            // Vector de centroide y descripciones
            $table->json('centroide_json_clm');                 // Vector de medias del centroide por feature
            $table->text('descripcion_automatica_clm');         // Generada por el sistema basada en estadísticas
            $table->text('descripcion_profesional_clm')->nullable(); // Interpretación médica redactada por nutricionista

            // Estado y validación profesional del cluster (pendiente, validado, ajustado)
            $table->string('estado_validacion_clm', 30)->default('pendiente');
            $table->foreignId('cod_nutricionistas_validador_clm')
                ->nullable()
                ->constrained('nutricionistas', 'cod_nutricionistas')
                ->nullOnDelete();
            $table->timestamp('fec_validacion_clm')->nullable();

            $table->timestamps();

            // Restricciones e índices
            $table->unique(['cod_modelos_ml_clm', 'numero_cluster_clm'], 'uq_clm_modelo_cluster');
            $table->index('cod_patrones_alimenticios_clm', 'idx_clm_patron');
            $table->index('estado_validacion_clm', 'idx_clm_estado_val');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clusters_modelo');
    }
};
