<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arquitectura B (modelo flexible paralelo):
     * EVIDENCIA solo pertenece al modelo tradicional (CRITERIO → EVIDENCIA).
     * El modelo flexible usa ELEMENTO → ELEMENTO_ASIGNACION → ARCHIVO directamente.
     *
     * Se elimina elemento_id de EVIDENCIA y criterio_id vuelve a ser NOT NULL.
     */
    public function up(): void
    {
        // Eliminar evidencias huérfanas (sin criterio_id) creadas durante pruebas
        // de Arquitectura A — en producción no debería haber ninguna.
        $huerfanas = DB::table('EVIDENCIA')->whereNull('criterio_id')->pluck('evidencia_id');
        if ($huerfanas->isNotEmpty()) {
            DB::table('EVIDENCIA_ASIGNACION')->whereIn('evidencia_id', $huerfanas)->delete();
            DB::table('EVIDENCIA')->whereIn('evidencia_id', $huerfanas)->delete();
        }

        Schema::table('EVIDENCIA', function (Blueprint $table) {
            // elemento_id ya fue eliminado si la migración falló previamente
            // (MySQL no hace rollback DDL). Solo intentamos si aún existe.
            if (Schema::hasColumn('EVIDENCIA', 'elemento_id')) {
                $table->dropForeign(['elemento_id']);
                $table->dropIndex('idx_ev_elemento_id');
                $table->dropColumn('elemento_id');
            }

            // criterio_id vuelve a ser obligatorio — toda evidencia pertenece a un criterio
            $table->unsignedBigInteger('criterio_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('EVIDENCIA', function (Blueprint $table) {
            $table->unsignedBigInteger('criterio_id')->nullable()->change();

            $table->foreignId('elemento_id')
                ->nullable()
                ->after('criterio_id')
                ->constrained('ELEMENTO', 'elemento_id')
                ->onDelete('set null');

            $table->index('elemento_id', 'idx_ev_elemento_id');
        });
    }
};
