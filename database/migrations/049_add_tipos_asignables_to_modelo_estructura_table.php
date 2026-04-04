<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna tipos_asignables (JSON) a MODELO_ESTRUCTURA.
     *
     * Estrategia 3 — restricción de nivel de asignación:
     * El modelo define qué tipos de nodo ELEMENTO pueden recibir asignaciones
     * y archivos. Si el array está vacío o null, no hay restricción (retrocompat).
     *
     * Ejemplo: ["fuente", "indicador"]
     * Cualquier elemento cuyo campo `tipo` no esté en esta lista rechaza
     * asignaciones y subida de archivos con 422.
     */
    public function up(): void
    {
        Schema::table('MODELO_ESTRUCTURA', function (Blueprint $table) {
            $table->json('tipos_asignables')
                  ->nullable()
                  ->after('activo')
                  ->comment('Tipos de nodo ELEMENTO que pueden recibir asignaciones y archivos en este modelo.');
        });
    }

    public function down(): void
    {
        Schema::table('MODELO_ESTRUCTURA', function (Blueprint $table) {
            $table->dropColumn('tipos_asignables');
        });
    }
};
