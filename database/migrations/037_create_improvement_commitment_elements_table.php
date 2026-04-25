<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para registrar los compromisos de mejora asociados a elementos específicos dentro de un proceso
        Schema::create('COMPROMISO_MEJORA_ELEMENTO', function (Blueprint $table) {
            // Clave primaria
            $table->id('compromiso_elemento_id');
            // Clave foránea hacia el proceso
            $table->foreignId('proceso_id')->constrained('PROCESO', 'proceso_id')->onDelete('cascade');
            // Descripción detallada del compromiso de mejora
            $table->text('descripcion');
            // Fecha de inicio y fecha de fin para el compromiso de mejora
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            // Estado del compromiso
            $table->enum('estado', ['Pendiente', 'En Progreso', 'Completado', 'Vencido'])->default('Pendiente');
            // Indicador de actividad
            $table->boolean('activo')->default(true);
            // Timestamps
            $table->timestamps();

            // Índices
            $table->index('estado', 'idx_cme_estado');
            $table->index('fecha_fin', 'idx_cme_fecha_fin');
            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_cme_fechas');
            $table->index('activo', 'idx_cme_activo');
            $table->index('proceso_id', 'idx_cme_proceso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('COMPROMISO_MEJORA_ELEMENTO');
    }
};
