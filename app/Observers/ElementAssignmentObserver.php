<?php

namespace App\Observers;

use App\Models\ElementAssignment;
use App\Models\ElementExtensionRequest;
use Illuminate\Support\Facades\Log;

class ElementAssignmentObserver
{
    /**
     * Al actualizar una asignación, si el estado pasa a Completado o Validada,
     * cancela automáticamente todas las solicitudes de ampliación pendientes
     * asociadas a esa asignación.
     */
    public function updating(ElementAssignment $model): void
    {
        if (!$model->isDirty('estado')) {
            return;
        }

        $nuevoEstado = $model->estado;

        if (!in_array($nuevoEstado, [ElementAssignment::ESTADO_COMPLETADO, ElementAssignment::ESTADO_VALIDADA])) {
            return;
        }

        $canceladas = ElementExtensionRequest::where('elemento_asignacion_id', $model->elemento_asignacion_id)
            ->where('estado', ElementExtensionRequest::ESTADO_PENDIENTE)
            ->update([
                'estado'        => ElementExtensionRequest::ESTADO_CANCELADA,
                'justificacion' => 'Auto-cancelada: la asignación fue marcada como ' . strtolower($nuevoEstado) . '.',
            ]);

        if ($canceladas > 0) {
            Log::info("Observer: {$canceladas} solicitud(es) de ampliación canceladas automáticamente.", [
                'elemento_asignacion_id' => $model->elemento_asignacion_id,
                'nuevo_estado'           => $nuevoEstado,
            ]);
        }
    }
}
