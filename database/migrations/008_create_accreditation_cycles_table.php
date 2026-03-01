<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        // Tabla de ciclos de acreditación
        Schema::create('CICLO_ACREDITACION', function (Blueprint $table) {
            // Clave primaria 
            $table->id()->name('ciclo_acreditacion_id');
            // Relación con carrera_sede
            $table->foreignId('carrera_sede_id')->constrained('CARRERA_SEDE', 'carrera_sede_id')->onDelete('restrict');
            // Nombre del ciclo
            $table->string('nombre', 50);
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices de performance
            $table->index('carrera_sede_id', 'idx_ca_carrera_sede_id');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('CICLO_ACREDITACION');
    }
};
