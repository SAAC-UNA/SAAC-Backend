<?php

namespace App\Events;

use App\Models\ExtensionRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExtensionRequestRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ExtensionRequest $extensionRequest;
    public string $motivoRechazo;

    /**
     * Create a new event instance.
     */
    public function __construct(ExtensionRequest $extensionRequest, string $motivoRechazo)
    {
        $this->extensionRequest = $extensionRequest;
        $this->motivoRechazo = $motivoRechazo;
    }
}
