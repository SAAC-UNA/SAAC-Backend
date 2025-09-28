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
        // Tabla de dimensiones
        Schema::create('DIMENSION', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('dimension_id');
            // Relación con comentario
            $table->foreignId('comentario_id')->constrained('COMENTARIO', 'comentario_id')->onDelete('cascade');
            // Nombre de la dimensión
            $table->string('nombre', 100);
            // Nomenclatura de la dimensión
            $table->string('nomenclatura', 20);
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de dimensiones
        Schema::dropIfExists('DIMENSION');
    }
};
