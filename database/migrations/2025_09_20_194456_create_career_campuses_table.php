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
        // Tabla intermedia de carreras y sedes
        Schema::create('CARRERA_SEDE', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('carrera_sede_id');
            // Relación con carrera
            $table->foreignId('carrera_id')->constrained('CARRERA', 'carrera_id')->onDelete('cascade');
            // Relación con sede
            $table->foreignId('sede_id')->constrained('SEDE', 'sede_id')->onDelete('cascade');
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla intermedia de carreras y sedes
        Schema::dropIfExists('CARRERA_SEDE');
    }
};
