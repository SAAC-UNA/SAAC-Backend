<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para gestionar solicitudes de ampliación de plazo en asignaciones
        Schema::create('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            // Clave primaria
            $table->id()->name('solicitud_ampliacion_id');
            // Llave foránea hacia la evidencia de asignación relacionada
            $table->foreignId('evidencia_asignacion_id')->constrained('EVIDENCIA_ASIGNACION', 'evidencia_asignacion_id')->onDelete('restrict');
            // Llave foránea hacia el elemento de asignación relacionado (opcional, ya que la solicitud puede ser general para la asignación)
            $table->foreignId('elemento_asignacion_id')->nullable()->constrained('ELEMENTO_ASIGNACION', 'elemento_asignacion_id')->onDelete('restrict');
            // Llave foránea hacia el usuario que realiza la solicitud
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            // Motivo de la solicitud, con longitud suficiente para detalles
            $table->string('motivo', 1000);
            // Fecha sugerida para la nueva fecha límite
            $table->dateTime('fecha_sugerida');
            // Estado de la solicitud: pendiente, aprobada, rechazada o cancelada
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'cancelada'])->default('pendiente');
            // Fecha de resolución de la solicitud
            $table->dateTime('fecha_resolucion')->nullable();
            // Llave foránea hacia el usuario que resolvió la solicitud
            $table->foreignId('usuario_resolutor_id')->nullable()->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            // Justificación de la resolución
            $table->string('justificacion', 500)->nullable();
            // Timestamps de creación y actualización
            $table->timestamps();

            // Índices
            $table->index('evidencia_asignacion_id', 'idx_sa_evidencia_asignacion_id');
            $table->index('elemento_asignacion_id', 'idx_sa_elemento_asignacion_id');
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
