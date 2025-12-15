<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtensionRequest extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'SOLICITUD_AMPLIACION';

    // Clave primaria
    protected $primaryKey = 'solicitud_ampliacion_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'evidencia_asignacion_id',
        'usuario_id',
        'fecha_solicitud',
        'motivo',
        'fecha_sugerida',
        'estado',
        'fecha_resolucion',
        'usuario_resolutor_id',
        'justificacion'
    ];

    // Cast de tipos
    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_sugerida' => 'datetime',
        'fecha_resolucion' => 'datetime'
    ];

    // Constantes de estados
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADA = 'aprobada';
    const ESTADO_RECHAZADA = 'rechazada';

    /**
     * Relación: Una solicitud pertenece a una asignación de evidencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evidenceAssignment()
    {
        return $this->belongsTo(EvidenceAssignment::class, 'evidencia_asignacion_id', 'evidencia_asignacion_id');
    }

    /**
     * Relación: Una solicitud pertenece a un usuario (solicitante).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación: Una solicitud puede tener un usuario resolutor (quien aprueba/rechaza).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resolutor()
    {
        return $this->belongsTo(User::class, 'usuario_resolutor_id', 'usuario_id');
    }

    /**
     * Scope: Filtrar solicitudes pendientes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope: Filtrar solicitudes aprobadas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAprobadas($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }

    /**
     * Scope: Filtrar solicitudes rechazadas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRechazadas($query)
    {
        return $query->where('estado', self::ESTADO_RECHAZADA);
    }

    /**
     * Scope: Filtrar solicitudes de un usuario específico.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $usuarioId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }
}
