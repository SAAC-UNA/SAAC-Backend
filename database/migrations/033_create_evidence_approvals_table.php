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
        Schema::create('APROBACION_EVIDENCIA', function (Blueprint $table) {
            $table->id('aprobacion_evidencia_id');
            $table->unsignedBigInteger('evidencia_id');
            $table->unsignedBigInteger('proceso_id');
            $table->unsignedBigInteger('criterio_aprobacion_id'); // Referencia a la aprobación del criterio
            $table->unsignedBigInteger('usuario_id');
            $table->enum('estado', ['aprobado', 'rechazado'])->default('aprobado');
            $table->timestamps();

            // Foreign keys
            $table->foreign('evidencia_id')->references('evidencia_id')->on('EVIDENCIA')->onDelete('cascade');
            $table->foreign('proceso_id')->references('proceso_id')->on('PROCESO')->onDelete('cascade');
            $table->foreign('criterio_aprobacion_id')->references('aprobacion_criterio_id')->on('APROBACION_CRITERIO')->onDelete('cascade');
            $table->foreign('usuario_id')->references('usuario_id')->on('USUARIO')->onDelete('cascade');

            // Indexes
            $table->index(['evidencia_id', 'proceso_id'], 'idx_ae_evidencia_proceso'); // cubre búsquedas por evidencia_id también
            $table->index('criterio_aprobacion_id', 'idx_ae_criterio_aprobacion_id');
            $table->index('usuario_id', 'idx_ae_usuario_id');
            $table->index('estado', 'idx_ae_estado');
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
