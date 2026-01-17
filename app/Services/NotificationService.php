<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

/**
 * Servicio de Notificaciones del Sistema
 * 
 * HU-018: Notificaciones automáticas
 * 
 * Responsabilidades:
 * - Crear notificaciones internas en BD
 * - Enviar notificaciones por email según criticidad
 * - Registrar en bitácora todas las notificaciones
 * - Validar y confirmar entrega
 * - Evitar duplicados
 * 
 * Uso:
 * ```php
 * NotificationService::create([
 *     'usuario_id' => 1,
 *     'tipo_evento' => 'asignacion_evidencia',
 *     'titulo' => 'Nueva evidencia asignada',
 *     'mensaje' => 'Se te ha asignado la evidencia X',
 *     'relacionado' => $evidencia,
 *     'enlace' => '/evidencias/123'
 * ]);
 * ```
 */
use App\Mail\TestNotificationMail;

class NotificationService
{
    /**
     * Crear una notificación
     * 
     * @param array $data Datos de la notificación
     *   - usuario_id: ID del usuario destinatario (requerido)
     *   - tipo_evento: Tipo de evento (requerido)
     *   - titulo: Título breve (requerido)
     *   - mensaje: Mensaje completo (requerido)
     *   - relacionado: Modelo Eloquent relacionado (opcional)
     *   - enlace: URL/ruta del sistema (opcional)
     *   - metadatos: Array con datos adicionales (opcional)
     *   - forzar_email: Enviar email aunque no sea crítico (opcional, default: false)
     * 
     * @return Notification
     */
    public static function create(array $data): Notification
    {
        // Validar datos requeridos
        self::validateData($data);

        // Verificar duplicados (misma notificación en últimos 5 minutos)
        if (self::isDuplicate($data)) {
            Log::warning('Notificación duplicada detectada y evitada', $data);
            // Retornar la notificación existente
            return Notification::where('usuario_id', $data['usuario_id'])
                ->where('tipo_evento', $data['tipo_evento'])
                ->where('created_at', '>=', now()->subMinutes(5))
                ->latest()
                ->first();
        }

        // Determinar canal según criticidad
        $canal = self::determinarCanal($data['tipo_evento'], $data['forzar_email'] ?? false);

        // Crear notificación interna
        $notificacion = new Notification([
            'usuario_id' => $data['usuario_id'],
            'tipo_evento' => $data['tipo_evento'],
            'canal' => $canal,
            'titulo' => $data['titulo'],
            'mensaje' => $data['mensaje'],
            'enlace' => $data['enlace'] ?? null,
            'metadatos' => $data['metadatos'] ?? null,
        ]);

        // Asociar entidad relacionada si existe
        if (isset($data['relacionado']) && $data['relacionado'] instanceof Model) {
            $notificacion->relacionado()->associate($data['relacionado']);
        }

        $notificacion->save();

        // Enviar por email si es necesario
        if (in_array($canal, [Notification::CANAL_EMAIL, Notification::CANAL_AMBOS])) {
            self::sendEmail($notificacion);
        }

        // Registrar en bitácora
        AuditLogService::log(
            'notificacion_creada',
            "Notificación creada: {$notificacion->titulo} para usuario {$notificacion->usuario_id}",
            'Notificación',
            $notificacion->notificacion_id
        );

        return $notificacion;
    }

    /**
     * Crear múltiples notificaciones (para múltiples usuarios)
     * 
     * @param array $usuariosIds Array de IDs de usuarios
     * @param array $data Datos de la notificación (sin usuario_id)
     * @return array Array de notificaciones creadas
     */
    public static function createMany(array $usuariosIds, array $data): array
    {
        $notificaciones = [];

        foreach ($usuariosIds as $usuarioId) {
            $dataCopia = $data;
            $dataCopia['usuario_id'] = $usuarioId;
            $notificaciones[] = self::create($dataCopia);
        }

        return $notificaciones;
    }

    /**
     * Marcar notificación como leída
     */
    public static function markAsRead(int $notificacionId): bool
    {
        $notificacion = Notification::find($notificacionId);
        
        if (!$notificacion) {
            return false;
        }

        $notificacion->markAsRead();
        return true;
    }

    /**
     * Marcar todas las notificaciones de un usuario como leídas
     */
    public static function markAllAsRead(int $usuarioId): int
    {
        return Notification::where('usuario_id', $usuarioId)
            ->where('leida', false)
            ->update([
                'leida' => true,
                'fecha_lectura' => now(),
            ]);
    }

