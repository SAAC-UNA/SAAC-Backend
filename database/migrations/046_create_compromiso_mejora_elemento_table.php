<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('COMPROMISO_MEJORA_ELEMENTO', function (Blueprint $table) {
            $table->id('compromiso_elemento_id');
            $table->foreignId('proceso_id')
                ->constrained('PROCESO', 'proceso_id')
                ->onDelete('cascade');
            $table->text('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['Pendiente', 'En Progreso', 'Completado', 'Vencido'])->default('Pendiente');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index('estado', 'idx_cme_estado');
            $table->index('fecha_fin', 'idx_cme_fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('COMPROMISO_MEJORA_ELEMENTO');
    }
};