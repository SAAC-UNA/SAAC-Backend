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
        // Migración vacía - duplicada por error
        // Se mantiene solo por historial
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compromiso_mejora_evidencia_asignacion', function (Blueprint $table) {
            //
        });
    }
};
