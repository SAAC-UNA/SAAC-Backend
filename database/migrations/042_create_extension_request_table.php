<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            $table->id()->name('solicitud_ampliacion_id');
            $table->foreignId('evidencia_asignacion_id')
                ->constrained('EVIDENCIA_ASIGNACION', 'evidencia_asignacion_id')
                ->onDelete('restrict');
            $table->foreignId('elemento_asignacion_id')
                ->nullable()
                ->constrained('ELEMENTO_ASIGNACION', 'elemento_asignacion_id')
                ->onDelete('restrict');
            $table->foreignId('usuario_id')
                ->constrained('USUARIO', 'usuario_id')
                ->onDelete('restrict');
            $table->string('motivo', 1000);
            $table->dateTime('fecha_sugerida');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->dateTime('fecha_resolucion')->nullable();
            $table->foreignId('usuario_resolutor_id')
                ->nullable()
                ->constrained('USUARIO', 'usuario_id')
                ->onDelete('restrict');
            $table->string('justificacion', 500)->nullable();
            $table->timestamps();
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