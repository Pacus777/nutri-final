<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion_patron_paciente', function (Blueprint $table) {
            // PK: Código Asignación Patrón Paciente
            $table->bigIncrements('cod_asignacion_patron_paciente');

            // FK: Código Pacientes Asignación Patrón Paciente
            $table->unsignedBigInteger('cod_pacientes_app');

            // FK: Código Patrones Alimenticios Asignación Patrón Paciente
            $table->unsignedBigInteger('cod_patrones_alimenticios_app');

            // CAMPO: Fecha Asignación Patrón Paciente
            $table->date('fec_app');

            $table->timestamps();

            $table->foreign('cod_pacientes_app', 'fk_asignacion_patron_paciente_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->foreign('cod_patrones_alimenticios_app', 'fk_asignacion_patron_paciente_patrones')
                ->references('cod_patrones_alimenticios')
                ->on('patrones_alimenticios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_patron_paciente');
    }
};
