<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para registrar las solicitudes de ampliación de fecha límite para elementos asignados
        Schema::create('SOLICITUD_AMPLIACION_ELEMENTO', function (Blueprint $table) {
            // Clave primaria
            $table->id('solicitud_ampliacion_elemento_id');
            // Clave foránea hacia ELEMENTO_ASIGNACION
            $table->unsignedBigInteger('elemento_asignacion_id');
            $table->foreign('elemento_asignacion_id', 'sae_elemento_asignacion_id_foreign')->references('elemento_asignacion_id')->on('ELEMENTO_ASIGNACION')->onDelete('restrict');
            // Clave foránea hacia USUARIO
            $table->unsignedBigInteger('usuario_id');
            $table->foreign('usuario_id', 'sae_usuario_id_foreign')->references('usuario_id')->on('USUARIO')->onDelete('restrict');
            // Motivo de la solicitud
            $table->string('motivo', 1000);
            // Fecha sugerida para la ampliación
            $table->datetime('fecha_sugerida');
            // Estado de la solicitud
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'cancelada'])->default('pendiente');
            // Fecha de resolución de la solicitud
            $table->datetime('fecha_resolucion')->nullable();
            // Clave foránea hacia USUARIO que resuelve la solicitud
            $table->unsignedBigInteger('usuario_resolutor_id')->nullable();
            $table->foreign('usuario_resolutor_id', 'sae_resolutor_id_foreign')->references('usuario_id')->on('USUARIO')->onDelete('restrict');
            // Justificación de la resolución
            $table->string('justificacion', 500)->nullable();

            // Índices
            $table->index('elemento_asignacion_id', 'idx_sae_elemento_asignacion_id');
            $table->index('usuario_id', 'idx_sae_usuario_id');
            $table->index('estado', 'idx_sae_estado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SOLICITUD_AMPLIACION_ELEMENTO');
    }
};
