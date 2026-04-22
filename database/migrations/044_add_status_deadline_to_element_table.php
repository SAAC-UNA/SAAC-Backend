<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ELEMENTO', function (Blueprint $table) {
            $table->enum('estado', ['Pendiente', 'En Progreso', 'Completado', 'Vencido'])
                  ->default('Pendiente')
                  ->after('activo');
            $table->date('fecha_limite')
                  ->nullable()
                  ->after('estado');

            $table->index('estado', 'idx_elem_estado');
            $table->index('fecha_limite', 'idx_elem_fecha_limite');
        });
    }

    public function down(): void
    {
        Schema::table('ELEMENTO', function (Blueprint $table) {
            $table->dropIndex('idx_elem_estado');
            $table->dropIndex('idx_elem_fecha_limite');
            $table->dropColumn(['estado', 'fecha_limite']);
        });
    }
};
