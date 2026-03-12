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
        // Tabla de carreras
        Schema::create('CARRERA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('carrera_id');
            // Nombre de la carrera
            $table->string('nombre', 250);
            // Estado de la carrera
            $table->boolean('activo')->default(true);
            // Timestamps de creación y actualización
            $table->timestamps();

            // Índices de performance
            $table->index('activo', 'idx_ca_activo');
            $table->index('nombre', 'idx_ca_nombre');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de carreras
        Schema::dropIfExists('CARRERA');
    }
};
