<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE  = 'CRITERIO';
    private const COLUMN = 'estado';
    private const ESTADOS = ['Pendiente', 'En Proceso', 'Completado'];

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->enum(self::COLUMN, self::ESTADOS)
                  ->default('Pendiente')
                  ->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn(self::COLUMN);
        });
    }
};
