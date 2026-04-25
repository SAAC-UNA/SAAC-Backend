<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para registrar las aprobaciones o rechazos de elementos por parte de los evaluadores
        Schema::create('APROBACION_ELEMENTO', function (Blueprint $table) {
            // Clave primaria
            $table->id('aprobacion_elemento_id');
            // Claves foráneas
            $table->foreignId('elemento_id')->constrained('ELEMENTO', 'elemento_id')->onDelete('restrict');
            $table->foreignId('proceso_id')->constrained('PROCESO', 'proceso_id')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            // Estado de la aprobación
            $table->enum('estado', ['aprobado', 'rechazado', 'pendiente', 'incompleto'])->default('pendiente');
            // Comentario opcional
            $table->string('comentario', 100)->nullable();
            // Nueva fecha límite al aprobar una solicitud de ampliación
            $table->date('nueva_fecha_limite')->nullable();
            // Timestamps
            $table->timestamps();

            // Índices
            $table->index('elemento_id', 'idx_ae_elemento_id');
            $table->index('proceso_id', 'idx_ae_proceso_id');
            $table->index('usuario_id', 'idx_ae_usuario_id');
            $table->index('estado', 'idx_ae_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('APROBACION_ELEMENTO');
    }
};
