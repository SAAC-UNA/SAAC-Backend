<?php

namespace App\Events;

use App\Models\CriterionApproval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CriterionRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CriterionApproval $approval;

    /**
     * Create a new event instance.
     */
    public function __construct(CriterionApproval $approval)
    {
        $this->approval = $approval;
    }
}
