<?php

namespace App\Listeners;

use App\Events\CriterionRejected;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyCriterionRejected implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CriterionRejected $event): void
    {
        try {
            $approval = $event->approval;
            $approval->load(['criterion', 'process']);
            
            $criterion = $approval->criterion;
            $proceso = $approval->process;

            // Obtener usuarios asignados al criterio
            $usuariosAsignados = \App\Models\EvidenceAssignment::whereHas('evidence', function($query) use ($criterion) {
                $query->where('criterio_id', $criterion->criterio_id);
            })->with('user')->get()->pluck('user')->unique('usuario_id');

            foreach ($usuariosAsignados as $usuario) {
                NotificationService::create([
                    'usuario_id' => $usuario->usuario_id,
                    'tipo_evento' => Notification::TIPO_DEVOLUCION_OBSERVACION,
                    'titulo' => 'Criterio Devuelto con Observaciones',
                    'mensaje' => "El criterio {$criterion->nomenclatura} ha sido RECHAZADO y requiere corrección." .
                                 "\n\nObservaciones:\n{$approval->comentario}" .
                                 "\n\nPor favor, revisa las observaciones y realiza las correcciones necesarias.",
                    'relacionado' => $approval,
                    'enlace' => "/criterios/{$criterion->criterio_id}",
                    'metadatos' => [
                        'criterio_id' => $criterion->criterio_id,
                        'nomenclatura' => $criterion->nomenclatura,
                        'aprobacion_id' => $approval->aprobacion_criterio_id,
                        'observaciones' => $approval->comentario,
                    ]
                ]);
            }

            Log::info('Notificaciones de criterio rechazado enviadas', [
                'criterio_id' => $criterion->criterio_id,
                'usuarios_notificados' => $usuariosAsignados->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar criterio rechazado', [
                'error' => $e->getMessage(),
                'aprobacion_id' => $event->approval->aprobacion_criterio_id ?? null
            ]);
        }
    }
}
