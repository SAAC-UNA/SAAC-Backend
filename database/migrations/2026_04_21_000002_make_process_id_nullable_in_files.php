<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hace proceso_id nullable en ARCHIVO.
     *
     * Los archivos adjuntos a informes de acreditación (HU-027) no pertenecen
     * a ningún proceso específico: son resoluciones SINAES a nivel de ciclo.
     * Con esta migración se permite crear registros ARCHIVO sin proceso_id.
     */
    public function up(): void
    {
        Schema::table('ARCHIVO', function (Blueprint $table) {
            $table->unsignedBigInteger('proceso_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ARCHIVO', function (Blueprint $table) {
            $table->unsignedBigInteger('proceso_id')->nullable(false)->change();
        });
    }
};
