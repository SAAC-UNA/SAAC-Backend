<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla de comentarios (relación polimórfica)
        Schema::create('COMENTARIO', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('comentario_id');
            // Relación con usuario (restrict: no borrar usuario si tiene comentarios)
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            // Relación polimórfica: puede comentar en cualquier entidad
            $table->morphs('commentable'); // Crea commentable_type y commentable_id
            // Texto del comentario
            $table->text('texto');
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de comentarios
        Schema::dropIfExists('COMENTARIO');
    }
};
