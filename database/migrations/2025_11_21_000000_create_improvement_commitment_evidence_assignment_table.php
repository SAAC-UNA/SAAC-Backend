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
        // Tabla intermedia para relacionar compromisos de mejora con evidencias asignadas
        Schema::create('COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION', function (Blueprint $table) {
            // Clave primaria compuesta
            $table->unsignedBigInteger('compromiso_mejora_id');
            $table->unsignedBigInteger('evidencia_asignacion_id');
            
            // Foreign keys con nombres cortos
            $table->foreign('compromiso_mejora_id', 'fk_cm_evidencia_compromiso')
                ->references('compromiso_mejora_id')
                ->on('COMPROMISO_MEJORA')
                ->onDelete('cascade');
            
            $table->foreign('evidencia_asignacion_id', 'fk_cm_evidencia_asignacion')
                ->references('evidencia_asignacion_id')
                ->on('EVIDENCIA_ASIGNACION')
                ->onDelete('cascade');
            
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Definir clave primaria compuesta
            $table->primary(['compromiso_mejora_id', 'evidencia_asignacion_id'], 'pk_compromiso_evidencia');
            
            // Índice para búsquedas inversas (desde evidencia hacia compromiso)
            $table->index('evidencia_asignacion_id', 'idx_evidencia_asignacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla intermedia
        Schema::dropIfExists('COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION');
    }
};
