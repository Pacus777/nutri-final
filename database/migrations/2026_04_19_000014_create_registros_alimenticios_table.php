<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_alimenticios', function (Blueprint $table) {
            // PK: Código Registros Alimenticios
            $table->bigIncrements('cod_registros_alimenticios');

            // FK: Código Pacientes Registro Alimenticio
            $table->unsignedBigInteger('cod_pacientes_ral');

            // CAMPO: Fecha Registro Alimenticio
            $table->date('fec_ral');

            // CAMPO: Estado Registro Alimenticio
            $table->string('est_ral', 20);

            $table->timestamps();

            $table->foreign('cod_pacientes_ral', 'fk_registros_alimenticios_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->unique(['cod_pacientes_ral', 'fec_ral'], 'uq_registros_alimenticios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_alimenticios');
    }
};
