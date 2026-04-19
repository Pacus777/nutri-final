<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervenciones_nutricionales', function (Blueprint $table) {
            // PK: Código Intervenciones Nutricionales
            $table->bigIncrements('cod_intervenciones_nutricionales');

            // FK: Código Pacientes Intervención
            $table->unsignedBigInteger('cod_pacientes_int');

            // FK: Código Nutricionistas Intervención
            $table->unsignedBigInteger('cod_nutricionistas_int');

            // CAMPO: Fecha Intervención
            $table->date('fec_int');

            // CAMPO: Tipo Intervención
            $table->string('tip_int', 80);

            // CAMPO: Descripción Intervención
            $table->text('des_int');

            $table->timestamps();

            $table->foreign('cod_pacientes_int', 'fk_intervenciones_nutricionales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->foreign('cod_nutricionistas_int', 'fk_intervenciones_nutricionales_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervenciones_nutricionales');
    }
};
