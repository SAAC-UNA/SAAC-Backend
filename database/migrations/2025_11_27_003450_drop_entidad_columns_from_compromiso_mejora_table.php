<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('COMPROMISO_MEJORA', function (Blueprint $table) {
            // Eliminar índice primero
            $table->dropIndex('idx_entidad');
            
            // Eliminar columnas obsoletas
            $table->dropColumn(['entidad_tipo', 'entidad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('COMPROMISO_MEJORA', function (Blueprint $table) {
            // Restaurar columnas
            $table->enum('entidad_tipo', ['ESTANDAR', 'DIMENSION', 'COMPONENTE', 'CRITERIO', 'EVIDENCIA'])->after('proceso_id');
            $table->unsignedBigInteger('entidad_id')->after('entidad_tipo');
            
            // Restaurar índice
            $table->index(['entidad_tipo', 'entidad_id'], 'idx_entidad');
        });
    }
};