    /**
     * Obtener notificaciones de un usuario
     */
    public static function getForUser(int $usuarioId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Notification::where('usuario_id', $usuarioId)
            ->with('relacionado')
            ->orderBy('created_at', 'desc');

        // Filtros opcionales
        if (isset($filters['leida'])) {
            $query->where('leida', $filters['leida']);
        }

        if (isset($filters['tipo_evento'])) {
            $query->where('tipo_evento', $filters['tipo_evento']);
        }

        if (isset($filters['fecha_desde'])) {
            $query->where('created_at', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta'])) {
            $query->where('created_at', '<=', $filters['fecha_hasta']);
        }

        return $query->get();
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public static function getUnreadCount(int $usuarioId): int
    {
        return Notification::where('usuario_id', $usuarioId)
            ->where('leida', false)
            ->count();
    }

    /**
     * Eliminar notificaciones antiguas (limpieza automática)
     * 
     * @param int $dias Días de antigüedad (default: 90 días)
     */
    public static function cleanOldNotifications(int $dias = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($dias))
            ->where('leida', true) // Solo eliminar las leídas
            ->delete();
    }

    // ===== MÉTODOS PRIVADOS =====

    /**
     * Validar datos requeridos
     */
    private static function validateData(array $data): void
    {
        $required = ['usuario_id', 'tipo_evento', 'titulo', 'mensaje'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("El campo {$field} es requerido para crear una notificación");
            }
        }

        // Validar que el usuario existe
        if (!User::find($data['usuario_id'])) {
            throw new \InvalidArgumentException("El usuario {$data['usuario_id']} no existe");
        }
    }

    /**
     * Verificar si es una notificación duplicada
     */
    private static function isDuplicate(array $data): bool
    {
        return Notification::where('usuario_id', $data['usuario_id'])
            ->where('tipo_evento', $data['tipo_evento'])
            ->where('titulo', $data['titulo'])
            ->where('created_at', '>=', now()->subMinutes(5)) // Últimos 5 minutos
            ->exists();
    }

    /**
     * Determinar canal de notificación según criticidad
     */
    private static function determinarCanal(string $tipoEvento, bool $forzarEmail = false): string
    {
        // Eventos críticos siempre van por ambos canales
        $eventosCriticos = [
            Notification::TIPO_ASIGNACION_EVIDENCIA,
            Notification::TIPO_VENCIMIENTO_PLAZO,
            Notification::TIPO_DEVOLUCION_OBSERVACION,
            Notification::TIPO_SOLICITUD_AMPLIACION,
        ];

        if (in_array($tipoEvento, $eventosCriticos) || $forzarEmail) {
            return Notification::CANAL_AMBOS;
        }

        // El resto solo notificación interna
        return Notification::CANAL_INTERNO;
    }

    /**
     * Enviar notificación por email
     */
    private static function sendEmail(Notification $notificacion): void
    {
        try {
            $user = $notificacion->user;

            // Verificar que el usuario tenga email
            if (!$user || !$user->email) {
                Log::warning('Usuario sin email, no se puede enviar notificación por correo', [
                    'notificacion_id' => $notificacion->notificacion_id,
                    'usuario_id' => $notificacion->usuario_id,
                ]);
                $notificacion->update(['estado_email' => Notification::EMAIL_NO_APLICA]);
                return;
            }

            // Marcar como pendiente
            $notificacion->update(['estado_email' => Notification::EMAIL_PENDIENTE]);

            // Enviar email con plantilla profesional
            Mail::to($user->email)->send(new TestNotificationMail(
                userName: $user->nombre,
                notificationTitle: $notificacion->titulo,
                notificationMessage: $notificacion->mensaje,
                actionUrl: $notificacion->enlace ? config('app.url') . $notificacion->enlace : null,
                actionText: 'Ver en el sistema'
            ));

            // Marcar como enviado
            $notificacion->update(['estado_email' => Notification::EMAIL_ENVIADO]);

            Log::info('Email de notificación enviado', [
                'notificacion_id' => $notificacion->notificacion_id,
                'email' => $user->email,
            ]);

        } catch (\Exception $e) {
            // Marcar como fallido
            $notificacion->update([
                'estado_email' => Notification::EMAIL_FALLIDO,
                'detalle_error' => $e->getMessage(),
            ]);

            Log::error('Error enviando email de notificación', [
                'notificacion_id' => $notificacion->notificacion_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
