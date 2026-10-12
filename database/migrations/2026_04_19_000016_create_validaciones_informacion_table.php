<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validaciones_informacion', function (Blueprint $table) {
            // PK: Código Validaciones Información
            $table->bigIncrements('cod_validaciones_informacion');

            // FK: Código Registros Alimenticios Validación Información
            $table->unsignedBigInteger('cod_registros_alimenticios_vin');

            // FK: Código Nutricionistas Validación Información
            $table->unsignedBigInteger('cod_nutricionistas_vin');

            // CAMPO: Fecha Validación Información
            $table->date('fec_vin');

            // CAMPO: Estado Validación Información
            $table->string('est_vin', 20);

            // CAMPO: Observación Validación Información
            $table->text('obs_vin')->nullable();

            $table->timestamps();

            $table->foreign('cod_registros_alimenticios_vin', 'fk_validaciones_informacion_registros')
                ->references('cod_registros_alimenticios')
                ->on('registros_alimenticios');

            $table->foreign('cod_nutricionistas_vin', 'fk_validaciones_informacion_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');

            $table->unique(
                ['cod_registros_alimenticios_vin', 'cod_nutricionistas_vin'],
                'uq_validacion_informacion_registro_nutricionista'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validaciones_informacion');
    }
};
