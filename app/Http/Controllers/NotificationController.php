<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de Notificaciones
 * 
 * HU-018: Notificaciones automáticas
 * 
 * Endpoints:
 * - GET /api/notificaciones - Listar notificaciones del usuario autenticado
 * - GET /api/notificaciones/no-leidas/contador - Contador de no leídas
 * - POST /api/notificaciones/{id}/marcar-leida - Marcar como leída
 * - POST /api/notificaciones/marcar-todas-leidas - Marcar todas como leídas
 * - DELETE /api/notificaciones/{id} - Eliminar notificación
 */
class NotificationController extends Controller
{
    /**
     * Listar notificaciones del usuario autenticado
     * 
     * GET /api/notificaciones?leida=false&tipo_evento=asignacion_evidencia&fecha_desde=2024-01-01
     * 
     * Filtros opcionales:
     * - leida: boolean (true/false)
     * - tipo_evento: string
     * - fecha_desde: date
     * - fecha_hasta: date
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $filters = $request->only(['leida', 'tipo_evento', 'fecha_desde', 'fecha_hasta']);
        
        // Convertir 'leida' a boolean si viene como string
        if (isset($filters['leida'])) {
            $filters['leida'] = filter_var($filters['leida'], FILTER_VALIDATE_BOOLEAN);
        }

        $notificaciones = NotificationService::getForUser($user->usuario_id, $filters);

        return response()->json([
            'message' => 'Notificaciones obtenidas exitosamente',
            'data' => $notificaciones->map(function ($notificacion) {
                return [
                    'notificacion_id' => $notificacion->notificacion_id,
                    'tipo_evento' => $notificacion->tipo_evento,
                    'canal' => $notificacion->canal,
                    'titulo' => $notificacion->titulo,
                    'mensaje' => $notificacion->mensaje,
                    'leida' => $notificacion->leida,
                    'fecha_lectura' => $notificacion->fecha_lectura,
                    'enlace' => $notificacion->enlace,
                    'icono' => $notificacion->getIcon(),
                    'color' => $notificacion->getColor(),
                    'es_critica' => $notificacion->isCritical(),
                    'metadatos' => $notificacion->metadatos,
                    'created_at' => $notificacion->created_at,
                    'relacionado' => $notificacion->relacionado ? [
                        'tipo' => class_basename($notificacion->relacionado_type),
                        'id' => $notificacion->relacionado_id,
                    ] : null,
                ];
            }),
            'total' => $notificaciones->count(),
        ], 200);
    }

    /**
     * Obtener contador de notificaciones no leídas
     * 
     * GET /api/notificaciones/no-leidas/contador
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = auth()->user();
        $count = NotificationService::getUnreadCount($user->usuario_id);

        return response()->json([
            'message' => 'Contador obtenido exitosamente',
            'data' => [
                'contador_no_leidas' => $count,
            ],
        ], 200);
    }

    /**
     * Marcar notificación como leída
     * 
     * POST /api/notificaciones/{id}/marcar-leida
     */
    public function markAsRead(int $id): JsonResponse
    {
        $user = auth()->user();

        // Verificar que la notificación pertenece al usuario
        $notificacion = \App\Models\Notification::where('notificacion_id', $id)
            ->where('usuario_id', $user->usuario_id)
            ->first();

        if (!$notificacion) {
            return response()->json([
                'message' => 'Notificación no encontrada o no autorizada',
            ], 404);
        }

        $notificacion->markAsRead();

        AuditLogService::log(
            'notificacion_leida',
            "Notificación {$id} marcada como leída",
            'Notificación',
            $id
        );

        return response()->json([
            'message' => 'Notificación marcada como leída',
            'data' => $notificacion,
        ], 200);
    }

    /**
     * Marcar todas las notificaciones como leídas
     * 
     * POST /api/notificaciones/marcar-todas-leidas
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = auth()->user();
        $count = NotificationService::markAllAsRead($user->usuario_id);

        AuditLogService::log(
            'notificaciones_leidas_masivo',
            "Se marcaron {$count} notificaciones como leídas",
            'Notificación'
        );

        return response()->json([
            'message' => "Se marcaron {$count} notificaciones como leídas",
            'data' => [
                'cantidad_actualizada' => $count,
            ],
        ], 200);
    }

    /**
     * Eliminar una notificación
     * 
     * DELETE /api/notificaciones/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();

        // Verificar que la notificación pertenece al usuario
        $notificacion = \App\Models\Notification::where('notificacion_id', $id)
            ->where('usuario_id', $user->usuario_id)
            ->first();

        if (!$notificacion) {
            return response()->json([
                'message' => 'Notificación no encontrada o no autorizada',
            ], 404);
        }

        $titulo = $notificacion->titulo;
        $notificacion->delete();

        AuditLogService::log(
            'notificacion_eliminada',
            "Notificación eliminada: {$titulo}",
            'Notificación',
            $id
        );

        return response()->json([
            'message' => 'Notificación eliminada exitosamente',
        ], 200);
    }
}
