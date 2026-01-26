<?php

namespace App\Events;

use App\Models\EvidenceAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento: Plazo Próximo a Vencer
 * 
 * Se dispara cuando una evidencia está próxima a vencer (configurable: 3, 7, 15 días)
 * Trigger: Comando programado que revisa fechas límite
 */
class DeadlineApproaching
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EvidenceAssignment $assignment;
    public int $daysRemaining;

    public function __construct(EvidenceAssignment $assignment, int $daysRemaining)
    {
        $this->assignment = $assignment;
        $this->daysRemaining = $daysRemaining;
    }
}
