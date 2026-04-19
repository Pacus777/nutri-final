<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_nutricional', function (Blueprint $table) {
            // PK: Código Historial Nutricional
            $table->bigIncrements('cod_historial_nutricional');

            // FK: Código Pacientes Historial Nutricional
            $table->unsignedBigInteger('cod_pacientes_hin');

            // CAMPO: Fecha Historial Nutricional
            $table->date('fec_hin');

            // CAMPO: Descripción Historial Nutricional
            $table->text('des_hin');

            $table->timestamps();

            $table->foreign('cod_pacientes_hin', 'fk_historial_nutricional_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_nutricional');
    }
};
