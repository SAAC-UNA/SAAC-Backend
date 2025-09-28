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
        // Tabla de sedes
        Schema::create('SEDE', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('sede_id');
            // Relación con universidad
            $table->foreignId('universidad_id')->constrained('UNIVERSIDAD', 'universidad_id')->onDelete('cascade');
            // Nombre de la sede
            $table->string('nombre', 250);
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de sedes
        Schema::dropIfExists('SEDE');
    }
};
