<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // PK: Código Roles
            $table->bigIncrements('cod_roles');

            // CAMPO: Nombre Rol
            $table->string('nom_rol', 50)->unique();

            // CAMPO: Descripción Rol
            $table->text('des_rol')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
