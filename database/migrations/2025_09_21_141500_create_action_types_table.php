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
        // Tabla de tipos de acción
        Schema::create('TIPO_ACCION', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('tipo_accion_id');
            // Descripción del tipo de acción
            $table->string('descripcion', 100);
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de tipos de acción
        Schema::dropIfExists('TIPO_ACCION');
    }
};
