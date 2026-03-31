<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ARCHIVO', function (Blueprint $table) {
            // Hacer evidencia_id nullable (en modelo flexible no siempre existe)
            $table->unsignedBigInteger('evidencia_id')->nullable()->change();

            // FK alternativa para modelo flexible: archivo pertenece directamente a un ELEMENTO
            $table->unsignedBigInteger('elemento_id')->nullable()->after('evidencia_id');

            $table->foreign('elemento_id', 'ar_elemento_id_foreign')
                  ->references('elemento_id')->on('ELEMENTO')->onDelete('restrict');

            $table->index('elemento_id', 'idx_ar_elemento_id');
            $table->index(['elemento_id', 'tipo'], 'idx_ar_elemento_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('ARCHIVO', function (Blueprint $table) {
            $table->dropForeign('ar_elemento_id_foreign');
            $table->dropIndex('idx_ar_elemento_id');
            $table->dropIndex('idx_ar_elemento_tipo');
            $table->dropColumn('elemento_id');

            // Revertir evidencia_id a NOT NULL
            $table->unsignedBigInteger('evidencia_id')->nullable(false)->change();
        });
    }
};
