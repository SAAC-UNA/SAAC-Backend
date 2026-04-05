<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Notificación Interna del Sistema
 * 
 * HU-018: Notificaciones automáticas
 * 
 * Gestiona las notificaciones internas mostradas en la interfaz del sistema.
 * Las notificaciones por email se manejan con Laravel Notifications estándar.
 * 
 * Relaciones:
 * - belongsTo: User (destinatario)
 * - morphTo: Relacionado (entidad que generó la notificación)
 */
class Notification extends Model
{
    use HasFactory;

    protected $table = 'NOTIFICACION';
    protected $primaryKey = 'notificacion_id';

    /**
     * Tipos de eventos que pueden generar notificaciones
     */
    public const TIPO_ASIGNACION_EVIDENCIA = 'asignacion_evidencia';
    public const TIPO_ASIGNACION_ELEMENTO  = 'asignacion_elemento';
    public const TIPO_CARGA_ARCHIVO = 'carga_archivo';
    public const TIPO_VENCIMIENTO_PLAZO = 'vencimiento_plazo';
    public const TIPO_DEVOLUCION_OBSERVACION = 'devolucion_observacion';
    public const TIPO_APROBACION_CRITERIO = 'aprobacion_criterio';
    public const TIPO_RECHAZO_CRITERIO    = 'rechazo_criterio';
    public const TIPO_APROBACION_EVIDENCIA = 'aprobacion_evidencia';
    public const TIPO_RECHAZO_EVIDENCIA = 'rechazo_evidencia';
    public const TIPO_SOLICITUD_AMPLIACION = 'solicitud_ampliacion';
    public const TIPO_RESPUESTA_AMPLIACION = 'respuesta_ampliacion';
    public const TIPO_COMENTARIO_NUEVO = 'comentario_nuevo';
    public const TIPO_ACTUALIZACION_SISTEMA = 'actualizacion_sistema';

    /**
     * Canales de notificación
     */
    public const CANAL_INTERNO = 'interno';
    public const CANAL_EMAIL = 'email';
    public const CANAL_AMBOS = 'ambos';

    /**
     * Estados de entrega de email
     */
    public const EMAIL_PENDIENTE = 'pendiente';
    public const EMAIL_ENVIADO = 'enviado';
    public const EMAIL_FALLIDO = 'fallido';
    public const EMAIL_NO_APLICA = 'no_aplica';

    protected $fillable = [
        'usuario_id',
        'tipo_evento',
        'canal',
        'titulo',
        'mensaje',
        'leida',
        'fecha_lectura',
        'relacionado_type',
        'relacionado_id',
        'enlace',
        'estado_email',
        'detalle_error',
        'metadatos',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'fecha_lectura' => 'datetime',
        'metadatos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'leida' => false,
        'canal' => self::CANAL_INTERNO,
        'estado_email' => self::EMAIL_NO_APLICA,
    ];

    /**
     * Relación: Usuario destinatario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación polimórfica: Entidad relacionada
     * 
     * Puede ser: Evidence, Criterion, ExtensionRequest, Comment, etc.
     */
    public function relacionado()
    {
        return $this->morphTo(__FUNCTION__, 'relacionado_type', 'relacionado_id');
    }

    /**
     * Scope: Notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->where('leida', false);
    }

    /**
     * Scope: Notificaciones leídas
     */
    public function scopeRead($query)
    {
        return $query->where('leida', true);
    }

    /**
     * Scope: Notificaciones por tipo
     */
    public function scopeOfType($query, string $tipo)
    {
        return $query->where('tipo_evento', $tipo);
    }

    /**
     * Scope: Notificaciones de un usuario
     */
    public function scopeForUser($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope: Notificaciones recientes (últimos N días)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead(): void
    {
        if (!$this->leida) {
            $this->update([
                'leida' => true,
                'fecha_lectura' => now(),
            ]);
        }
    }

    /**
     * Marcar notificación como no leída
     */
    public function markAsUnread(): void
    {
        if ($this->leida) {
            $this->update([
                'leida' => false,
                'fecha_lectura' => null,
            ]);
        }
    }

    /**
     * Verificar si la notificación es crítica
     * 
     * Notificaciones críticas requieren email
     */
    public function isCritical(): bool
    {
        return in_array($this->tipo_evento, [
            self::TIPO_ASIGNACION_EVIDENCIA,
            self::TIPO_ASIGNACION_ELEMENTO,
            self::TIPO_VENCIMIENTO_PLAZO,
            self::TIPO_DEVOLUCION_OBSERVACION,
            self::TIPO_SOLICITUD_AMPLIACION,
        ]);
    }

    /**
     * Obtener icono según tipo de evento
     */
    public function getIcon(): string
    {
        return match($this->tipo_evento) {
            self::TIPO_ASIGNACION_EVIDENCIA => 'assignment',
            self::TIPO_ASIGNACION_ELEMENTO  => 'assignment',
            self::TIPO_CARGA_ARCHIVO => 'upload',
            self::TIPO_VENCIMIENTO_PLAZO => 'alarm',
            self::TIPO_DEVOLUCION_OBSERVACION => 'undo',
            self::TIPO_APROBACION_CRITERIO, self::TIPO_APROBACION_EVIDENCIA => 'check_circle',
            self::TIPO_RECHAZO_EVIDENCIA => 'cancel',
            self::TIPO_SOLICITUD_AMPLIACION => 'schedule',
            self::TIPO_RESPUESTA_AMPLIACION => 'reply',
            self::TIPO_COMENTARIO_NUEVO => 'comment',
            default => 'notifications',
        };
    }

    /**
     * Obtener color según tipo de evento
     */
    public function getColor(): string
    {
        return match($this->tipo_evento) {
            self::TIPO_ASIGNACION_EVIDENCIA => 'blue',
            self::TIPO_ASIGNACION_ELEMENTO  => 'blue',
            self::TIPO_CARGA_ARCHIVO => 'green',
            self::TIPO_VENCIMIENTO_PLAZO => 'red',
            self::TIPO_DEVOLUCION_OBSERVACION => 'orange',
            self::TIPO_APROBACION_CRITERIO, self::TIPO_APROBACION_EVIDENCIA => 'green',
            self::TIPO_RECHAZO_EVIDENCIA => 'red',
            self::TIPO_SOLICITUD_AMPLIACION => 'purple',
            self::TIPO_RESPUESTA_AMPLIACION => 'blue',
            self::TIPO_COMENTARIO_NUEVO => 'teal',
            default => 'gray',
        };
    }
}
