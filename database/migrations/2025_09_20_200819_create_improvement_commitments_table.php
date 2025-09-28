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
        // Tabla de compromisos de mejora
        Schema::create('COMPROMISO_MEJORA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('compromiso_mejora_id');
            // Relación con proceso
            $table->foreignId('proceso_id')->constrained('PROCESO', 'proceso_id')->onDelete('cascade');
            // Fecha de inicio del compromiso (opcional)
            $table->date('fecha_inicio')->nullable();
            // Fecha de fin del compromiso (opcional)
            $table->date('fecha_fin')->nullable();
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de compromisos de mejora
        Schema::dropIfExists('COMPROMISO_MEJORA');
    }
};
