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
        // Tabla de evidencias
        Schema::create('EVIDENCIA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('evidencia_id');
            // Relación con criterio
            $table->foreignId('criterio_id')->constrained('CRITERIO', 'criterio_id');
            // Relación con estado de evidencia
                // 🔹 Relación con proceso (muy importante para el filtro por carrera)
           // $table->foreignId('proceso_id') ->constrained('PROCESO', 'proceso_id') ->onDelete('cascade');
            $table->foreignId('estado_evidencia_id')->constrained('ESTADO_EVIDENCIA', 'estado_evidencia_id');
            // Descripción de la evidencia
            $table->string('descripcion', 80);
            // Nomenclatura de la evidencia
            $table->string('nomenclatura', 20);
            // Estado de la evidencia
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
        // Elimina la tabla de evidencias
        Schema::dropIfExists('EVIDENCIA');
    }
};
