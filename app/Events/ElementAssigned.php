<?php

namespace App\Events;

use App\Models\ElementAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Element Assigned to User (flexible model)
 *
 * Fired when an element is assigned to a user.
 * Trigger: Creating a record in ELEMENTO_ASIGNACION
 * HU-007 / HU-018
 */
class ElementAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ElementAssignment $assignment;

    public function __construct(ElementAssignment $assignment)
    {
        $this->assignment = $assignment;
    }
}
