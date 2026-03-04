<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes collation mismatch between stored procedure VARCHAR parameters and table columns.
 *
 * Root cause: SPs were created while the database's default collation was
 * utf8mb4_0900_ai_ci (MySQL 8.0 default). Table columns were created with
 * utf8mb4_unicode_ci (explicit in migrations). VARCHAR parameters in SPs
 * without an explicit CHARACTER SET inherit collation_database at creation
 * time, causing SQLSTATE 1267 when comparing against column values.
 *
 * Fix: alter the database default collation to utf8mb4_unicode_ci, then
 * drop and recreate all stored procedures so their untyped VARCHAR parameters
 * pick up utf8mb4_unicode_ci from collation_database.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Align the database default collation with the table column collation.
        $db = DB::select('SELECT DATABASE() AS db')[0]->db;
        DB::statement("ALTER DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 2. Drop and recreate all stored procedures using the 037 migration.
        //    Requiring the file re-instantiates the anonymous migration class.
        $migration = require __DIR__ . '/037_create_stored_procedures.php';
        $migration->down(); // drops all SPs (uses DROP PROCEDURE IF EXISTS — safe)
        $migration->up();   // recreates all SPs; VARCHAR params now inherit utf8mb4_unicode_ci
    }

    public function down(): void
    {
        // Revert database collation to MySQL 8.0 default
        $db = DB::select('SELECT DATABASE() AS db')[0]->db;
        DB::statement("ALTER DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");

        // Recreate SPs with old collation
        $migration = require __DIR__ . '/037_create_stored_procedures.php';
        $migration->down();
        $migration->up();
    }
};
