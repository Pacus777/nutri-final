<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_nutricionales', function (Blueprint $table) {
            // PK: Código Reportes Nutricionales
            $table->bigIncrements('cod_reportes_nutricionales');

            // FK: Código Pacientes Reporte Nutricional
            $table->unsignedBigInteger('cod_pacientes_rpn');

            // CAMPO: Tipo Reporte Nutricional
            $table->string('tip_rpn', 80);

            // CAMPO: Fecha Reporte Nutricional
            $table->date('fec_rpn');

            // CAMPO: Contenido Reporte Nutricional
            $table->text('con_rpn');

            $table->timestamps();

            $table->foreign('cod_pacientes_rpn', 'fk_reportes_nutricionales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_nutricionales');
    }
};
