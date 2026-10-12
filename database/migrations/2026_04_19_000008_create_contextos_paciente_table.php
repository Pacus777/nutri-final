<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contextos_paciente', function (Blueprint $table) {
            // PK: Código Contextos Paciente
            $table->bigIncrements('cod_contextos_paciente');

            // FK: Código Pacientes Contexto Paciente
            $table->unsignedBigInteger('cod_pacientes_cxp');

            // CAMPO: Tipo Contexto Paciente
            $table->string('tip_cxp', 80)
                ->comment('Tipo de contexto clínico: alergia, patología, medicamento, hábitos, actividad física, observación clínica, etc.');

            // CAMPO: Prioridad Contexto Paciente
            $table->string('pri_cxp', 20)
                ->default('media')
                ->comment('Prioridad clínica del contexto: baja, media o alta.');

            // CAMPO: Descripción Contexto Paciente
            $table->text('des_cxp');

            // CAMPO: Fecha Contexto Paciente
            $table->date('fec_cxp');

            // CAMPO: Estado Contexto Paciente
            $table->string('est_cxp', 20)
                ->default('activo')
                ->comment('Estado lógico del contexto: activo o inactivo.');

            $table->timestamps();

            $table->foreign('cod_pacientes_cxp', 'fk_contextos_paciente_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->index(['cod_pacientes_cxp', 'est_cxp'], 'idx_contextos_paciente_estado');
            $table->index(['cod_pacientes_cxp', 'pri_cxp'], 'idx_contextos_paciente_prioridad');
            $table->index(['cod_pacientes_cxp', 'tip_cxp'], 'idx_contextos_paciente_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contextos_paciente');
    }
};
