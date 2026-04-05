<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * HU-010 (flexible): Agrega el estado 'pendiente' al enum de APROBACION_ELEMENTO.
 *
 * 'pendiente' es el estado inicial: ninguna aprobación/rechazo registrado aún.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE APROBACION_ELEMENTO MODIFY COLUMN estado ENUM('aprobado', 'rechazado', 'pendiente') NOT NULL");
    }

    public function down(): void
    {
        DB::table('APROBACION_ELEMENTO')->where('estado', 'pendiente')->delete();
        DB::statement("ALTER TABLE APROBACION_ELEMENTO MODIFY COLUMN estado ENUM('aprobado', 'rechazado') NOT NULL");
    }
};
