<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('solicitud_ampliacion_id');
            // Relación con evidencia_asignacion (restrict: no borrar asignación si tiene solicitudes)
            $table->foreignId('evidencia_asignacion_id')
                ->constrained('EVIDENCIA_ASIGNACION', 'evidencia_asignacion_id')
                ->onDelete('restrict');
            // Nueva relación: elemento_asignacion (asociación a ELEMENTO_ASIGNACION)
            $table->unsignedBigInteger('elemento_asignacion_id')->nullable()
                ->after('evidencia_asignacion_id');
            $table->foreign('elemento_asignacion_id', 'sa_elemento_asignacion_id_foreign')
                ->references('elemento_asignacion_id')->on('ELEMENTO_ASIGNACION')
                ->onDelete('restrict');
            $table->index('elemento_asignacion_id', 'idx_sa_elemento_asignacion_id');
            // Relación con usuario solicitante (restrict: no borrar usuario si tiene solicitudes)
            $table->foreignId('usuario_id')
                ->constrained('USUARIO', 'usuario_id')
                ->onDelete('restrict');
            // Motivo de la solicitud de ampliación (varchar 1000)
            $table->string('motivo', 1000);
            // Fecha sugerida para la nueva fecha límite
            $table->datetime('fecha_sugerida');
            // Estado de la solicitud: pendiente, aprobada, rechazada
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
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
            $table->index('evidencia_asignacion_id', 'idx_sa_evidencia_asignacion_id');
            $table->index('usuario_id', 'idx_sa_usuario_id');
            $table->index('usuario_resolutor_id', 'idx_sa_resolutor_id');
            $table->index('estado', 'idx_sa_estado');
            $table->index('created_at', 'idx_sa_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SOLICITUD_AMPLIACION');
    }
};
