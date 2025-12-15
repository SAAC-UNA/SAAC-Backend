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
        // Migración vacía - el campo comentario ya se agregó en la creación de la tabla
        // Esta migración se mantiene solo por historial
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay nada que revertir
    }
};
