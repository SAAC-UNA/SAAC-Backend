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
        // Tabla de compromisos de mejora
        Schema::create('COMPROMISO_MEJORA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('compromiso_mejora_id');
            
            // Relación con proceso (proceso nuevo tipo "CompromisoMejora")
            $table->foreignId('proceso_id')->constrained('PROCESO', 'proceso_id')->onDelete('cascade');
            
            // Descripción del compromiso de mejora 
            $table->text('descripcion');
            
            // Fechas de planificación 
            $table->date('fecha_inicio');    // Cuándo se inicia el trabajo
            $table->date('fecha_fin');       // Fecha objetivo de culminación
            
            // Estado del compromiso (inicial: Pendiente)
            $table->enum('estado', ['Pendiente', 'En Progreso', 'Completado', 'Vencido'])->default('Pendiente');
            
            // Campo para activar/inactivar (mostrar/ocultar)
            $table->boolean('activo')->default(true)->comment('true=activo, false=inactivo');
            
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index('estado', 'idx_estado');
            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_fechas');
            $table->index('activo', 'idx_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de compromisos de mejora
        Schema::dropIfExists('COMPROMISO_MEJORA');
    }
};
