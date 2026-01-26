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

        // Determinar si el plazo está vencido o próximo a vencer
        $isExpired = $daysRemaining < 0;
        $daysAbs = abs($daysRemaining);

        // Construir mensaje según estado
        if ($isExpired) {
            $titulo = "🚨 Plazo VENCIDO hace {$daysAbs} días";
            $mensaje = sprintf(
                'URGENTE: La evidencia "%s" (%s) venció el %s. El plazo expiró hace %d días. ¡Requiere atención inmediata!',
                $evidence->descripcion ?? 'Sin descripción',
                $evidence->nomenclatura ?? 'N/A',
                $assignment->fecha_limite->format('d/m/Y'),
                $daysAbs
            );
        } elseif ($daysRemaining === 0) {
            $titulo = '🔥 Plazo vence HOY';
            $mensaje = sprintf(
                'ÚLTIMO DÍA: La evidencia "%s" (%s) vence hoy. ¡Acción urgente requerida!',
                $evidence->descripcion ?? 'Sin descripción',
                $evidence->nomenclatura ?? 'N/A'
            );
        } elseif ($daysRemaining === 1) {
            $titulo = '⚠️ Plazo vence mañana';
            $mensaje = sprintf(
                'La evidencia "%s" (%s) vence mañana (%s). ¡Acción urgente requerida!',
                $evidence->descripcion ?? 'Sin descripción',
                $evidence->nomenclatura ?? 'N/A',
                $assignment->fecha_limite->format('d/m/Y')
            );
        } else {
            $titulo = "⚠️ Plazo vence en {$daysRemaining} días";
            $mensaje = sprintf(
                'La evidencia "%s" (%s) vence el %s. %s',
                $evidence->descripcion ?? 'Sin descripción',
                $evidence->nomenclatura ?? 'N/A',
                $assignment->fecha_limite->format('d/m/Y'),
                $daysRemaining <= 3 ? '¡Acción urgente requerida!' : 'Por favor, completa la evidencia a tiempo.'
            );
        }

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
                    'vencido' => $isExpired,
                    'dias_vencido' => $isExpired ? $daysAbs : 0,
                ],
            ]);

            Log::info('Notificación de vencimiento de plazo creada', [
                'usuario_id' => $user->usuario_id,
                'evidencia_id' => $evidence->evidencia_id,
                'dias_restantes' => $daysRemaining,
                'vencido' => $isExpired,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando notificación de vencimiento de plazo', [
                'error' => $e->getMessage(),
                'evidencia_id' => $evidence->evidencia_id,
            ]);
        }
    }
}
