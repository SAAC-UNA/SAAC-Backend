<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega índice FULLTEXT en ELEMENTO(nomenclatura, descripcion)
     * para búsqueda de texto libre con MATCH...AGAINST en lugar de LIKE '%texto%'.
     *
     * Beneficio: MySQL usa el índice en lugar de full-table-scan.
     * Requiere InnoDB (MySQL 5.6+) — ya cumplido en este proyecto.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE ELEMENTO ADD FULLTEXT INDEX ft_elemento_busqueda (nomenclatura, descripcion)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ELEMENTO DROP INDEX ft_elemento_busqueda');
    }
};
