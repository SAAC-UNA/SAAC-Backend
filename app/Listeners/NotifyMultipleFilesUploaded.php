<?php

namespace App\Listeners;

use App\Events\MultipleFilesUploaded;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Notificar Subida de Múltiples Archivos
 * 
 * Cuando un usuario sube 2+ archivos simultáneamente, se crea una
 * notificación resumen en lugar de N notificaciones individuales.
 * 
 * Canal: INTERNO (no crítico, optimización)
 */
class NotifyMultipleFilesUploaded implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MultipleFilesUploaded $event): void
    {
        try {
            // Cargar evidencia
            $evidence = Evidence::find($event->evidenciaId);
            
            if (!$evidence) {
                Log::warning('Evidencia no encontrada para notificación de múltiples archivos', [
                    'evidencia_id' => $event->evidenciaId,
                ]);
                return;
            }

            // Buscar usuarios asignados a esta evidencia
            $assignedUsers = EvidenceAssignment::where('evidencia_id', $event->evidenciaId)
                ->where('proceso_id', $event->procesoId)
                ->where('usuario_id', '!=', $event->usuarioId) // Excluir quien subió
                ->with('user')
                ->get();

            if ($assignedUsers->isEmpty()) {
                Log::info('No hay otros usuarios asignados para notificar sobre múltiples archivos');
                return;
            }

            // Preparar lista de archivos
            $filesList = collect($event->filesData)
                ->map(fn($file) => "• {$file['nombre_original']} ({$file['size_kb']} KB)")
                ->join("\n");

            // Crear notificación para cada usuario asignado
            foreach ($assignedUsers as $assignment) {
                NotificationService::create([
                    'usuario_id' => $assignment->usuario_id,
                    'tipo_evento' => Notification::TIPO_CARGA_ARCHIVO,
                    'titulo' => "📎 {$event->totalFiles} archivos subidos a {$evidence->nomenclatura}",
                    'mensaje' => "Se han subido {$event->totalFiles} archivos nuevos a la evidencia \"{$evidence->nombre}\":\n\n{$filesList}",
                    'relacionado' => $evidence,
                    'enlace' => "/evidencias/{$evidence->evidencia_id}",
                    'metadatos' => [
                        'evidencia_id' => $evidence->evidencia_id,
                        'total_archivos' => $event->totalFiles,
                        'archivos' => $event->filesData,
                        'subido_por' => $event->usuarioId,
                    ],
                ]);
            }

            Log::info('Notificaciones de múltiples archivos creadas', [
                'evidencia_id' => $event->evidenciaId,
                'total_archivos' => $event->totalFiles,
                'usuarios_notificados' => $assignedUsers->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando notificación de múltiples archivos', [
                'error' => $e->getMessage(),
                'evidencia_id' => $event->evidenciaId,
            ]);
        }
    }
}
