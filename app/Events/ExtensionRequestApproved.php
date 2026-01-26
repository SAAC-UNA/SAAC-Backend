<?php

namespace App\Events;

use App\Models\ExtensionRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExtensionRequestApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ExtensionRequest $extensionRequest;
    public string $comentario;

    /**
     * Create a new event instance.
     */
    public function __construct(ExtensionRequest $extensionRequest, string $comentario = '')
    {
        $this->extensionRequest = $extensionRequest;
        $this->comentario = $comentario;
    }
}
