<?php

namespace App\Observers;

use App\Models\CriterionApproval;
use App\Models\EvidenceApproval;
use App\Models\EvidenceAssignment;
use App\Services\EvidenceService;

/**
 * Cascada de estados: EVIDENCIA_ASIGNACION → EVIDENCIA → CRITERIO
 *
 * Cuando el estado de una asignación cambia (el profesor lo actualiza
 * manualmente o el comando CheckDeadlines lo marca como Vencido),
 * este observer recalcula el estado de la evidencia padre.
 * La evidencia, al guardarse, dispara EvidenceObserver que recalcula el criterio.
 */
class EvidenceAssignmentObserver
{
    public function __construct(
        private readonly EvidenceService $evidenceService
    ) {}

    /** Se llama cuando el profesor actualiza su asignación (En Progreso, Completado, etc.) */
    public function updated(EvidenceAssignment $assignment): void
    {
        if ($assignment->wasChanged('estado')) {
            $this->evidenceService->recalcularEstadoEvidencia($assignment->evidencia_id);

            // HU-010: si el responsable marcó Completado y existe una APROBACION_EVIDENCIA
            // rechazada para esta evidencia+proceso, el bloque incompleto vuelve a pendiente
            // para que el RF sepa que hay nuevas correcciones listas para revisar.
            if ($assignment->estado === EvidenceAssignment::ESTADO_COMPLETADO) {
                $tieneRechazo = EvidenceApproval::where('evidencia_id', $assignment->evidencia_id)
                    ->where('proceso_id', $assignment->proceso_id)
                    ->where('estado', 'rechazado')
                    ->exists();

                if ($tieneRechazo) {
                    CriterionApproval::where('estado', 'incompleto')
                        ->whereHas('evidenceApprovals', function ($q) use ($assignment) {
                            $q->where('evidencia_id', $assignment->evidencia_id)
                              ->where('proceso_id', $assignment->proceso_id);
                        })
                        ->update(['estado' => 'pendiente']);
                }
            }
        }
    }

    /** Se llama al crear una nueva asignación (podría revertir "Completado" si se agrega un nuevo responsable) */
    public function created(EvidenceAssignment $assignment): void
    {
        $this->evidenceService->recalcularEstadoEvidencia($assignment->evidencia_id);
    }

    /** Se llama al eliminar una asignación */
    public function deleted(EvidenceAssignment $assignment): void
    {
        $this->evidenceService->recalcularEstadoEvidencia($assignment->evidencia_id);
    }
}
