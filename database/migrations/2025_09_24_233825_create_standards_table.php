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
        // Tabla de estándares
        Schema::create('ESTANDAR', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('estandar_id');
            // Relación con criterio (restrict: no borrar criterio si tiene estándares)
            $table->foreignId('criterio_id')->constrained('CRITERIO')->name('criterio_id')->onDelete('restrict');
            // Descripción del estándar
            $table->string('descripcion', 250)->name('descripcion');
            // Estado del estándar
            $table->boolean('activo')->default(true)->name('activo');
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de estándares
        Schema::dropIfExists('ESTANDAR');
    }
};
