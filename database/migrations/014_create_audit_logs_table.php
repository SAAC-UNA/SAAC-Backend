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
            // Módulo del sistema donde ocurrió la acción
            $table->string('modulo', 100)->nullable()->comment('Módulo del sistema donde ocurrió la acción (ej: Usuarios, Evidencias)');
            // Relación con usuario (restrict: mantener historial de auditoría)
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            // Fecha y hora de la acción
            $table->timestamp('fecha_hora')->useCurrent();
            // Detalle de la acción (opcional)
            $table->text('detalle')->nullable();
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices de performance para reportes y auditoría
            $table->index('usuario_id', 'idx_bi_usuario_id');
            $table->index('tipo_accion_id', 'idx_bi_tipo_accion_id');
            $table->index('modulo', 'idx_bi_modulo');
            $table->index('fecha_hora', 'idx_bi_fecha_hora');
            $table->index(['usuario_id', 'fecha_hora'], 'idx_bi_usuario_fecha');
            $table->index(['modulo', 'fecha_hora'], 'idx_bi_modulo_fecha');
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
