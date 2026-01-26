<?php

namespace App\Listeners;

use App\Events\ExtensionRequestCreated;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyExtensionRequestCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ExtensionRequestCreated $event): void
    {
        try {
            $request = $event->extensionRequest;
            $request->load(['usuario', 'evidenceAssignment.evidence', 'evidenceAssignment.user']);
            
            $assignment = $request->evidenceAssignment;
            $solicitante = $request->usuario;
            $evidencia = $assignment->evidence ?? null;

            // Notificar a los encargados de acreditación
            $encargados = \App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'Encargado de Acreditación');
            })->get();

            foreach ($encargados as $encargado) {
                NotificationService::create([
                    'usuario_id' => $encargado->usuario_id,
                    'tipo_evento' => Notification::TIPO_SOLICITUD_AMPLIACION,
                    'titulo' => 'Nueva Solicitud de Ampliación de Plazo',
                    'mensaje' => "{$solicitante->nombre} ha solicitado una ampliación de plazo" . 
                                 ($evidencia ? " para la evidencia \"{$evidencia->nombre}\"" : "") .
                                 ".\n\nMotivo: {$request->motivo}" .
                                 "\nNueva fecha propuesta: " . \Carbon\Carbon::parse($request->fecha_limite_propuesta)->format('d/m/Y'),
                    'relacionado' => $request,
                    'enlace' => "/solicitudes-ampliacion/{$request->solicitud_ampliacion_id}",
                    'metadatos' => [
                        'solicitud_id' => $request->solicitud_ampliacion_id,
                        'usuario_solicitante' => $solicitante->nombre,
                        'evidencia_nombre' => $evidencia->nombre ?? null,
                        'fecha_propuesta' => $request->fecha_limite_propuesta,
                    ]
                ]);
            }

            Log::info('Notificaciones de solicitud de ampliación enviadas', [
                'solicitud_id' => $request->solicitud_ampliacion_id,
                'encargados_notificados' => $encargados->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar solicitud de ampliación creada', [
                'error' => $e->getMessage(),
                'solicitud_id' => $event->extensionRequest->solicitud_ampliacion_id ?? null
            ]);
        }
    }
}
