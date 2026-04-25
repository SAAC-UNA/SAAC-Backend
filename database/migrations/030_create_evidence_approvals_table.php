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
        // Tabla de aprobaciones de evidencias
        Schema::create('APROBACION_EVIDENCIA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id('aprobacion_evidencia_id');
            // Llave foránea hacia la tabla EVIDENCIA
            $table->unsignedBigInteger('evidencia_id');
            // Llave foránea hacia la tabla PROCESO
            $table->unsignedBigInteger('proceso_id');
            // Llave foránea hacia la aprobación del criterio
            $table->unsignedBigInteger('criterio_aprobacion_id');
            // Llave foránea hacia el usuario que realiza la aprobación
            $table->unsignedBigInteger('usuario_id');
            // Estado de la aprobación de la evidencia
            $table->enum('estado', ['Pendiente', 'En Proceso', 'Completado', 'Vencido', 'Aprobado', 'Rechazado', 'Observada', 'Validada'])->default('Pendiente');
            // Observaciones sobre la evidencia aprobada/rechazada
            $table->string('comentario', 500)->nullable();
            // Nueva fecha límite aplicable a la aprobación de evidencias
            $table->date('nueva_fecha_limite')->nullable();
            // Timestamps de creación y actualización
            $table->timestamps();

            // Foreign keys
            $table->foreign('evidencia_id')->references('evidencia_id')->on('EVIDENCIA')->onDelete('cascade');
            $table->foreign('proceso_id')->references('proceso_id')->on('PROCESO')->onDelete('cascade');
            $table->foreign('criterio_aprobacion_id')->references('aprobacion_criterio_id')->on('APROBACION_CRITERIO')->onDelete('cascade');
            $table->foreign('usuario_id')->references('usuario_id')->on('USUARIO')->onDelete('cascade');

            // Indices
            $table->index(['evidencia_id', 'proceso_id'], 'idx_ae_evidencia_proceso'); // cubre búsquedas por evidencia_id también
            $table->index('criterio_aprobacion_id', 'idx_ae_criterio_aprobacion_id');
            $table->index('usuario_id', 'idx_ae_usuario_id');
            $table->index('estado', 'idx_ae_estado');
            $table->index('nueva_fecha_limite', 'idx_ae_nueva_fecha_limite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('APROBACION_EVIDENCIA');
    }
};
