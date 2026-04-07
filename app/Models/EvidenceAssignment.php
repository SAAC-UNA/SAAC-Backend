<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\File;

class EvidenceAssignment extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_EN_PROGRESO = 'En Progreso';
    public const ESTADO_COMPLETADO = 'Completado';
    public const ESTADO_VENCIDO = 'Vencido';

    // Nombre de la tabla en la base de datos
    protected $table = 'EVIDENCIA_ASIGNACION';

    // Clave primaria
    protected $primaryKey = 'evidencia_asignacion_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'proceso_id',
        'evidencia_id',
        'usuario_id',
        'estado',
        'fecha_asignacion',
        'fecha_limite',
        'comentario'
    ];

    // Cast de tipos
    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_limite' => 'datetime'
    ];

    /**
     * Convierte estado API (snake_case) a estado persistido en BD (Title Case).
     */
    public static function dbStatusFromApi(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return match (strtolower(trim($status))) {
            'pendiente' => self::ESTADO_PENDIENTE,
            'en_progreso' => self::ESTADO_EN_PROGRESO,
            'completado' => self::ESTADO_COMPLETADO,
            'vencido' => self::ESTADO_VENCIDO,
            default => $status,
        };
    }

    /**
     * Convierte estado persistido en BD (Title Case) a estado API (snake_case).
     */
    public static function apiStatusFromDb(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return strtolower(str_replace(' ', '_', trim($status)));
    }

    /**
     * Relación: Una asignación pertenece a un proceso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     * Alias para la relación process (para compatibilidad)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function proceso()
    {
        return $this->process();
    }

    /**
     * Relación: Una asignación pertenece a una evidencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidencia_id', 'evidencia_id');
    }

    /**
     * Relación: Una asignación pertenece a un usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación: Una asignación puede tener varias solicitudes de ampliación.
     * HU-016
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function extensionRequests()
    {
        return $this->hasMany(ExtensionRequest::class, 'evidencia_asignacion_id', 'evidencia_asignacion_id');
    }

    /**
     * Relación filtrada: solicitudes de ampliación en estado pendiente.
     * Usada con withExists() para evitar N+1 en EvidenceAssignmentResource.
     */
    public function pendingExtensionRequests()
    {
        return $this->extensionRequests()->where('estado', ExtensionRequest::ESTADO_PENDIENTE);
    }

    /**
     * Relación: archivos/enlaces subidos por el mismo usuario y proceso de la asignación.
     * Se usa con withExists() para validar si puede marcarse como completada.
     */
    public function filesByAssignee()
    {
        return $this->hasMany(File::class, 'evidencia_id', 'evidencia_id')
            ->whereColumn('ARCHIVO.usuario_id', 'EVIDENCIA_ASIGNACION.usuario_id')
            ->whereColumn('ARCHIVO.proceso_id', 'EVIDENCIA_ASIGNACION.proceso_id');
    }

    /**
     * Aprobaciones rechazadas del mismo usuario/proceso para esta evidencia.
     * Se usa con withExists() para marcar asignaciones devueltas para corrección.
     */
    public function rejectedApprovalsByAssignee()
    {
        return $this->hasMany(EvidenceApproval::class, 'evidencia_id', 'evidencia_id')
            ->whereColumn('APROBACION_EVIDENCIA.usuario_id', 'EVIDENCIA_ASIGNACION.usuario_id')
            ->whereColumn('APROBACION_EVIDENCIA.proceso_id', 'EVIDENCIA_ASIGNACION.proceso_id')
            ->where('estado', 'rechazado');
    }
}
