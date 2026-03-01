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
        // Tabla de componentes
        Schema::create('COMPONENTE', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('componente_id');
            // Relación con dimensión (restrict: no borrar dimensión si tiene componentes)
            $table->foreignId('dimension_id')->constrained('DIMENSION', 'dimension_id')->onDelete('restrict');
            // Nombre del componente
            $table->string('nombre', 80);
            // Nomenclatura del componente
            $table->string('nomenclatura', 20);
            // Estado del componente
            $table->boolean('activo')->default(true);
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices de performance
            $table->index('dimension_id', 'idx_co_dimension_id');
            $table->index('activo', 'idx_co_activo');
            $table->index(['dimension_id', 'activo'], 'idx_co_dimension_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de componentes
        Schema::dropIfExists('COMPONENTE');
    }
};
