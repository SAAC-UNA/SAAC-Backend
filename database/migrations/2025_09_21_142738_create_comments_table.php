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
            $table->id()->name('comentario_id');
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('cascade');
            $table->text('texto', 300);
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
