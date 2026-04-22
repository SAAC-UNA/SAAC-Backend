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
        Schema::table('BITACORA', function (Blueprint $table) {
            // Eliminar foreign key constraint existente
            $table->dropForeign(['usuario_id']);
            
            // Cambiar el campo a nullable
            $table->foreignId('usuario_id')->nullable()->change();
            
            // Recrear foreign key constraint con nullable
            $table->foreign('usuario_id')
                  ->references('usuario_id')
                  ->on('USUARIO')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('BITACORA', function (Blueprint $table) {
            // Eliminar foreign key constraint
            $table->dropForeign(['usuario_id']);
            
            // Revertir a NOT NULL
            $table->foreignId('usuario_id')->nullable(false)->change();
            
            // Recrear foreign key constraint original
            $table->foreign('usuario_id')
                  ->references('usuario_id')
                  ->on('USUARIO')
                  ->onDelete('restrict');
        });
    }
};
