<?php

namespace App\Listeners;

use App\Events\ExtensionRequestRejected;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyExtensionRequestRejected implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ExtensionRequestRejected $event): void
    {
        try {
            $request = $event->extensionRequest;
            $request->load(['usuario', 'evidenceAssignment.evidence']);
            
            $solicitante = $request->usuario;
            $evidencia = $request->evidenceAssignment->evidence ?? null;
            $motivoRechazo = $event->motivoRechazo;

            // Notificar al usuario solicitante
            NotificationService::create([
                'usuario_id' => $solicitante->usuario_id,
                'tipo_evento' => Notification::TIPO_RESPUESTA_AMPLIACION,
                'titulo' => 'Solicitud de Ampliación Rechazada',
                'mensaje' => "Tu solicitud de ampliación de plazo ha sido RECHAZADA" .
                             ($evidencia ? " para la evidencia \"{$evidencia->nombre}\"" : "") .
                             ".\n\nMotivo del rechazo: {$motivoRechazo}" .
                             "\n\nLa fecha límite original se mantiene: " . \Carbon\Carbon::parse($request->evidenceAssignment->fecha_limite)->format('d/m/Y'),
                'relacionado' => $request,
                'enlace' => "/solicitudes-ampliacion/{$request->solicitud_ampliacion_id}",
                'metadatos' => [
                    'solicitud_id' => $request->solicitud_ampliacion_id,
                    'rechazada' => true,
                    'motivo_rechazo' => $motivoRechazo,
                ]
            ]);

            Log::info('Notificación de rechazo de ampliación enviada', [
                'solicitud_id' => $request->solicitud_ampliacion_id,
                'usuario_id' => $solicitante->usuario_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar rechazo de ampliación', [
                'error' => $e->getMessage(),
                'solicitud_id' => $event->extensionRequest->solicitud_ampliacion_id ?? null
            ]);
        }
    }
}
