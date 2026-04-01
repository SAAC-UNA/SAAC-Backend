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
        // MySQL no permite cambiar ENUM con schema builder; se usa ALTER TABLE directo.
        // Se re-declaran todos los valores existentes + el nuevo 'asignacion_elemento'.
        DB::statement("ALTER TABLE `NOTIFICACION` MODIFY COLUMN `tipo_evento` ENUM(
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
        ) NOT NULL COMMENT 'Tipo de evento que generó la notificación'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `NOTIFICACION` MODIFY COLUMN `tipo_evento` ENUM(
            'asignacion_evidencia',
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
        ) NOT NULL COMMENT 'Tipo de evento que generó la notificación'");
    }
};
