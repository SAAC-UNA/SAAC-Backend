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
        // Tabla de bitácora de acciones (auditoría)
        Schema::create('BITACORA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('bitacora_id');
            // Relación con tipo de acción (restrict: mantener integridad de catálogo)
            $table->foreignId('tipo_accion_id')->constrained('TIPO_ACCION', 'tipo_accion_id')->onDelete('restrict');
            // Relación con usuario (restrict: mantener historial de auditoría)
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            // Fecha y hora de la acción
            $table->timestamp('fecha_hora')->useCurrent();
            // Detalle de la acción (opcional)
            $table->text('detalle')->nullable();
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de bitácora de acciones
        Schema::dropIfExists('BITACORA');
    }
};
