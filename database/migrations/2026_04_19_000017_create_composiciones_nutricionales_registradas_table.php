<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('composiciones_nutricionales_registradas', function (Blueprint $table) {
            // PK: Código Composiciones Nutricionales Registradas
            $table->bigIncrements('cod_composiciones_nutricionales_registradas');

            // FK: Código Registros Alimenticios Composición Nutricional Registrada
            $table->unsignedBigInteger('cod_registros_alimenticios_cnr');

            // CAMPO: Calorías Composición Nutricional Registrada
            $table->decimal('cal_cnr', 10, 2);

            // CAMPO: Proteínas Composición Nutricional Registrada
            $table->decimal('pro_cnr', 10, 2);

            // CAMPO: Grasas Composición Nutricional Registrada
            $table->decimal('gra_cnr', 10, 2);

            // CAMPO: Carbohidratos Composición Nutricional Registrada
            $table->decimal('car_cnr', 10, 2);

            // CAMPO: Vitaminas Composición Nutricional Registrada
            $table->decimal('vit_cnr', 10, 2);

            // CAMPO: Minerales Composición Nutricional Registrada
            $table->decimal('min_cnr', 10, 2);

            $table->timestamps();

            $table->foreign('cod_registros_alimenticios_cnr', 'fk_cnr_registros')
                ->references('cod_registros_alimenticios')
                ->on('registros_alimenticios');

            $table->unique(
                ['cod_registros_alimenticios_cnr'],
                'uq_cnr_registro'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composiciones_nutricionales_registradas');
    }
};