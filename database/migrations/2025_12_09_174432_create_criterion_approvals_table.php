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
        Schema::create('APROBACION_CRITERIO', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('aprobacion_criterio_id');
            
            // Relación con criterio (restrict: no borrar criterio si tiene aprobaciones)
            $table->foreignId('criterio_id')->constrained('CRITERIO', 'criterio_id')->onDelete('restrict');
            
            // Relación con proceso (cascade: si se borra el proceso, se borran sus aprobaciones)
            $table->foreignId('proceso_id')->constrained('PROCESO', 'proceso_id')->onDelete('cascade');
            
            // Relación con usuario que aprueba (restrict: no borrar usuario si tiene aprobaciones)
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            
            // Estado de la aprobación: 'aprobado' o 'rechazado'
            $table->enum('estado', ['aprobado', 'rechazado']);
            
            // Comentario opcional de la aprobación/rechazo (máximo 100 caracteres)
            $table->string('comentario', 100)->nullable();
            
            // Timestamps de creación y actualización (created_at = fecha de aprobación)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('APROBACION_CRITERIO');
    }
};
