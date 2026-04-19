<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_proceso_nutricional', function (Blueprint $table) {
            // PK: Código Control Proceso Nutricional
            $table->bigIncrements('cod_control_proceso_nutricional');

            // FK: Código Pacientes Control Proceso Nutricional
            $table->unsignedBigInteger('cod_pacientes_cpn');

            // CAMPO: Fecha Control Proceso Nutricional
            $table->date('fec_cpn');

            // CAMPO: Estado Control Proceso Nutricional
            $table->string('est_cpn', 30);

            // CAMPO: Sostenibilidad Control Proceso Nutricional
            $table->string('sos_cpn', 30);

            // CAMPO: Observación Control Proceso Nutricional
            $table->text('obs_cpn')->nullable();

            $table->timestamps();

            $table->foreign('cod_pacientes_cpn', 'fk_control_proceso_nutricional_pacientes')
                ->references('cod_pacientes')
                ->on('pacientes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_proceso_nutricional');
    }
};
