<?php

namespace App\Events;

use App\Models\ExtensionRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExtensionRequestCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ExtensionRequest $extensionRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(ExtensionRequest $extensionRequest)
    {
        $this->extensionRequest = $extensionRequest;
    }
}
