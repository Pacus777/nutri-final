<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alimentos', function (Blueprint $table) {
            // PK: Código Alimentos
            $table->bigIncrements('cod_alimentos');

            // CAMPO: Nombre Alimento
            $table->string('nom_ali', 150)->unique();

            // CAMPO: Tipo Alimento
            $table->string('tip_ali', 80);

            // CAMPO: Estado Alimento
            $table->string('est_ali', 20);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentos');
    }
};
