<?php

namespace App\Listeners;

use App\Events\DeadlineApproaching;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Notificar Vencimiento de Plazo
 * 
 * Cuando una evidencia está próxima a vencer, notifica al responsable
 * Canal: AMBOS (interno + email) - Es crítico
 */
class NotifyDeadlineApproaching implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DeadlineApproaching $event): void
    {
        $assignment = $event->assignment->load(['evidence', 'user']);
        $daysRemaining = $event->daysRemaining;

        $evidence = $assignment->evidence;
        $user = $assignment->user;

        // Construir mensaje de urgencia
        $titulo = $daysRemaining === 1 
            ? '⚠️ Plazo vence mañana'
            : "⚠️ Plazo vence en {$daysRemaining} días";

        $mensaje = sprintf(
            'La evidencia "%s" (%s) vence el %s. %s',
            $evidence->descripcion ?? 'Sin descripción',
            $evidence->nomenclatura ?? 'N/A',
            $assignment->fecha_limite->format('d/m/Y'),
            $daysRemaining <= 3 ? '¡Acción urgente requerida!' : 'Por favor, completa la evidencia a tiempo.'
        );

        $enlace = "/evidencias/{$evidence->evidencia_id}";

        try {
            NotificationService::create([
                'usuario_id' => $user->usuario_id,
                'tipo_evento' => Notification::TIPO_VENCIMIENTO_PLAZO,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'relacionado' => $evidence,
                'enlace' => $enlace,
                'forzar_email' => true, // Siempre enviar email
                'metadatos' => [
                    'evidencia_id' => $evidence->evidencia_id,
                    'dias_restantes' => $daysRemaining,
                    'fecha_limite' => $assignment->fecha_limite->toDateString(),
                    'urgente' => $daysRemaining <= 3,
                ],
            ]);

            Log::info('Notificación de vencimiento de plazo creada', [
                'usuario_id' => $user->usuario_id,
                'evidencia_id' => $evidence->evidencia_id,
                'dias_restantes' => $daysRemaining,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando notificación de vencimiento de plazo', [
                'error' => $e->getMessage(),
                'evidencia_id' => $evidence->evidencia_id,
            ]);
        }
    }
}
