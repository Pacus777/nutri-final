<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutricionistas', function (Blueprint $table) {
            // PK: Código Nutricionistas
            $table->bigIncrements('cod_nutricionistas');

            // FK: Código Usuarios Nutricionista
            $table->unsignedBigInteger('cod_usuarios_nut')->unique();

            // CAMPO: Especialidad Nutricionista
            $table->string('esp_nut', 100)->nullable();

            $table->timestamps();

            $table->foreign('cod_usuarios_nut', 'fk_nutricionistas_usuarios')
                ->references('cod_usuarios')
                ->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutricionistas');
    }
};
