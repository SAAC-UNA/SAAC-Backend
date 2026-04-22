<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE SOLICITUD_AMPLIACION MODIFY estado ENUM('pendiente','aprobada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        // Eliminar registros cancelados antes de revertir para evitar error de datos
        DB::statement("UPDATE SOLICITUD_AMPLIACION SET estado = 'pendiente' WHERE estado = 'cancelada'");
        DB::statement("ALTER TABLE SOLICITUD_AMPLIACION MODIFY estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente'");
    }
};
