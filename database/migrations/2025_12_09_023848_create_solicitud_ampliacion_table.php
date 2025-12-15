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
        Schema::create('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('solicitud_ampliacion_id');
            
            // Relación con evidencia_asignacion (restrict: no borrar asignación si tiene solicitudes)
            $table->foreignId('evidencia_asignacion_id')
                ->constrained('EVIDENCIA_ASIGNACION', 'evidencia_asignacion_id')
                ->onDelete('restrict');
            
            // Relación con usuario solicitante (restrict: no borrar usuario si tiene solicitudes)
            $table->foreignId('usuario_id')
                ->constrained('USUARIO', 'usuario_id')
                ->onDelete('restrict');
            
            // Fecha y hora cuando se creó la solicitud
            $table->datetime('fecha_solicitud');
            
            // Motivo de la solicitud de ampliación (varchar 300)
            $table->string('motivo', 300);
            
            // Fecha sugerida para la nueva fecha límite
            $table->datetime('fecha_sugerida');
            
            // Estado de la solicitud (varchar 30)
            $table->string('estado', 30)->default('pendiente');
            
            // Fecha y hora cuando se resolvió la solicitud (nullable)
            $table->datetime('fecha_resolucion')->nullable();
            
            // Usuario que resolvió la solicitud - FK a USUARIO (nullable)
            $table->foreignId('usuario_resolutor_id')
                ->nullable()
                ->constrained('USUARIO', 'usuario_id')
                ->onDelete('restrict');
            
            // Justificación de la decisión del encargado (nullable)
            $table->string('justificacion', 500)->nullable();
            
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices para mejorar consultas
            $table->index('evidencia_asignacion_id');
            $table->index('usuario_id');
            $table->index('usuario_resolutor_id');
            $table->index('estado');
            $table->index('fecha_solicitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('SOLICITUD_AMPLIACION');
    }
};
