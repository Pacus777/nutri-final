<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion_nutricionista_paciente', function (Blueprint $table) {
            // PK: Código Asignación Nutricionista Paciente
            $table->bigIncrements('cod_asignacion_nutricionista_paciente');

            // FK: Código Nutricionistas Asignación Nutricionista Paciente
            $table->unsignedBigInteger('cod_nutricionistas_anp')
                ->comment('Código del nutricionista responsable asignado al paciente.');

            // FK: Código Pacientes Asignación Nutricionista Paciente
            $table->unsignedBigInteger('cod_pacientes_anp')
                ->comment('Código del paciente asignado al nutricionista.');

            // CAMPO: Fecha Asignación Nutricionista Paciente
            $table->date('fec_anp')
                ->comment('Fecha en la que se registra la asignación del nutricionista al paciente.');

            // CAMPO: Estado Asignación Nutricionista Paciente
            $table->string('est_anp', 20)
                ->comment('Estado de la asignación: activa, cerrada o reasignada.');

            $table->timestamps();

            $table->foreign('cod_nutricionistas_anp', 'fk_asignacion_nutricionista_paciente_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');

            $table->foreign('cod_pacientes_anp', 'fk_asignacion_nutricionista_paciente_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');
        });

        DB::statement("ALTER TABLE asignacion_nutricionista_paciente COMMENT = 'Tabla de asignación formal entre nutricionistas y pacientes, con historial de cambios de responsable.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_nutricionista_paciente');
    }
};