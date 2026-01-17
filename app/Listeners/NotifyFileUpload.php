<?php

namespace App\Listeners;

use App\Events\FileUploaded;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Notificar Subida de Archivo
 * 
 * Cuando se sube un archivo, notifica al usuario que lo subió (confirmación)
 * Canal: INTERNO - No es crítico
 */
class NotifyFileUpload implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(FileUploaded $event): void
    {
        $file = $event->file->load(['user', 'evidence']);

        $user = $file->user;
        $evidence = $file->evidence;

        // Construir mensaje de confirmación
        $titulo = 'Archivo subido correctamente';
        $mensaje = sprintf(
            'Tu archivo "%s" se ha subido exitosamente para la evidencia %s.',
            $file->nombre_original,
            $evidence->nomenclatura ?? 'N/A'
        );

        $enlace = "/evidencias/{$evidence->evidencia_id}";

        try {
            NotificationService::create([
                'usuario_id' => $user->usuario_id,
                'tipo_evento' => Notification::TIPO_CARGA_ARCHIVO,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'relacionado' => $file,
                'enlace' => $enlace,
                'metadatos' => [
                    'archivo_id' => $file->archivo_id,
                    'evidencia_id' => $evidence->evidencia_id,
                    'nombre_archivo' => $file->nombre_original,
                    'tamanio' => $file->tamanio_bytes,
                ],
            ]);

            Log::info('Notificación de archivo subido creada', [
                'usuario_id' => $user->usuario_id,
                'archivo_id' => $file->archivo_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando notificación de archivo subido', [
                'error' => $e->getMessage(),
                'archivo_id' => $file->archivo_id,
            ]);
        }
    }
}
