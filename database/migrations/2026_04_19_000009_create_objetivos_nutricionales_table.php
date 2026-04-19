<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objetivos_nutricionales', function (Blueprint $table) {
            // PK: Código Objetivos Nutricionales
            $table->bigIncrements('cod_objetivos_nutricionales');

            // FK: Código Pacientes Objetivo
            $table->unsignedBigInteger('cod_pacientes_obe');

            // FK: Código Nutricionistas Objetivo
            $table->unsignedBigInteger('cod_nutricionistas_obe');

            // CAMPO: Descripción Objetivo
            $table->text('des_obe');

            // CAMPO: Fecha Objetivo
            $table->date('fec_obe');

            // CAMPO: Estado Objetivo
            $table->string('est_obe', 20);

            $table->timestamps();

            $table->foreign('cod_pacientes_obe', 'fk_objetivos_nutricionales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->foreign('cod_nutricionistas_obe', 'fk_objetivos_nutricionales_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objetivos_nutricionales');
    }
};
