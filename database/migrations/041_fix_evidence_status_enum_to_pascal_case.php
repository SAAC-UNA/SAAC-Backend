<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE  = 'EVIDENCIA';
    private const COLUMN = 'estado';

    // Mapeo de valores viejos (snake_case / minúscula) a PascalCase
    private const MAP = [
        'pendiente'  => 'Pendiente',
        'en_proceso' => 'En Proceso',
        'completado' => 'Completado',
        'vencido'    => 'Vencido',
        'aprobado'   => 'Aprobado',
        'rechazado'  => 'Rechazado',
        'observada'  => 'Observada',
        'validada'   => 'Validada',
    ];

    private const ENUM_PASCAL = ['Pendiente', 'En Proceso', 'Completado', 'Vencido', 'Aprobado', 'Rechazado', 'Observada', 'Validada'];

    public function up(): void
    {
        // 1. Actualizar datos existentes a PascalCase
        foreach (self::MAP as $old => $new) {
            DB::table(self::TABLE)
                ->where(self::COLUMN, $old)
                ->update([self::COLUMN => $new]);
        }

        // 2. Redefinir el ENUM con valores PascalCase
        $enumList = implode("','", self::ENUM_PASCAL);
        DB::statement("ALTER TABLE `" . self::TABLE . "` MODIFY COLUMN `" . self::COLUMN . "` ENUM('{$enumList}') NOT NULL DEFAULT 'Pendiente'");
    }

    public function down(): void
    {
        // Revertir datos a minúscula
        $reverseMap = array_flip(self::MAP);
        foreach ($reverseMap as $pascal => $snake) {
            DB::table(self::TABLE)
                ->where(self::COLUMN, $pascal)
                ->update([self::COLUMN => $snake]);
        }

        // Redefinir el ENUM con valores originales
        $oldEnum = implode("','", array_keys(self::MAP));
        DB::statement("ALTER TABLE `" . self::TABLE . "` MODIFY COLUMN `" . self::COLUMN . "` ENUM('{$oldEnum}') NOT NULL DEFAULT 'pendiente'");
    }
};
