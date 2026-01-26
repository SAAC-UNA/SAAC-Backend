<?php

namespace App\Listeners;

use App\Events\EvidenceAssigned;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Notificar Asignación de Evidencia
 * 
 * Cuando se asigna una evidencia, notifica al usuario asignado
 * Canal: AMBOS (interno + email) - Es crítico
 */
class NotifyEvidenceAssignment implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(EvidenceAssigned $event): void
    {
        $assignment = $event->assignment->load(['evidence.criterion.component.dimension', 'user', 'process']);
        
        $evidence = $assignment->evidence;
        $user = $assignment->user;
        $process = $assignment->process;

        // Construir mensaje descriptivo
        $titulo = 'Nueva evidencia asignada';
        $mensaje = sprintf(
            'Se te ha asignado la evidencia "%s" (%s) del criterio %s. Fecha límite: %s',
            $evidence->descripcion ?? 'Sin descripción',
            $evidence->nomenclatura ?? 'N/A',
            $evidence->criterion->nomenclatura ?? 'N/A',
            $assignment->fecha_limite ? $assignment->fecha_limite->format('d/m/Y') : 'No definida'
        );

        // Construir enlace directo
        $enlace = "/evidencias/{$evidence->evidencia_id}/asignar";

        try {
            NotificationService::create([
                'usuario_id' => $user->usuario_id,
                'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'relacionado' => $evidence,
                'enlace' => $enlace,
                'metadatos' => [
                    'evidencia_id' => $evidence->evidencia_id,
                    'criterio_id' => $evidence->criterio_id,
                    'proceso_id' => $process->proceso_id,
                    'fecha_limite' => $assignment->fecha_limite?->toDateString(),
                ],
            ]);

            Log::info('Notificación de asignación de evidencia creada', [
                'usuario_id' => $user->usuario_id,
                'evidencia_id' => $evidence->evidencia_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando notificación de asignación de evidencia', [
                'error' => $e->getMessage(),
                'usuario_id' => $user->usuario_id,
                'evidencia_id' => $evidence->evidencia_id,
            ]);
        }
    }
}
