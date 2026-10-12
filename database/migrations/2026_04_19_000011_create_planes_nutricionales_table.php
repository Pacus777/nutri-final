<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_nutricionales', function (Blueprint $table) {
            // PK: Código Planes Nutricionales
            $table->bigIncrements('cod_planes_nutricionales');

            // FK: Código Pacientes Plan
            $table->unsignedBigInteger('cod_pacientes_pan');

            // FK: Código Nutricionistas Plan
            $table->unsignedBigInteger('cod_nutricionistas_pan');

            // CAMPO: Fecha Plan
            $table->date('fec_pan');

            // CAMPO: Descripción Plan
            $table->text('des_pan');

            // CAMPO: Estado Plan
            $table->string('est_pan', 20);

            $table->timestamps();

            $table->foreign('cod_pacientes_pan', 'fk_planes_nutricionales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->foreign('cod_nutricionistas_pan', 'fk_planes_nutricionales_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_nutricionales');
    }
};