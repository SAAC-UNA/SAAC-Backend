<?php

namespace App\Events;

use App\Models\ElementApproval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ElementRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ElementApproval $approval;

    public function __construct(ElementApproval $approval)
    {
        $this->approval = $approval;
    }
}
