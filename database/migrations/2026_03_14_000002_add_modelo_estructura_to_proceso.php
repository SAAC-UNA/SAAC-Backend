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
        Schema::table('PROCESO', function (Blueprint $table) {
            // Agregar FK a MODELO_ESTRUCTURA
            $table->unsignedBigInteger('modelo_estructura_id')->nullable()->after('ciclo_acreditacion_id');
            
            $table->foreign('modelo_estructura_id')
                  ->references('modelo_estructura_id')
                  ->on('MODELO_ESTRUCTURA')
                  ->onDelete('restrict');
            
            $table->index('modelo_estructura_id', 'idx_proceso_modelo');
        });
        
        // Actualizar procesos existentes al modelo tradicional (id=1) si existe
        DB::statement("UPDATE PROCESO SET modelo_estructura_id = 1 WHERE modelo_estructura_id IS NULL");
        
        // Hacer el campo NOT NULL después de llenar datos
        Schema::table('PROCESO', function (Blueprint $table) {
            $table->unsignedBigInteger('modelo_estructura_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('PROCESO', function (Blueprint $table) {
            $table->dropForeign(['modelo_estructura_id']);
            $table->dropIndex('idx_proceso_modelo');
            $table->dropColumn('modelo_estructura_id');
        });
    }
};
