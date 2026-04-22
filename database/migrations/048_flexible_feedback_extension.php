<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE ELEMENTO_ASIGNACION "
            . "MODIFY estado ENUM('Pendiente','En Progreso','Completado','Vencido','Observada','Validada') "
            . "NOT NULL DEFAULT 'Pendiente'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE ELEMENTO_ASIGNACION "
            . "MODIFY estado ENUM('Pendiente','En Progreso','Completado','Vencido') "
            . "NOT NULL DEFAULT 'Pendiente'"
        );
    }
};
