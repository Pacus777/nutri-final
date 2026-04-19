<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrones_alimenticios', function (Blueprint $table) {
            // PK: Código Patrones Alimenticios
            $table->bigIncrements('cod_patrones_alimenticios');

            // CAMPO: Nombre Patrón
            $table->string('nom_pat', 100)->unique();

            // CAMPO: Descripción Patrón
            $table->text('des_pat');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrones_alimenticios');
    }
};
