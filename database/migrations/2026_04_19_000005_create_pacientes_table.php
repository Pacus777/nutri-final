<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            // PK: Código Pacientes
            $table->bigIncrements('cod_pacientes');

            // FK: Código Usuarios Paciente
            $table->unsignedBigInteger('cod_usuarios_pac')->unique();

            // CAMPO: Fecha Paciente
            $table->date('fec_pac');

            // CAMPO: Edad Paciente
            $table->integer('eda_pac');

            // CAMPO: Sexo Paciente
            $table->string('sex_pac', 20);

            // CAMPO: Peso Paciente
            $table->decimal('pes_pac', 8, 2);

            // CAMPO: Talla Paciente
            $table->decimal('tal_pac', 8, 2);

            $table->timestamps();

            $table->foreign('cod_usuarios_pac', 'fk_pacientes_usuarios')
                ->references('cod_usuarios')
                ->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
