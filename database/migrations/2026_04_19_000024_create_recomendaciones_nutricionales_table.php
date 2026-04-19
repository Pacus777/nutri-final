<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recomendaciones_nutricionales', function (Blueprint $table) {
            // PK: Código Recomendaciones Nutricionales
            $table->bigIncrements('cod_recomendaciones_nutricionales');

            // FK: Código Pacientes Recomendación
            $table->unsignedBigInteger('cod_pacientes_rec');

            // FK: Código Nutricionistas Recomendación
            $table->unsignedBigInteger('cod_nutricionistas_rec');

            // CAMPO: Fecha Recomendación
            $table->date('fec_rec');

            // CAMPO: Descripción Recomendación
            $table->text('des_rec');

            // CAMPO: Tipo Recomendación
            $table->string('tip_rec', 80);

            $table->timestamps();

            $table->foreign('cod_pacientes_rec', 'fk_recomendaciones_nutricionales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->foreign('cod_nutricionistas_rec', 'fk_recomendaciones_nutricionales_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recomendaciones_nutricionales');
    }
};
