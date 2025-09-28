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
        // Tabla de procesos
        Schema::create('PROCESO', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('proceso_id');
            // Relación con ciclo de acreditación
            $table->foreignId('ciclo_acreditacion_id')->constrained('CICLO_ACREDITACION', 'ciclo_acreditacion_id')->onDelete('cascade');
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de procesos
        Schema::dropIfExists('PROCESO');
    }
};
