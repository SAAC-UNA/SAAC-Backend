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
        // Tabla de evidencias
        Schema::create('EVIDENCIA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('evidencia_id');
            // Relación con criterio (restrict: no borrar criterio si tiene evidencias)
            $table->foreignId('criterio_id')->constrained('CRITERIO', 'criterio_id')->onDelete('restrict');
            // Relación de estado de evidencia migrada a enum directo (CREATE)
            $table->enum('estado', ['Pendiente','En Proceso','Completado','Vencido','Aprobado','Rechazado','Observada','Validada'])->default('Pendiente');
            // Descripción de la evidencia
            $table->string('descripcion', 300);
            // Nomenclatura de la evidencia
            $table->string('nomenclatura', 20);
            // Estado de la evidencia
            $table->boolean('activo')->default(true);
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices de performance para queries frecuentes
            $table->index('criterio_id', 'idx_ev_criterio_id');
            $table->index('estado', 'idx_ev_estado');
            $table->index('activo', 'idx_ev_activo');
            $table->index('created_at', 'idx_ev_created_at');
            $table->index('nomenclatura', 'idx_ev_nomenclatura');
            $table->index(['criterio_id', 'activo'], 'idx_ev_criterio_activo');
            $table->index(['estado', 'activo'], 'idx_ev_estado_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de evidencias
        Schema::dropIfExists('EVIDENCIA');
    }
};
