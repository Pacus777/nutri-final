<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_iniciales', function (Blueprint $table) {
            // PK: Código Evaluaciones Iniciales
            $table->bigIncrements('cod_evaluaciones_iniciales');

            // FK: Paciente evaluado
            $table->unsignedBigInteger('cod_pacientes_evi');

            // FK: Nutricionista responsable
            // Nullable porque el paciente puede completar su onboarding inicial
            // antes de que un nutricionista revise o confirme la evaluación.
            $table->unsignedBigInteger('cod_nutricionistas_evi')->nullable();

            // Fecha de la evaluación inicial
            $table->date('fec_evi');

            // Estado del checkpoint inicial
            // Estados sugeridos:
            // borrador, pendiente_revision, activa, cerrada
            $table->string('est_evi', 50)->default('borrador');

            // Datos antropométricos iniciales
            $table->decimal('peso_evi', 6, 2)->nullable()
                ->comment('Peso inicial en kilogramos');

            $table->decimal('estatura_evi', 5, 2)->nullable()
                ->comment('Estatura inicial en metros');

            $table->decimal('imc_evi', 5, 2)->nullable()
                ->comment('Índice de Masa Corporal calculado');

            // Información clínica inicial
            $table->text('objetivo_inicial_evi')->nullable()
                ->comment('Objetivo inicial del paciente al iniciar el proceso');

            $table->text('restricciones_evi')->nullable()
                ->comment('Restricciones alimentarias, alergias, intolerancias o limitaciones');

            $table->text('contexto_general_evi')->nullable()
                ->comment('Contexto general del paciente al inicio');

            // Observación profesional o general
            $table->text('obs_evi')->nullable();

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['cod_pacientes_evi', 'est_evi'], 'idx_evi_paciente_estado');
            $table->index(['cod_nutricionistas_evi', 'est_evi'], 'idx_evi_nutricionista_estado');
            $table->index('fec_evi', 'idx_evi_fecha');

            // Relaciones
            $table->foreign('cod_pacientes_evi', 'fk_evaluaciones_iniciales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->foreign('cod_nutricionistas_evi', 'fk_evaluaciones_iniciales_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_iniciales');
    }
};