<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion_nutricionista_paciente', function (Blueprint $table) {
            // PK: Código Asignación Nutricionista Paciente
            $table->bigIncrements('cod_asignacion_nutricionista_paciente');

            // FK: Código Nutricionistas Asignación Nutricionista Paciente
            $table->unsignedBigInteger('cod_nutricionistas_anp');

            // FK: Código Pacientes Asignación Nutricionista Paciente
            $table->unsignedBigInteger('cod_pacientes_anp');

            // CAMPO: Fecha Asignación Nutricionista Paciente
            $table->date('fec_anp');

            // CAMPO: Estado Asignación Nutricionista Paciente
            $table->string('est_anp', 20);

            $table->timestamps();

            $table->foreign('cod_nutricionistas_anp', 'fk_asignacion_nutricionista_paciente_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');

            $table->foreign('cod_pacientes_anp', 'fk_asignacion_nutricionista_paciente_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_nutricionista_paciente');
    }
};
