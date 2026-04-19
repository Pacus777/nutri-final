<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riesgos_nutricionales', function (Blueprint $table) {
            // PK: Código Riesgos Nutricionales
            $table->bigIncrements('cod_riesgos_nutricionales');

            // FK: Código Pacientes Riesgo Nutricional
            $table->unsignedBigInteger('cod_pacientes_rnu');

            // CAMPO: Fecha Riesgo Nutricional
            $table->date('fec_rnu');

            // CAMPO: Tipo Riesgo Nutricional
            $table->string('tip_rnu', 80);

            // CAMPO: Nivel Riesgo Nutricional
            $table->string('niv_rnu', 30);

            // CAMPO: Descripción Riesgo Nutricional
            $table->text('des_rnu');

            $table->timestamps();

            $table->foreign('cod_pacientes_rnu', 'fk_riesgos_nutricionales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riesgos_nutricionales');
    }
};
