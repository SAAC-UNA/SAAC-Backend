<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 048 — Soporte retroalimentación y ampliación para modelo flexible
 *
 * 1. ELEMENTO_ASIGNACION.estado: añade 'Observada' y 'Validada' al enum
 *    (equivalente a los estados de retroalimentación de EVIDENCIA en HU-013)
 *
 * 2. SOLICITUD_AMPLIACION: permite vincular solicitudes a ELEMENTO_ASIGNACION
 *    (equivalente HU-016 para modelo flexible)
 *    - evidencia_asignacion_id se vuelve nullable (XOR con elemento_asignacion_id)
 *    - se agrega columna nullable elemento_asignacion_id con FK
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Ampliar enum de ELEMENTO_ASIGNACION ───────────────────────────
        DB::statement(
            "ALTER TABLE ELEMENTO_ASIGNACION "
            . "MODIFY estado ENUM('Pendiente','En Progreso','Completado','Vencido','Observada','Validada') "
            . "NOT NULL DEFAULT 'Pendiente'"
        );

        // ── 2. Adaptar SOLICITUD_AMPLIACION ──────────────────────────────────
        // Eliminar FK existente de evidencia_asignacion_id para poder hacerla nullable
        DB::statement(
            'ALTER TABLE SOLICITUD_AMPLIACION '
            . 'DROP FOREIGN KEY solicitud_ampliacion_evidencia_asignacion_id_foreign'
        );

        // Hacer evidencia_asignacion_id nullable (XOR: o tiene evidencia o tiene elemento)
        DB::statement(
            'ALTER TABLE SOLICITUD_AMPLIACION '
            . 'MODIFY evidencia_asignacion_id BIGINT UNSIGNED NULL'
        );

        // Re-añadir FK de evidencia_asignacion_id (ahora nullable)
        DB::statement(
            'ALTER TABLE SOLICITUD_AMPLIACION '
            . 'ADD CONSTRAINT sa_evidencia_asignacion_id_foreign '
            . 'FOREIGN KEY (evidencia_asignacion_id) '
            . 'REFERENCES EVIDENCIA_ASIGNACION(evidencia_asignacion_id) ON DELETE RESTRICT'
        );

        // Añadir columna elemento_asignacion_id con FK a ELEMENTO_ASIGNACION
        Schema::table('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            $table->unsignedBigInteger('elemento_asignacion_id')
                  ->nullable()
                  ->after('evidencia_asignacion_id')
                  ->comment('FK flexible: XOR con evidencia_asignacion_id');

            $table->foreign('elemento_asignacion_id', 'sa_elemento_asignacion_id_foreign')
                  ->references('elemento_asignacion_id')
                  ->on('ELEMENTO_ASIGNACION')
                  ->onDelete('restrict');

            $table->index('elemento_asignacion_id', 'idx_sa_elemento_asignacion_id');
        });
    }

    public function down(): void
    {
        // Eliminar columna elemento_asignacion_id
        Schema::table('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            $table->dropForeign('sa_elemento_asignacion_id_foreign');
            $table->dropIndex('idx_sa_elemento_asignacion_id');
            $table->dropColumn('elemento_asignacion_id');
        });

        // Revertir evidencia_asignacion_id a NOT NULL
        DB::statement(
            'ALTER TABLE SOLICITUD_AMPLIACION '
            . 'DROP FOREIGN KEY sa_evidencia_asignacion_id_foreign'
        );
        DB::statement(
            'ALTER TABLE SOLICITUD_AMPLIACION '
            . 'MODIFY evidencia_asignacion_id BIGINT UNSIGNED NOT NULL'
        );
        DB::statement(
            'ALTER TABLE SOLICITUD_AMPLIACION '
            . 'ADD CONSTRAINT solicitud_ampliacion_evidencia_asignacion_id_foreign '
            . 'FOREIGN KEY (evidencia_asignacion_id) '
            . 'REFERENCES EVIDENCIA_ASIGNACION(evidencia_asignacion_id) ON DELETE RESTRICT'
        );

        // Revertir enum de ELEMENTO_ASIGNACION
        DB::statement(
            "ALTER TABLE ELEMENTO_ASIGNACION "
            . "MODIFY estado ENUM('Pendiente','En Progreso','Completado','Vencido') "
            . "NOT NULL DEFAULT 'Pendiente'"
        );
    }
};
