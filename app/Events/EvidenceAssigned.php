<?php

namespace App\Events;

use App\Models\EvidenceAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento: Evidencia Asignada a Usuario
 * 
 * Se dispara cuando se asigna una evidencia a un profesor/encargado
 * Trigger: Al crear un registro en EVIDENCIA_ASIGNACION
 */
class EvidenceAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EvidenceAssignment $assignment;

    public function __construct(EvidenceAssignment $assignment)
    {
        $this->assignment = $assignment;
    }
}
