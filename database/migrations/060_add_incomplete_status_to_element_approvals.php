<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * HU-010 (flexible): Agrega el estado 'incompleto' al enum de APROBACION_ELEMENTO.
 *
 * 'incompleto' indica que el elemento tiene al menos 1 hijo rechazado
 * individualmente pero no todos están en el mismo estado. El bloque bloquea
 * los hijos aprobados y espera corrección de los rechazados.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE APROBACION_ELEMENTO MODIFY COLUMN estado ENUM('aprobado', 'rechazado', 'pendiente', 'incompleto') NOT NULL");
    }

    public function down(): void
    {
        DB::table('APROBACION_ELEMENTO')->where('estado', 'incompleto')->update(['estado' => 'pendiente']);
        DB::statement("ALTER TABLE APROBACION_ELEMENTO MODIFY COLUMN estado ENUM('aprobado', 'rechazado', 'pendiente') NOT NULL");
    }
};
