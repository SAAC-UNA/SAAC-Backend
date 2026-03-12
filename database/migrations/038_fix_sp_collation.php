<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aligns the database default collation with the table column collation.
 *
 * All table columns use utf8mb4_unicode_ci. MySQL 8.0 defaults to
 * utf8mb4_0900_ai_ci, which caused SQLSTATE 1267 collation-mismatch errors.
 * Setting the database default here ensures any future objects (views, events,
 * etc.) inherit the correct collation without needing explicit per-column casts.
 */
return new class extends Migration
{
    public function up(): void
    {
        $db = DB::select('SELECT DATABASE() AS db')[0]->db;
        DB::statement("ALTER DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        $db = DB::select('SELECT DATABASE() AS db')[0]->db;
        DB::statement("ALTER DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    }
};
