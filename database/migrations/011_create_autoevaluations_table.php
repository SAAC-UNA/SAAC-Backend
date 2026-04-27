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
        // Tabla de autoevaluaciones
        Schema::create('AUTOEVALUACION', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('autoevaluacion_id');
            // Relación con proceso
            $table->foreignId('proceso_id')->constrained('PROCESO', 'proceso_id')->onDelete('cascade');
            // Fecha de inicio de la autoevaluación (opcional)
            $table->date('fecha_inicio')->nullable();
            // Fecha de fin de la autoevaluación (opcional)
            $table->date('fecha_fin')->nullable();
            // Timestamps de creación y actualización
            $table->timestamps();

            // Índices de performance
            $table->index('proceso_id', 'idx_au_proceso_id');
            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_au_fechas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de autoevaluaciones
        Schema::dropIfExists('AUTOEVALUACION');
    }
};
