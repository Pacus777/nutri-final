<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_rol', function (Blueprint $table) {
            // PK: Código Usuario Rol
            $table->bigIncrements('cod_usuario_rol');

            // FK: Código Usuarios Usuario Rol
            $table->unsignedBigInteger('cod_usuarios_url');

            // FK: Código Roles Usuario Rol
            $table->unsignedBigInteger('cod_roles_url');

            $table->timestamps();

            $table->foreign('cod_usuarios_url', 'fk_usuario_rol_usuarios')
                ->references('cod_usuarios')
                ->on('usuarios');

            $table->foreign('cod_roles_url', 'fk_usuario_rol_roles')
                ->references('cod_roles')
                ->on('roles');

            $table->unique(['cod_usuarios_url', 'cod_roles_url'], 'uq_usuario_rol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_rol');
    }
};
