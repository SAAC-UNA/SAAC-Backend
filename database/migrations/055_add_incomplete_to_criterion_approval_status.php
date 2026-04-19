<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * HU-010: Agrega el estado 'incompleto' al enum de APROBACION_CRITERIO.
 *
 * 'incompleto' indica que el bloque tiene al menos 1 evidencia rechazada
 * individualmente pero no todas están en el mismo estado, por lo que el
 * bloque queda bloqueando las aprobadas y esperando corrección de las rechazadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE APROBACION_CRITERIO MODIFY COLUMN estado ENUM('aprobado', 'rechazado', 'pendiente', 'incompleto') NOT NULL");
    }

    public function down(): void
    {
        DB::table('APROBACION_CRITERIO')->where('estado', 'incompleto')->update(['estado' => 'pendiente']);
        DB::statement("ALTER TABLE APROBACION_CRITERIO MODIFY COLUMN estado ENUM('aprobado', 'rechazado', 'pendiente') NOT NULL");
    }
};
