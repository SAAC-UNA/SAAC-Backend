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
        // Tabla de facultades
        Schema::create('FACULTAD', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('facultad_id');
            // Relación con universidad
            $table->foreignId('universidad_id')->constrained('UNIVERSIDAD', 'universidad_id')->onDelete('cascade');
            // Relación con sede
            $table->foreignId('sede_id')->constrained('SEDE', 'sede_id')->onDelete('cascade');
            // Nombre de la facultad
            $table->string('nombre', 250);
            // Estado de la facultad
            $table->boolean('activo')->default(true);
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de facultades
        Schema::dropIfExists('FACULTAD');
    }
};
