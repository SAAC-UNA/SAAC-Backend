<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Amplía el enum CRITERIO.estado para incluir 'Vencido'.
 *
 * Motivación: cuando alguna de las evidencias de un criterio pasa a estado
 * 'Vencido' (plazo expirado sin completar), el criterio tampoco puede
 * reflejar 'Pendiente' o 'En Proceso' — debe mostrar 'Vencido'.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE CRITERIO MODIFY COLUMN estado
             ENUM('Pendiente', 'En Proceso', 'Completado', 'Vencido')
             NOT NULL DEFAULT 'Pendiente'"
        );
    }

    public function down(): void
    {
        // Revertir cualquier valor 'Vencido' antes de quitar el valor del enum
        DB::statement("UPDATE CRITERIO SET estado = 'En Proceso' WHERE estado = 'Vencido'");

        DB::statement(
            "ALTER TABLE CRITERIO MODIFY COLUMN estado
             ENUM('Pendiente', 'En Proceso', 'Completado')
             NOT NULL DEFAULT 'Pendiente'"
        );
    }
};
