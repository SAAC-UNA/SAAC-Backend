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
        Schema::create('COMENTARIO', function (Blueprint $table) {
            // clave primaria personalizada (en vez de id()->name(...))
            $table->id('comentario_id');

            // relación con USUARIO
            $table->foreignId('usuario_id')
                  ->constrained('USUARIO', 'usuario_id')
                  ->onDelete('cascade');

            $table->text('texto'); // text no acepta longitud, se quita (300)
            $table->date('fecha_creacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('COMENTARIO');
    }
};
