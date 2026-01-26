<?php

namespace App\Events;

use App\Models\File;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento: Archivo Subido
 * 
 * Se dispara cuando un usuario sube un archivo de evidencia
 * Trigger: Al crear un registro en ARCHIVO
 */
class FileUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }
}
