<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimientos_nutricionales', function (Blueprint $table) {
            // PK: Código Seguimientos Nutricionales
            $table->bigIncrements('cod_seguimientos_nutricionales');

            // FK: Código Pacientes Seguimiento
            $table->unsignedBigInteger('cod_pacientes_seg');

            // FK: Código Nutricionistas Seguimiento
            $table->unsignedBigInteger('cod_nutricionistas_seg');

            // CAMPO: Fecha Seguimiento
            $table->date('fec_seg');

            // CAMPO: Resultado Seguimiento
            $table->text('res_seg');

            // CAMPO: Cambio Seguimiento
            $table->text('cam_seg')->nullable();

            $table->timestamps();

            $table->foreign('cod_pacientes_seg', 'fk_seguimientos_nutricionales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->foreign('cod_nutricionistas_seg', 'fk_seguimientos_nutricionales_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimientos_nutricionales');
    }
};
