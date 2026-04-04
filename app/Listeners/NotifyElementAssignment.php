<?php

namespace App\Listeners;

use App\Events\ElementAssigned;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Notify Element Assignment (flexible model)
 *
 * Fires when an element is assigned to a user via the flexible model.
 * Mirrors NotifyEvidenceAssignment for the ELEMENTO_ASIGNACION flow.
 * HU-018
 */
class NotifyElementAssignment implements ShouldQueue
{
    public function handle(ElementAssigned $event): void
    {
        $assignment = $event->assignment->load(['element', 'user', 'process', 'assignedBy']);

        $element   = $assignment->element;
        $user      = $assignment->user;
        $process   = $assignment->process;

        $titulo  = 'Nuevo elemento asignado';
        $mensaje = sprintf(
            'Se te ha asignado el elemento "%s" (%s). Fecha límite: %s',
            $element->descripcion ?? $element->nomenclatura ?? 'Sin descripción',
            $element->nomenclatura ?? 'N/A',
            $assignment->fecha_limite
                ? \Carbon\Carbon::parse($assignment->fecha_limite)->format('d/m/Y')
                : 'No definida'
        );

        $enlace = "/Elements/{$element->elemento_id}";

        try {
            NotificationService::create([
                'usuario_id'   => $user->usuario_id,
                'tipo_evento'  => Notification::TIPO_ASIGNACION_ELEMENTO,
                'titulo'       => $titulo,
                'mensaje'      => $mensaje,
                'relacionado'  => $element,
                'enlace'       => $enlace,
                'metadatos'    => [
                    'elemento_id'            => $element->elemento_id,
                    'elemento_asignacion_id' => $assignment->elemento_asignacion_id,
                    'proceso_id'             => $process->proceso_id,
                    'asignado_por'           => $assignment->asignado_por,
                    'fecha_limite'           => $assignment->fecha_limite,
                ],
            ]);

            Log::info('Notificación de asignación de elemento creada', [
                'usuario_id'  => $user->usuario_id,
                'elemento_id' => $element->elemento_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creando notificación de asignación de elemento', [
                'error'       => $e->getMessage(),
                'usuario_id'  => $user->usuario_id,
                'elemento_id' => $element->elemento_id,
            ]);
        }
    }
}
