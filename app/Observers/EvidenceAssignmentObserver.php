<?php

namespace App\Observers;

use App\Models\EvidenceAssignment;
use App\Services\TradicionalEvidenceService;

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
        private readonly TradicionalEvidenceService $evidenceService
    ) {}

    /** Se llama cuando el profesor actualiza su asignación (En Progreso, Completado, etc.) */
    public function updated(EvidenceAssignment $assignment): void
    {
        if ($assignment->wasChanged('estado')) {
            $this->evidenceService->recalcularEstadoEvidencia($assignment->evidencia_id);
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
