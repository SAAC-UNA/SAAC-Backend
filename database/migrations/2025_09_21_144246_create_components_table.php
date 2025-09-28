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
        // Tabla de componentes
        Schema::create('COMPONENTE', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('componente_id');
            // Relación con dimensión
            $table->foreignId('dimension_id')->constrained('DIMENSION', 'dimension_id')->onDelete('cascade');
            // Relación con comentario
            $table->foreignId('comentario_id')->constrained('COMENTARIO', 'comentario_id')->onDelete('cascade');
            // Nombre del componente
            $table->string('nombre', 80);
            // Nomenclatura del componente
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
        // Elimina la tabla de componentes
        Schema::dropIfExists('COMPONENTE');
    }
};
