<?php

namespace App\Events;

use App\Models\EvidenceAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvidenceAssignmentDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $assignmentData;

    /**
     * Create a new event instance.
     * 
     * @param array $assignmentData Datos de la asignación antes de eliminarla
     */
    public function __construct(array $assignmentData)
    {
        $this->assignmentData = $assignmentData;
    }
}
