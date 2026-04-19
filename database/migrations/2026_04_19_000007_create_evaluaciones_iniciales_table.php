<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_iniciales', function (Blueprint $table) {
            // PK: Código Evaluaciones Iniciales
            $table->bigIncrements('cod_evaluaciones_iniciales');

            // FK: Código Pacientes Evaluación Inicial
            $table->unsignedBigInteger('cod_pacientes_evi');

            // FK: Código Nutricionistas Evaluación Inicial
            $table->unsignedBigInteger('cod_nutricionistas_evi');

            // CAMPO: Fecha Evaluación Inicial
            $table->date('fec_evi');

            // CAMPO: Estado Evaluación Inicial
            $table->string('est_evi', 50);

            // CAMPO: Observación Evaluación Inicial
            $table->text('obs_evi')->nullable();

            $table->timestamps();

            $table->foreign('cod_pacientes_evi', 'fk_evaluaciones_iniciales_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');

            $table->foreign('cod_nutricionistas_evi', 'fk_evaluaciones_iniciales_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_iniciales');
    }
};
