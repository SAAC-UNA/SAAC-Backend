<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE APROBACION_CRITERIO ADD COLUMN nueva_fecha_limite DATE NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE APROBACION_CRITERIO DROP COLUMN nueva_fecha_limite");
    }
};
