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

            // CAMPO: Descripción Contexto Paciente
            $table->text('des_cxp');

            // CAMPO: Fecha Contexto Paciente
            $table->date('fec_cxp');

            $table->timestamps();

            $table->foreign('cod_pacientes_cxp', 'fk_contextos_paciente_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contextos_paciente');
    }
};
