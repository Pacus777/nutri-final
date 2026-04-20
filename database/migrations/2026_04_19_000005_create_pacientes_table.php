<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            // PK: Código Pacientes
            $table->bigIncrements('cod_pacientes');

            // FK: Código Usuarios Paciente
            $table->unsignedBigInteger('cod_usuarios_pac')
                ->unique()
                ->comment('Código del usuario vinculado al perfil del paciente.');

            // CAMPO: Fecha Paciente
            $table->date('fec_pac')
                ->comment('Fecha de registro base del paciente en el sistema.');

            // CAMPO: Edad Paciente
            $table->integer('eda_pac')
                ->comment('Edad base del paciente al momento del registro.');

            // CAMPO: Sexo Paciente
            $table->string('sex_pac', 20)
                ->comment('Sexo del paciente.');

            // CAMPO: Peso Paciente
            $table->decimal('pes_pac', 8, 2)
                ->comment('Peso base del paciente.');

            // CAMPO: Talla Paciente
            $table->decimal('tal_pac', 8, 2)
                ->comment('Talla base del paciente.');

            // CAMPO: Estado Paciente
            $table->string('est_pac', 20)
                ->default('activo')
                ->comment('Estado lógico del paciente: activo o inactivo.');

            $table->timestamps();

            $table->foreign('cod_usuarios_pac', 'fk_pacientes_usuarios')
                ->references('cod_usuarios')
                ->on('usuarios');
        });

        DB::statement("ALTER TABLE pacientes COMMENT = 'Tabla de pacientes del sistema vinculados a usuarios existentes o creados desde el módulo de pacientes.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};