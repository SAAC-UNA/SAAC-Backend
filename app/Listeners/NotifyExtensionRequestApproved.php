<?php

namespace App\Listeners;

use App\Events\ExtensionRequestApproved;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyExtensionRequestApproved implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ExtensionRequestApproved $event): void
    {
        try {
            $request = $event->extensionRequest;
            $request->load(['usuario', 'evidenceAssignment.evidence']);
            
            $solicitante = $request->usuario;
            $evidencia = $request->evidenceAssignment->evidence ?? null;
            $comentario = $event->comentario;

            // Notificar al usuario solicitante
            NotificationService::create([
                'usuario_id' => $solicitante->usuario_id,
                'tipo_evento' => Notification::TIPO_RESPUESTA_AMPLIACION,
                'titulo' => '¡Solicitud de Ampliación Aprobada!',
                'mensaje' => "Tu solicitud de ampliación de plazo ha sido APROBADA" .
                             ($evidencia ? " para la evidencia \"{$evidencia->nombre}\"" : "") .
                             ".\n\nNueva fecha límite: " . \Carbon\Carbon::parse($request->fecha_limite_propuesta)->format('d/m/Y') .
                             ($comentario ? "\n\nComentarios del encargado: {$comentario}" : ""),
                'relacionado' => $request,
                'enlace' => "/solicitudes-ampliacion/{$request->solicitud_ampliacion_id}",
                'metadatos' => [
                    'solicitud_id' => $request->solicitud_ampliacion_id,
                    'nueva_fecha_limite' => $request->fecha_limite_propuesta,
                    'aprobada' => true,
                ]
            ]);

            Log::info('Notificación de aprobación de ampliación enviada', [
                'solicitud_id' => $request->solicitud_ampliacion_id,
                'usuario_id' => $solicitante->usuario_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar aprobación de ampliación', [
                'error' => $e->getMessage(),
                'solicitud_id' => $event->extensionRequest->solicitud_ampliacion_id ?? null
            ]);
        }
    }
}
