<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HU-010: Agrega campo 'comentario' a APROBACION_EVIDENCIA.
 *
 * Permite registrar la observación/motivo cuando se rechaza una evidencia
 * individualmente, para que el responsable sepa qué corregir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('APROBACION_EVIDENCIA', function (Blueprint $table) {
            $table->string('comentario', 500)->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('APROBACION_EVIDENCIA', function (Blueprint $table) {
            $table->dropColumn('comentario');
        });
    }
};
