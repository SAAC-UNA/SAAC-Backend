<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Services\AuditLogService;
use App\Mail\TestNotificationMail;

/**
 * Servicio de Notificaciones del Sistema
 *
 * HU-018: Notificaciones automaticas
 *
 * Responsabilidades:
 * - Crear notificaciones internas en BD via stored procedures
 * - Enviar notificaciones por email segun criticidad
 * - Registrar en bitacora todas las notificaciones
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
class NotificationService
{
    /**
     * Crear una notificacion
     *
     * @param array $data Datos de la notificacion
     *   - usuario_id: ID del usuario destinatario (requerido)
     *   - tipo_evento: Tipo de evento (requerido)
     *   - titulo: Titulo breve (requerido)
     *   - mensaje: Mensaje completo (requerido)
     *   - relacionado: Modelo Eloquent relacionado (opcional)
     *   - enlace: URL/ruta del sistema (opcional)
     *   - metadatos: Array con datos adicionales (opcional)
     *   - forzar_email: Enviar email aunque no sea critico (opcional, default: false)
     *
     * @return Notification
     */
    public static function create(array $data): Notification
    {
        // Validar datos requeridos
        self::validateData($data);

        // Verificar duplicados (misma notificacion en ultimos 5 minutos)
        if (self::isDuplicate($data)) {
            Log::warning('Notificacion duplicada detectada y evitada', $data);
            return Notification::where('usuario_id', $data['usuario_id'])
                ->where('tipo_evento', $data['tipo_evento'])
                ->latest()
                ->firstOrFail();
        }

        // Determinar canal segun criticidad
        $canal = self::determinarCanal($data['tipo_evento'], $data['forzar_email'] ?? false);

        // Extraer tipo e ID de entidad relacionada si existe
        $relacionadoType = null;
        $relacionadoId   = null;
        if (isset($data['relacionado']) && $data['relacionado'] instanceof Model) {
            $relacionadoType = get_class($data['relacionado']);
            $relacionadoId   = $data['relacionado']->getKey();
        }

        // Estado email inicial
        $estadoEmail = in_array($canal, [Notification::CANAL_EMAIL, Notification::CANAL_AMBOS])
            ? Notification::EMAIL_PENDIENTE
            : Notification::EMAIL_NO_APLICA;

        // Crear notificacion via Eloquent
        $notificacion = Notification::create([
            'usuario_id'        => $data['usuario_id'],
            'tipo_evento'       => $data['tipo_evento'],
            'canal'             => $canal,
            'titulo'            => $data['titulo'],
            'mensaje'           => $data['mensaje'],
            'enlace'            => $data['enlace'] ?? null,
            'metadatos'         => $data['metadatos'] ?? null,
            'relacionado_type'  => $relacionadoType,
            'relacionado_id'    => $relacionadoId,
            'estado_email'      => $estadoEmail,
        ]);

        // Registrar exito en bitacora
        AuditLogService::log(
            'notificar',
            "Notificacion '{$notificacion->titulo}' creada para usuario #{$notificacion->usuario_id} (Tipo: {$notificacion->tipo_evento}, Canal: {$canal})",
            'Notificaciones'
        );

        // Enviar por email si es necesario
        if (in_array($canal, [Notification::CANAL_EMAIL, Notification::CANAL_AMBOS])) {
            self::sendEmail($notificacion);
        }

        return $notificacion;
    }

    /**
     * Crear multiples notificaciones (para multiples usuarios)
     *
     * @param array $usuariosIds Array de IDs de usuarios
     * @param array $data Datos de la notificacion (sin usuario_id)
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
     * Marcar notificacion como leida
     */
    public static function markAsRead(int $notificacionId): bool
    {
        Notification::where('notificacion_id', $notificacionId)
            ->update(['leida' => true, 'fecha_lectura' => now()]);
        return true;
    }

    /**
     * Marcar todas las notificaciones de un usuario como leidas
     */
    public static function markAllAsRead(int $usuarioId): int
    {
        return Notification::where('usuario_id', $usuarioId)
            ->where('leida', false)
            ->update(['leida' => true, 'fecha_lectura' => now()]);
    }

    /**
     * Obtener notificaciones de un usuario
     */
    public static function getForUser(int $usuarioId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $leida      = isset($filters['leida']) ? (bool) $filters['leida'] : null;
        $tipoEvento = $filters['tipo_evento'] ?? null;
        $fechaDesde = $filters['fecha_desde'] ?? null;
        $fechaHasta = $filters['fecha_hasta'] ?? null;

        return Notification::where('usuario_id', $usuarioId)
            ->when($leida !== null, fn($q) => $q->where('leida', $leida))
            ->when($tipoEvento, fn($q) => $q->where('tipo_evento', $tipoEvento))
            ->when($fechaDesde, fn($q) => $q->where('created_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn($q) => $q->where('created_at', '<=', $fechaHasta))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener contador de notificaciones no leidas
     */
    public static function getUnreadCount(int $usuarioId): int
    {
        return Notification::where('usuario_id', $usuarioId)
            ->where('leida', false)
            ->count();
    }

    /**
     * Eliminar notificaciones antiguas (limpieza automatica)
     *
     * @param int $dias Dias de antiguedad (default: 90 dias)
     */
    public static function cleanOldNotifications(int $dias = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($dias))->delete();
    }

    // ===== METODOS PRIVADOS =====

    /**
     * Validar datos requeridos
     */
    private static function validateData(array $data): void
    {
        $required = ['usuario_id', 'tipo_evento', 'titulo', 'mensaje'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("El campo {$field} es requerido para crear una notificacion");
            }
        }

        // Validar que el usuario existe
        if (!User::where('usuario_id', $data['usuario_id'])->exists()) {
            throw new \InvalidArgumentException("El usuario {$data['usuario_id']} no existe");
        }
    }

    /**
     * Verificar si es una notificacion duplicada
     */
    private static function isDuplicate(array $data): bool
    {
        return Notification::where('usuario_id', $data['usuario_id'])
            ->where('tipo_evento', $data['tipo_evento'])
            ->where('titulo', $data['titulo'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
    }

    /**
     * Determinar canal de notificacion segun criticidad
     */
    private static function determinarCanal(string $tipoEvento, bool $forzarEmail = false): string
    {
        // Eventos criticos siempre van por ambos canales
        $eventosCriticos = [
            Notification::TIPO_ASIGNACION_EVIDENCIA,
            Notification::TIPO_VENCIMIENTO_PLAZO,
            Notification::TIPO_DEVOLUCION_OBSERVACION,
            Notification::TIPO_SOLICITUD_AMPLIACION,
        ];

        if (in_array($tipoEvento, $eventosCriticos) || $forzarEmail) {
            return Notification::CANAL_AMBOS;
        }

        // El resto solo notificacion interna
        return Notification::CANAL_INTERNO;
    }

    /**
     * Enviar notificacion por email
     */
    private static function sendEmail(Notification $notificacion): void
    {
        try {
            $user = User::find($notificacion->usuario_id);

            // Verificar que el usuario tenga email
            if (!$user || !$user->email) {
                Log::warning('Usuario sin email, no se puede enviar notificacion por correo', [
                    'notificacion_id' => $notificacion->notificacion_id,
                    'usuario_id'      => $notificacion->usuario_id,
                ]);
                $notificacion->update(['estado_email' => Notification::EMAIL_NO_APLICA]);
                return;
            }

            // Enviar email con plantilla profesional
            Mail::to($user->email)->send(new TestNotificationMail(
                userName:            $user->nombre,
                notificationTitle:   $notificacion->titulo,
                notificationMessage: $notificacion->mensaje,
                actionUrl:           $notificacion->enlace ? config('app.url') . $notificacion->enlace : null,
                actionText:          'Ver en el sistema'
            ));

            // Marcar como enviado
            $notificacion->update(['estado_email' => Notification::EMAIL_ENVIADO]);

            Log::info('Email de notificacion enviado', [
                'notificacion_id' => $notificacion->notificacion_id,
                'email'           => $user->email,
            ]);

            // Registrar envio exitoso en bitacora
            AuditLogService::log(
                'notificar',
                "Email enviado: '{$notificacion->titulo}' a {$user->email}",
                'Notificaciones'
            );

        } catch (\Exception $e) {
            // Marcar como fallido
            $notificacion->update([
                'estado_email'  => Notification::EMAIL_FALLIDO,
                'detalle_error' => $e->getMessage(),
            ]);

            Log::error('Error enviando email de notificacion', [
                'notificacion_id' => $notificacion->notificacion_id,
                'error'           => $e->getMessage(),
            ]);

            // Registrar fallo en bitacora
            AuditLogService::log(
                'notificar_fallido',
                "Error enviando email '{$notificacion->titulo}': {$e->getMessage()}",
                'Notificaciones'
            );
        }
    }
}