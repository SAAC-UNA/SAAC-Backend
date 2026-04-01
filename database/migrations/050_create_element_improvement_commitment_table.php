<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla COMPROMISO_MEJORA_ELEMENTO para el modelo flexible (HU-010).
 * Equivalente a COMPROMISO_MEJORA del modelo tradicional, pero vinculado a ELEMENTO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('COMPROMISO_MEJORA_ELEMENTO', function (Blueprint $table) {
            $table->id('compromiso_elemento_id');

            $table->unsignedBigInteger('proceso_id');
            $table->foreign('proceso_id')
                  ->references('proceso_id')
                  ->on('PROCESO')
                  ->onDelete('cascade');

            $table->text('descripcion');

            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            $table->enum('estado', ['Pendiente', 'En Progreso', 'Completado', 'Vencido'])
                  ->default('Pendiente');

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index('estado', 'idx_cme_estado');
            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_cme_fechas');
            $table->index('activo', 'idx_cme_activo');
            $table->index('proceso_id', 'idx_cme_proceso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('COMPROMISO_MEJORA_ELEMENTO');
    }
};
