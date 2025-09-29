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
        // Tabla de criterios
        Schema::create('CRITERIO', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('criterio_id');
            // Relación con componente
            $table->foreignId('componente_id')->constrained('COMPONENTE', 'componente_id');
            // Relación con comentario
            $table->foreignId('comentario_id')->constrained('COMENTARIO', 'comentario_id');
            // Descripción del criterio
            $table->string('descripcion', 300);
            // Nomenclatura del criterio
            $table->string('nomenclatura', 20);
            // Estado del criterio
            $table->boolean('activo')->default(true);
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de criterios
        Schema::dropIfExists('CRITERIO');
    }
};
