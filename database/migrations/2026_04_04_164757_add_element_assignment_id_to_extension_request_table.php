<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            $table->unsignedBigInteger('elemento_asignacion_id')
                  ->nullable()
                  ->after('evidencia_asignacion_id');

            $table->foreign('elemento_asignacion_id', 'sa_elemento_asignacion_id_foreign')
                  ->references('elemento_asignacion_id')
                  ->on('ELEMENTO_ASIGNACION')
                  ->onDelete('restrict');

            $table->index('elemento_asignacion_id', 'idx_sa_elemento_asignacion_id');
        });
    }

    public function down(): void
    {
        Schema::table('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            $table->dropForeign('sa_elemento_asignacion_id_foreign');
            $table->dropIndex('idx_sa_elemento_asignacion_id');
            $table->dropColumn('elemento_asignacion_id');
        });
    }
};
