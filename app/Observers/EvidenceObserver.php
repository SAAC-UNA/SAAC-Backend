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
     *
     * MODELO FLEXIBLE (HU-013): Si la evidencia es flexible (elemento_id no nulo),
     * criterio_id es null y no aplica recalculo. Pasar null a int lanza TypeError
     * en PHP 8.1+, por lo que se omite el recalculo en ese caso.
     */
    public function updated(Evidence $evidence): void
    {
        if ($evidence->criterio_id === null) return;
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }

    /**
     * Cuando se crea una nueva evidencia, recalcular el estado del criterio.
     *
     * MODELO FLEXIBLE (HU-013): Ver nota en updated().
     */
    public function created(Evidence $evidence): void
    {
        if ($evidence->criterio_id === null) return;
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }

    /**
     * Cuando se elimina una evidencia, recalcular el estado del criterio.
     *
     * MODELO FLEXIBLE (HU-013): Ver nota en updated().
     */
    public function deleted(Evidence $evidence): void
    {
        if ($evidence->criterio_id === null) return;
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }
}
