<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // Agregar CHECK constraint para patrón XOR
        // Exactamente UNO de evidencia_asignacion_id o elemento_asignacion_id debe estar presente
        DB::statement('
            ALTER TABLE SOLICITUD_AMPLIACION
            ADD CONSTRAINT chk_solicitud_ampliacion_xor
            CHECK (
                (evidencia_asignacion_id IS NOT NULL AND elemento_asignacion_id IS NULL) OR
                (evidencia_asignacion_id IS NULL AND elemento_asignacion_id IS NOT NULL)
            )
        ');
    }

    public function down(): void
    {// Eliminar CHECK constraint
        DB::statement('ALTER TABLE SOLICITUD_AMPLIACION DROP CONSTRAINT chk_solicitud_ampliacion_xor');

        
        Schema::table('SOLICITUD_AMPLIACION', function (Blueprint $table) {
            $table->dropForeign('sa_elemento_asignacion_id_foreign');
            $table->dropIndex('idx_sa_elemento_asignacion_id');
            $table->dropColumn('elemento_asignacion_id');
        });
    }
};
