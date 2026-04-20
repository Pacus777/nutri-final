<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutricionistas', function (Blueprint $table) {
            // PK: Código Nutricionistas
            $table->bigIncrements('cod_nutricionistas');

            // FK: Código Usuarios Nutricionista
            $table->unsignedBigInteger('cod_usuarios_nut')
                ->unique()
                ->comment('Código del usuario vinculado al perfil profesional del nutricionista.');

            // CAMPO: Especialidad Nutricionista
            $table->string('esp_nut', 100)
                ->nullable()
                ->comment('Especialidad o enfoque profesional del nutricionista.');

            // CAMPO: Estado Nutricionista
            $table->string('est_nut', 20)
                ->default('activo')
                ->comment('Estado lógico del nutricionista: activo o inactivo.');

            $table->timestamps();

            $table->foreign('cod_usuarios_nut', 'fk_nutricionistas_usuarios')
                ->references('cod_usuarios')
                ->on('usuarios');
        });

        DB::statement("ALTER TABLE nutricionistas COMMENT = 'Tabla de nutricionistas del sistema vinculados a usuarios existentes.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('nutricionistas');
    }
};