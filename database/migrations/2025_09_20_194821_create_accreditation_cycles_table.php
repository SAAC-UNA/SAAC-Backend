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
        // Tabla de ciclos de acreditación
        Schema::create('CICLO_ACREDITACION', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('ciclo_acreditacion_id');
            // Relación con carrera_sede
            $table->foreignId('carrera_sede_id')->constrained('CARRERA_SEDE', 'carrera_sede_id')->onDelete('cascade');
            // Nombre del ciclo
            $table->string('nombre', 50);
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de ciclos de acreditación
        Schema::dropIfExists('CICLO_ACREDITACION');
    }
};
