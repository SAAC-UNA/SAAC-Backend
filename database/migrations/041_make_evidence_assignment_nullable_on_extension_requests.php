<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE = 'SOLICITUD_AMPLIACION';
    private const FK = 'solicitud_ampliacion_evidencia_asignacion_id_foreign';

    public function up(): void
    {
        DB::statement('ALTER TABLE `' . self::TABLE . '` DROP FOREIGN KEY `' . self::FK . '`');
        DB::statement('ALTER TABLE `' . self::TABLE . '` MODIFY `evidencia_asignacion_id` BIGINT UNSIGNED NULL');
        DB::statement(
            'ALTER TABLE `' . self::TABLE . '` ADD CONSTRAINT `' . self::FK . '` ' .
            'FOREIGN KEY (`evidencia_asignacion_id`) ' .
            'REFERENCES `EVIDENCIA_ASIGNACION` (`evidencia_asignacion_id`) ON DELETE RESTRICT'
        );
    }

    public function down(): void
    {
        $hasFlexibleRequests = DB::table(self::TABLE)
            ->whereNull('evidencia_asignacion_id')
            ->exists();

        if ($hasFlexibleRequests) {
            return;
        }

        DB::statement('ALTER TABLE `' . self::TABLE . '` DROP FOREIGN KEY `' . self::FK . '`');
        DB::statement('ALTER TABLE `' . self::TABLE . '` MODIFY `evidencia_asignacion_id` BIGINT UNSIGNED NOT NULL');
        DB::statement(
            'ALTER TABLE `' . self::TABLE . '` ADD CONSTRAINT `' . self::FK . '` ' .
            'FOREIGN KEY (`evidencia_asignacion_id`) ' .
            'REFERENCES `EVIDENCIA_ASIGNACION` (`evidencia_asignacion_id`) ON DELETE RESTRICT'
        );
    }
};
