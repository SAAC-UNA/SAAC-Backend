<?php

namespace App\Observers;

use App\Models\ElementApproval;
use App\Models\ElementAssignment;
use App\Models\ElementExtensionRequest;
use App\Models\StructureElement;
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

    /**
     * HU-010 flexible: cuando el responsable marca su asignación como Completado
     * y existe una APROBACION_ELEMENTO rechazada para ese elemento+proceso,
     * el bloque padre incompleto vuelve a 'pendiente' para que el evaluador sepa
     * que hay nuevas correcciones listas para revisar.
     */
    public function updated(ElementAssignment $assignment): void
    {
        if (!$assignment->wasChanged('estado')) {
            return;
        }

        if ($assignment->estado !== ElementAssignment::ESTADO_COMPLETADO) {
            return;
        }

        $tieneRechazo = ElementApproval::where('elemento_id', $assignment->elemento_id)
            ->where('proceso_id', $assignment->proceso_id)
            ->where('estado', 'rechazado')
            ->exists();

        if (!$tieneRechazo) {
            return;
        }

        $elemento = StructureElement::find($assignment->elemento_id);
        if (!$elemento || !$elemento->padre_id) {
            return;
        }

        ElementApproval::where('elemento_id', $elemento->padre_id)
            ->where('proceso_id', $assignment->proceso_id)
            ->where('estado', 'incompleto')
            ->update(['estado' => 'pendiente']);
    }
}
