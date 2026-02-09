<?php

namespace App\Listeners;

use App\Events\EvidenceAssignmentDeleted;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAssignmentDeleted implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(EvidenceAssignmentDeleted $event): void
    {
        try {
            $data = $event->assignmentData;

            // Notificar al usuario que tenía la asignación
            NotificationService::create([
                'usuario_id' => $data['usuario_id'],
                'tipo_evento' => Notification::TIPO_ACTUALIZACION_SISTEMA,
                'titulo' => 'Asignación de Evidencia Eliminada',
                'mensaje' => "Tu asignación para la evidencia \"{$data['evidencia_nombre']}\" ha sido eliminada." .
                             "\n\nSi esto es un error, contacta con el administrador de tu carrera.",
                'enlace' => null,
                'metadatos' => [
                    'asignacion_eliminada_id' => $data['asignacion_evidencia_id'],
                    'evidencia_nombre' => $data['evidencia_nombre'],
                    'fecha_eliminacion' => now()->toDateTimeString(),
                ],
                'forzar_email' => true // Crítico: debe saberlo
            ]);

            Log::info('Notificación de asignación eliminada enviada', [
                'asignacion_id' => $data['asignacion_evidencia_id'],
                'usuario_id' => $data['usuario_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar asignación eliminada', [
                'error' => $e->getMessage(),
                'data' => $event->assignmentData ?? null
            ]);
        }
    }
}
