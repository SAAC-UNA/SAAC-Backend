<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento: Múltiples Archivos Subidos
 * 
 * Se dispara cuando un usuario sube 2 o más archivos simultáneamente.
 * Optimización: En lugar de crear N notificaciones individuales,
 * crea una sola notificación resumen.
 */
class MultipleFilesUploaded
{
    use Dispatchable, SerializesModels;

    public array $filesData;
    public int $usuarioId;
    public int $evidenciaId;
    public int $procesoId;
    public int $totalFiles;

    /**
     * @param array $filesData Array con info de cada archivo: [['nombre_original' => ..., 'size_kb' => ...], ...]
     * @param int $usuarioId ID del usuario que subió los archivos
     * @param int $evidenciaId ID de la evidencia
     * @param int $procesoId ID del proceso
     */
    public function __construct(array $filesData, int $usuarioId, int $evidenciaId, int $procesoId)
    {
        $this->filesData = $filesData;
        $this->usuarioId = $usuarioId;
        $this->evidenciaId = $evidenciaId;
        $this->procesoId = $procesoId;
        $this->totalFiles = count($filesData);
    }
}
