<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE APROBACION_CRITERIO MODIFY COLUMN estado ENUM('aprobado', 'rechazado', 'pendiente', 'incompleto') NOT NULL");
    }

    public function down(): void
    {
        DB::table('APROBACION_CRITERIO')->where('estado', 'pendiente')->delete();
        DB::statement("ALTER TABLE APROBACION_CRITERIO MODIFY COLUMN estado ENUM('aprobado', 'rechazado') NOT NULL");
    }
};
