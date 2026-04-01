<?php

namespace App\Models;

use App\Observers\ElementAssignmentObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementAssignment extends Model
{
    use HasFactory;

    protected $table = 'ELEMENTO_ASIGNACION';

    protected $primaryKey = 'elemento_asignacion_id';

    protected $fillable = [
        'elemento_id',
        'usuario_id',
        'proceso_id',
        'asignado_por',
        'estado',
        'fecha_limite',
        'comentario',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
    ];

    protected static function booted(): void
    {
        static::observe(ElementAssignmentObserver::class);
    }

    // ===== ESTADO CONSTANTS =====
    const ESTADO_PENDIENTE   = 'Pendiente';
    const ESTADO_EN_PROGRESO = 'En Progreso';
    const ESTADO_COMPLETADO  = 'Completado';
    const ESTADO_VENCIDO     = 'Vencido';
    const ESTADO_OBSERVADA   = 'Observada';  // retroalimentación: requiere corrección
    const ESTADO_VALIDADA    = 'Validada';   // retroalimentación: aprobada

    // ===== RELATIONS =====

    /**
     * An assignment belongs to a flexible structure element.
     */
    public function element()
    {
        return $this->belongsTo(StructureElement::class, 'elemento_id', 'elemento_id');
    }

    /**
     * An assignment belongs to a user (the assignee).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * An assignment belongs to a process.
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     * The user who created the assignment.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'asignado_por', 'usuario_id');
    }

    /**
     * Comentarios de retroalimentación sobre esta asignación (polimórfico).
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable', 'commentable_type', 'commentable_id');
    }

    /**
     * Solicitudes de ampliación de plazo para esta asignación (HU-016 flexible).
     */
    public function extensionRequests()
    {
        return $this->hasMany(ExtensionRequest::class, 'elemento_asignacion_id', 'elemento_asignacion_id');
    }
}
