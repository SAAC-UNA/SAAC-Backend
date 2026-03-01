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
        // Tabla de criterios
        Schema::create('CRITERIO', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('criterio_id');
            // Relación con componente (restrict: no borrar componente si tiene criterios)
            $table->foreignId('componente_id')->constrained('COMPONENTE', 'componente_id')->onDelete('restrict');
            // Descripción del criterio
            $table->string('descripcion', 300);
            // Nomenclatura del criterio
            $table->string('nomenclatura', 20);
            // Estado del criterio
            $table->boolean('activo')->default(true);
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices de performance para queries frecuentes
            $table->index('componente_id', 'idx_cr_componente_id');
            $table->index('activo', 'idx_cr_activo');
            $table->index('nomenclatura', 'idx_cr_nomenclatura');
            $table->index(['componente_id', 'activo'], 'idx_cr_componente_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de criterios
        Schema::dropIfExists('CRITERIO');
    }
};
