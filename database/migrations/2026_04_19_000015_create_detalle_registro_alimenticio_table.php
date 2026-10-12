<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_registro_alimenticio', function (Blueprint $table) {
            // PK: Código Detalle Registro Alimenticio
            $table->bigIncrements('cod_detalle_registro_alimenticio');

            // FK: Código Registros Alimenticios Detalle Registro Alimenticio
            $table->unsignedBigInteger('cod_registros_alimenticios_dra');

            // FK: Código Alimentos Detalle Registro Alimenticio
            $table->unsignedBigInteger('cod_alimentos_dra');

            // CAMPO: Tipo Detalle Registro Alimenticio
            $table->string('tip_dra', 80);

            // CAMPO: Cantidad Detalle Registro Alimenticio
            $table->decimal('can_dra', 10, 2);

            // CAMPO: Unidad Detalle Registro Alimenticio
            $table->string('uni_dra', 30)->nullable();

            // CAMPO: Hora Detalle Registro Alimenticio
            $table->time('hor_dra');

            $table->timestamps();

            $table->foreign('cod_registros_alimenticios_dra', 'fk_detalle_registro_alimenticio_registros')
                ->references('cod_registros_alimenticios')
                ->on('registros_alimenticios');

            $table->foreign('cod_alimentos_dra', 'fk_detalle_registro_alimenticio_alimentos')
                ->references('cod_alimentos')
                ->on('alimentos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_registro_alimenticio');
    }
};
