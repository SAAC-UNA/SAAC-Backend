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
        // Tabla de estados de evidencia
        Schema::create('ESTADO_EVIDENCIA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('estado_evidencia_id');
            // Nombre del estado
           // $table->foreignId('carrera_id')->constrained('CARRERA', 'carrera_id')->onDelete('cascade');/// duda
            $table->string('nombre', 30);
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de estados de evidencia
        Schema::dropIfExists('ESTADO_EVIDENCIA');
    }
};
