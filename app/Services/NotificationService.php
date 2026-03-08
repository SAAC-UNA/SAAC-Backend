<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
            // Retornar la notificacion existente
            $rows = DB::select('CALL SP_OBTENER_ULTIMA_NOTIFICACION(?, ?)', [
                $data['usuario_id'],
                $data['tipo_evento'],
            ]);
            return Notification::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
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

        // Crear notificacion via stored procedure
        $rows = DB::select('CALL SP_CREAR_NOTIFICACION(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $data['usuario_id'],
            $data['tipo_evento'],
            $canal,
            $data['titulo'],
            $data['mensaje'],
            $data['enlace'] ?? null,
            isset($data['metadatos']) ? json_encode($data['metadatos']) : null,
            $relacionadoType,
            $relacionadoId,
            $estadoEmail,
        ]);

        $notificacion = Notification::hydrate(array_map(fn($r) => (array) $r, $rows))->first();

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
        DB::statement('CALL SP_MARCAR_NOTIFICACION_LEIDA(?)', [$notificacionId]);
        return true;
    }

    /**
     * Marcar todas las notificaciones de un usuario como leidas
     */
    public static function markAllAsRead(int $usuarioId): int
    {
        $result = DB::select('CALL SP_MARCAR_TODAS_NOTIFICACIONES_LEIDAS(?)', [$usuarioId]);
        return $result[0]->affected ?? 0;
    }

    /**
     * Obtener notificaciones de un usuario
     */
    public static function getForUser(int $usuarioId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $leida      = isset($filters['leida']) ? (int) $filters['leida'] : null;
        $tipoEvento = $filters['tipo_evento'] ?? null;
        $fechaDesde = $filters['fecha_desde'] ?? null;
        $fechaHasta = $filters['fecha_hasta'] ?? null;

        $rows = DB::select('CALL SP_OBTENER_NOTIFICACIONES(?, ?, ?, ?, ?)', [
            $usuarioId,
            $leida,
            $tipoEvento,
            $fechaDesde,
            $fechaHasta,
        ]);

        return Notification::hydrate(array_map(fn($r) => (array) $r, $rows));
    }

    /**
     * Obtener contador de notificaciones no leidas
     */
    public static function getUnreadCount(int $usuarioId): int
    {
        $result = DB::select('CALL SP_CONTAR_NO_LEIDAS(?)', [$usuarioId]);
        return $result[0]->total ?? 0;
    }

    /**
     * Eliminar notificaciones antiguas (limpieza automatica)
     *
     * @param int $dias Dias de antiguedad (default: 90 dias)
     */
    public static function cleanOldNotifications(int $dias = 90): int
    {
        $result = DB::select('CALL SP_LIMPIAR_NOTIFICACIONES_ANTIGUAS(?)', [$dias]);
        return $result[0]->deleted ?? 0;
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

        // Validar que el usuario existe via SP
        $rows = DB::select('CALL SP_BUSCAR_USUARIO(?)', [$data['usuario_id']]);
        if (empty($rows)) {
            throw new \InvalidArgumentException("El usuario {$data['usuario_id']} no existe");
        }
    }

    /**
     * Verificar si es una notificacion duplicada
     */
    private static function isDuplicate(array $data): bool
    {
        $result = DB::select('CALL SP_VERIFICAR_NOTIFICACION_DUPLICADA(?, ?, ?)', [
            $data['usuario_id'],
            $data['tipo_evento'],
            $data['titulo'],
        ]);
        return ($result[0]->total ?? 0) > 0;
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
            $rows = DB::select('CALL SP_BUSCAR_USUARIO(?)', [$notificacion->usuario_id]);
            $user = !empty($rows) ? User::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;

            // Verificar que el usuario tenga email
            if (!$user || !$user->email) {
                Log::warning('Usuario sin email, no se puede enviar notificacion por correo', [
                    'notificacion_id' => $notificacion->notificacion_id,
                    'usuario_id'      => $notificacion->usuario_id,
                ]);
                DB::statement('CALL SP_ACTUALIZAR_ESTADO_EMAIL_NOTIFICACION(?, ?, ?)', [
                    $notificacion->notificacion_id,
                    Notification::EMAIL_NO_APLICA,
                    null,
                ]);
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
            DB::statement('CALL SP_ACTUALIZAR_ESTADO_EMAIL_NOTIFICACION(?, ?, ?)', [
                $notificacion->notificacion_id,
                Notification::EMAIL_ENVIADO,
                null,
            ]);

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
            DB::statement('CALL SP_ACTUALIZAR_ESTADO_EMAIL_NOTIFICACION(?, ?, ?)', [
                $notificacion->notificacion_id,
                Notification::EMAIL_FALLIDO,
                $e->getMessage(),
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