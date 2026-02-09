<?php

namespace App\Listeners;

use App\Events\CriterionApproved;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyCriterionApproved implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CriterionApproved $event): void
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
                    'tipo_evento' => Notification::TIPO_APROBACION_CRITERIO,
                    'titulo' => 'Criterio Aprobado',
                    'mensaje' => "El criterio {$criterion->nomenclatura} ha sido APROBADO." .
                                 "\n\nFelicitaciones por completar todas las evidencias requeridas." .
                                 ($approval->comentario ? "\n\nComentarios: {$approval->comentario}" : ""),
                    'relacionado' => $approval,
                    'enlace' => "/criterios/{$criterion->criterio_id}",
                    'metadatos' => [
                        'criterio_id' => $criterion->criterio_id,
                        'nomenclatura' => $criterion->nomenclatura,
                        'aprobacion_id' => $approval->aprobacion_criterio_id,
                    ]
                ]);
            }

            Log::info('Notificaciones de criterio aprobado enviadas', [
                'criterio_id' => $criterion->criterio_id,
                'usuarios_notificados' => $usuariosAsignados->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar criterio aprobado', [
                'error' => $e->getMessage(),
                'aprobacion_id' => $event->approval->aprobacion_criterio_id ?? null
            ]);
        }
    }
}
