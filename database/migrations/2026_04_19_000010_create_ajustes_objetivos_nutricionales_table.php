<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes_objetivos_nutricionales', function (Blueprint $table) {
            // PK: Código Ajustes Objetivos Nutricionales
            $table->bigIncrements('cod_ajustes_objetivos_nutricionales');

            // FK: Código Objetivos Nutricionales Ajuste
            $table->unsignedBigInteger('cod_objetivos_nutricionales_ajn');

            // FK: Código Nutricionistas Ajuste
            $table->unsignedBigInteger('cod_nutricionistas_ajn');

            // CAMPO: Fecha Ajuste
            $table->date('fec_ajn');

            // CAMPO: Descripción Ajuste
            $table->text('des_ajn');

            $table->timestamps();

            $table->foreign('cod_objetivos_nutricionales_ajn', 'fk_ajustes_objetivos_nutricionales_objetivos')
                ->references('cod_objetivos_nutricionales')
                ->on('objetivos_nutricionales');

            $table->foreign('cod_nutricionistas_ajn', 'fk_ajustes_objetivos_nutricionales_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_objetivos_nutricionales');
    }
};
