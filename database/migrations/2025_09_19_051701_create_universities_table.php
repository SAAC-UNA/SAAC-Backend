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
        // Tabla de universidades
        Schema::create('UNIVERSIDAD', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('universidad_id');
            // Nombre de la universidad
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
        // Elimina la tabla de universidades
        Schema::dropIfExists('UNIVERSIDAD');
    }
};
