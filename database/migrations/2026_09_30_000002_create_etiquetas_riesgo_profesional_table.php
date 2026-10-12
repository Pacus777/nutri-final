<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('etiquetas_riesgo_profesional', function (Blueprint $table) {
            $table->id('cod_etiquetas_riesgo_profesional');

            // Snapshot evaluado (sin cascade delete para preservar la trazabilidad histórica de auditoría)
            $table->unsignedBigInteger('cod_features_nutricionales_erp');
            $table->foreign('cod_features_nutricionales_erp', 'fk_erp_features')
                ->references('cod_features_nutricionales')
                ->on('features_nutricionales')
                ->onDelete('restrict');

            // Paciente evaluado
            $table->unsignedBigInteger('cod_pacientes_erp');
            $table->foreign('cod_pacientes_erp', 'fk_erp_paciente')
                ->references('cod_pacientes')
                ->on('pacientes')
                ->onDelete('restrict');

            // Nutricionista evaluador
            $table->unsignedBigInteger('cod_nutricionistas_erp');
            $table->foreign('cod_nutricionistas_erp', 'fk_erp_nutri')
                ->references('cod_nutricionistas')
                ->on('nutricionistas')
                ->onDelete('restrict');

            // Target supervisado: nivel de riesgo (bajo, moderado, alto) o NULL si la información es insuficiente
            $table->string('nivel_riesgo_erp', 30)->nullable()->comment('Target supervisado: bajo, moderado, alto');

            // Categoría/tipo de riesgo complementario (configurable)
            $table->string('tipo_riesgo_erp', 100)->nullable()->comment('Tipo de riesgo complementario');

            // Estado de la etiqueta: valida, insuficiente, anulada
            $table->string('estado_etiqueta_erp', 30)->default('valida')->comment('valida, insuficiente, anulada');

            // Justificación clínica del profesional
            $table->text('observacion_profesional_erp')->nullable()->comment('Observaciones clínicas del nutricionista');

            // Versión de criterio clínico/protocolo
            $table->string('version_criterio_erp', 30)->default('v1.0')->comment('Versión del criterio de evaluación');

            // Fecha y hora de la evaluación
            $table->dateTime('fec_evaluacion_erp');

            $table->timestamps();

            // Unicidad: un nutricionista solo emite 1 evaluación por snapshot y versión de criterio
            $table->unique(
                ['cod_features_nutricionales_erp', 'cod_nutricionistas_erp', 'version_criterio_erp'],
                'uniq_etiqueta_nutri_snap_crit'
            );

            $table->index(['nivel_riesgo_erp', 'estado_etiqueta_erp'], 'idx_etiquetas_nivel_estado');
            $table->index('fec_evaluacion_erp', 'idx_etiquetas_fec_evaluacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etiquetas_riesgo_profesional');
    }
};
