<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabla_composicion_nutricional', function (Blueprint $table) {
            // PK: Código Tabla Composición Nutricional
            $table->bigIncrements('cod_tabla_composicion_nutricional');

            // FK: Código Alimentos Tabla Composición Nutricional
            $table->unsignedBigInteger('cod_alimentos_tcn');

            // CAMPO: Calorías Tabla Composición Nutricional
            $table->decimal('cal_tcn', 10, 2);

            // CAMPO: Proteínas Tabla Composición Nutricional
            $table->decimal('pro_tcn', 10, 2);

            // CAMPO: Grasas Tabla Composición Nutricional
            $table->decimal('gra_tcn', 10, 2);

            // CAMPO: Carbohidratos Tabla Composición Nutricional
            $table->decimal('car_tcn', 10, 2);

            // CAMPO: Vitaminas Tabla Composición Nutricional
            $table->decimal('vit_tcn', 10, 2);

            // CAMPO: Minerales Tabla Composición Nutricional
            $table->decimal('min_tcn', 10, 2);

            $table->timestamps();

            $table->foreign('cod_alimentos_tcn', 'fk_tabla_composicion_nutricional_alimentos')
                ->references('cod_alimentos')
                ->on('alimentos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabla_composicion_nutricional');
    }
};
