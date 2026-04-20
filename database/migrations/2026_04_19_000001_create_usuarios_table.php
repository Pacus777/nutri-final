<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            // PK: Código Usuarios
            $table->bigIncrements('cod_usuarios');

            // CAMPO: Nombres Usuario
            $table->string('nom_usu', 120)
                ->comment('Nombres del usuario.');

            // CAMPO: Apellidos Usuario
            $table->string('ape_usu', 120)
                ->comment('Apellidos del usuario.');

            // CAMPO: CI Usuario
            $table->string('ci_usu', 30)
                ->comment('Número de cédula de identidad del usuario.');

            // CAMPO: Extensión CI Usuario
            $table->string('ext_ci_usu', 20)
                ->comment('Extensión o complemento del CI del usuario.');

            // CAMPO: Fecha Nacimiento Usuario
            $table->date('fec_nac_usu')
                ->comment('Fecha de nacimiento del usuario.');

            // CAMPO: Foto Usuario
            $table->string('fot_usu', 255)
                ->nullable()
                ->comment('Ruta o nombre del archivo de la foto del usuario.');

            // CAMPO: Correo Usuario
            $table->string('cor_usu', 120)
                ->unique()
                ->comment('Correo electrónico único del usuario.');

            // CAMPO: Clave Usuario
            $table->string('cla_usu', 255)
                ->comment('Contraseña cifrada del usuario.');

            // CAMPO: Estado Usuario
            $table->string('est_usu', 20)
                ->comment('Estado del usuario: activo, inactivo o bloqueado.');

            $table->rememberToken();

            $table->timestamps();

            $table->unique(['ci_usu', 'ext_ci_usu'], 'uq_usuarios_ci_ext');
        });

        DB::statement("ALTER TABLE usuarios COMMENT = 'Tabla de usuarios del sistema con datos base comunes para administradores, nutricionistas y pacientes.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};