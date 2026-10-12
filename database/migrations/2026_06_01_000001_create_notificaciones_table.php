<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->bigIncrements('cod_notificaciones');
            $table->unsignedBigInteger('cod_usuarios_not');
            $table->string('tit_not', 150);
            $table->text('des_not');
            $table->string('tip_not', 50)->default('info');
            $table->string('url_not', 255)->nullable();
            $table->string('est_not', 20)->default('no_leido'); // 'no_leido', 'leido'
            $table->timestamps();

            $table->foreign('cod_usuarios_not', 'fk_notificaciones_usuarios')
                ->references('cod_usuarios')
                ->on('usuarios')
                ->onDelete('cascade');
                
            $table->index(['cod_usuarios_not', 'est_not'], 'idx_notif_usuario_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
