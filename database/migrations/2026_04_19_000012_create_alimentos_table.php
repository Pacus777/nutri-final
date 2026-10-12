<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alimentos', function (Blueprint $table) {
            // PK: Código Alimentos
            $table->bigIncrements('cod_alimentos');

            // CAMPO: Nombre local del alimento
            $table->string('nom_ali', 180)
                ->comment('Nombre principal del alimento dentro del sistema.');

            // CAMPO: Nombre externo del alimento
            $table->string('nom_ext_ali', 220)
                ->nullable()
                ->comment('Nombre original del alimento devuelto por la fuente externa.');

            // CAMPO: Tipo alimento
            $table->string('tip_ali', 30)
                ->comment('Tipo del alimento: ingrediente, producto, plato o bebida.');

            // CAMPO: Descripción alimento
            $table->text('des_ali')
                ->nullable()
                ->comment('Descripción resumida del alimento.');

            // CAMPO: Marca alimento
            $table->string('mar_ali', 120)
                ->nullable()
                ->comment('Marca comercial del alimento si aplica.');

            // CAMPO: Fuente alimento
            $table->string('src_ali', 30)
                ->default('manual')
                ->comment('Fuente del alimento: usda, fatsecret o manual.');

            // CAMPO: Referencia externa alimento
            $table->string('ref_ext_ali', 120)
                ->nullable()
                ->comment('Identificador externo de la API de origen.');

            // CAMPO: Idioma alimento
            $table->string('idi_ali', 20)
                ->nullable()
                ->comment('Idioma de la respuesta cacheada.');

            // CAMPO: País alimento
            $table->string('pai_ali', 20)
                ->nullable()
                ->comment('País o edición regional utilizada al importar el alimento.');

            // CAMPO: Imagen alimento
            $table->string('img_ali', 255)
                ->nullable()
                ->comment('URL o ruta de imagen del alimento.');

            // CAMPO: Fecha sincronización alimento
            $table->timestamp('fec_sync_ali')
                ->nullable()
                ->comment('Última fecha de sincronización con la fuente externa.');

            // CAMPO: Estado alimento
            $table->string('est_ali', 20)
                ->default('activo')
                ->comment('Estado lógico del alimento.');

            $table->timestamps();

            $table->unique(['src_ali', 'ref_ext_ali'], 'uq_alimentos_fuente_referencia');
        });

        DB::statement("ALTER TABLE alimentos COMMENT = 'Catálogo local de alimentos, alimentado desde fuentes externas como USDA/FatSecret o registros manuales, cacheado para reutilización interna.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentos');
    }
};