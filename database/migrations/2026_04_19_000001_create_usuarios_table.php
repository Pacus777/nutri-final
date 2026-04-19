<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            // PK: Código Usuarios
            $table->bigIncrements('cod_usuarios');

            // CAMPO: Nombre Usuario
            $table->string('nom_usu', 120);

            // CAMPO: Correo Usuario
            $table->string('cor_usu', 120)->unique();

            // CAMPO: Clave Usuario
            $table->string('cla_usu', 255);

            // CAMPO: Estado Usuario
            $table->string('est_usu', 20);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
