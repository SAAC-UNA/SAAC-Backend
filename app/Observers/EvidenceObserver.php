<?php

namespace App\Observers;

use App\Models\Evidence;
use App\Services\CriterionService;

class EvidenceObserver
{
    public function __construct(
        private readonly CriterionService $criterionService
    ) {}

    /**
     * Cuando se actualiza una evidencia, recalcular el estado del criterio padre.
     * No se usa wasChanged() porque en algunos contextos (múltiples observers)
     * puede retornar false incorrectamente. recalcularEstado() tiene su propia
     * guarda interna que evita writes innecesarios.
     */
    public function updated(Evidence $evidence): void
    {
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }

    /**
     * Cuando se crea una nueva evidencia, recalcular el estado del criterio.
     */
    public function created(Evidence $evidence): void
    {
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }

    /**
     * Cuando se elimina una evidencia, recalcular el estado del criterio.
     */
    public function deleted(Evidence $evidence): void
    {
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }
}
