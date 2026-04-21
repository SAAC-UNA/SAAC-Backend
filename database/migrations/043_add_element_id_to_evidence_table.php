<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega elemento_id a EVIDENCIA para soporte del modelo flexible.
     *
     * Regla de negocio (excluyente):
     *   - Evidencia tradicional: criterio_id NOT NULL, elemento_id NULL
     *   - Evidencia flexible:    criterio_id NULL,     elemento_id NOT NULL
     *
     * Por eso en este mismo paso también se hace criterio_id nullable.
     */
    public function up(): void
    {
        Schema::table('EVIDENCIA', function (Blueprint $table) {
            // 1. Hacer nullable el FK existente para permitir evidencias flexibles
            $table->foreignId('criterio_id')
                ->nullable()
                ->change();

            // 2. Nueva FK hacia ELEMENTO (modelo flexible)
            $table->foreignId('elemento_id')
                ->nullable()
                ->after('criterio_id')
                ->constrained('ELEMENTO', 'elemento_id')
                ->onDelete('set null');

            // Índice de performance para filtrado por elemento
            $table->index('elemento_id', 'idx_ev_elemento_id');
        });
    }

    public function down(): void
    {
        Schema::table('EVIDENCIA', function (Blueprint $table) {
            $table->dropForeign(['elemento_id']);
            $table->dropIndex('idx_ev_elemento_id');
            $table->dropColumn('elemento_id');

            // Restaurar criterio_id a NOT NULL
            $table->foreignId('criterio_id')
                ->nullable(false)
                ->change();
        });
    }
};
