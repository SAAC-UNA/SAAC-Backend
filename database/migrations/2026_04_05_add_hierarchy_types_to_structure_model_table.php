<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna tipos_jerarquia (JSON) a MODELO_ESTRUCTURA.
     *
     * Define la jerarquía de tipos de nodo para modelos de tipo elemento_flexible.
     * Cada entrada indica el nombre del tipo y su tipo-padre (null = raíz).
     *
     * Ejemplo:
     * [
     *   { "tipo": "dimension", "padre_tipo": null },
     *   { "tipo": "pauta",     "padre_tipo": "dimension" },
     *   { "tipo": "fuente",    "padre_tipo": "pauta" }
     * ]
     *
     * Si está vacío/null, no se aplica restricción de jerarquía de tipos (retrocompatibilidad).
     */
    public function up(): void
    {
        Schema::table('MODELO_ESTRUCTURA', function (Blueprint $table) {
            $table->json('tipos_jerarquia')
                  ->nullable()
                  ->after('tipos_asignables')
                  ->comment('Jerarquía de tipos de nodo ELEMENTO para modelos elemento_flexible.');
        });
    }

    public function down(): void
    {
        Schema::table('MODELO_ESTRUCTURA', function (Blueprint $table) {
            $table->dropColumn('tipos_jerarquia');
        });
    }
};
