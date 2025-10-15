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
        Schema::create('EVIDENCIA_ASIGNACION', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('evidencia_asignacion_id');
            
            // Relación con proceso (cascade: si se borra el proceso, se borran sus asignaciones)
            $table->foreignId('proceso_id')->constrained('PROCESO', 'proceso_id')->onDelete('cascade');
            
            // Relación con evidencia (restrict: no borrar evidencia si tiene asignaciones)
            $table->foreignId('evidencia_id')->constrained('EVIDENCIA', 'evidencia_id')->onDelete('restrict');
            
            // Relación con usuario (restrict: no borrar usuario si tiene asignaciones)
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            
            // Estado de la asignación (varchar de 30)
            $table->string('estado', 30)->default('pendiente');
            
            // Fecha cuando se realizó la asignación
            $table->datetime('fecha_asignacion');
            
            // Fecha límite de entrega
            $table->datetime('fecha_limite')->nullable();
            
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índice único para evitar asignaciones duplicadas del mismo usuario a la misma evidencia en el mismo proceso
            $table->unique(['proceso_id', 'evidencia_id', 'usuario_id'], 'unique_proceso_evidencia_usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('EVIDENCIA_ASIGNACION');
    }
};
