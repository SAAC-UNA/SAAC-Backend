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
        // Tabla de comentarios
        Schema::create('COMENTARIO', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('comentario_id');
            // Relación con usuario (restrict: no borrar usuario si tiene comentarios)
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            // Texto del comentario
            $table->text('texto', 300);
            // Fecha de creación del comentario (opcional)
            $table->date('fecha_creacion')->nullable();
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
