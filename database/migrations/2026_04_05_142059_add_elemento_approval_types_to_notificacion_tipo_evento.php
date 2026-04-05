<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE NOTIFICACION MODIFY COLUMN tipo_evento ENUM(
            'asignacion_evidencia',
            'asignacion_elemento',
            'carga_archivo',
            'vencimiento_plazo',
            'devolucion_observacion',
            'aprobacion_criterio',
            'rechazo_criterio',
            'aprobacion_evidencia',
            'rechazo_evidencia',
            'aprobacion_elemento',
            'rechazo_elemento',
            'solicitud_ampliacion',
            'respuesta_ampliacion',
            'comentario_nuevo',
            'actualizacion_sistema'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE NOTIFICACION MODIFY COLUMN tipo_evento ENUM(
            'asignacion_evidencia',
            'asignacion_elemento',
            'carga_archivo',
            'vencimiento_plazo',
            'devolucion_observacion',
            'aprobacion_criterio',
            'aprobacion_evidencia',
            'rechazo_evidencia',
            'solicitud_ampliacion',
            'respuesta_ampliacion',
            'comentario_nuevo',
            'actualizacion_sistema'
        ) NOT NULL");
    }
};
