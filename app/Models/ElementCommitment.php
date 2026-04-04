<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo ElementCommitment — Compromiso de Mejora (modelo flexible).
 * Tabla: COMPROMISO_MEJORA_ELEMENTO
 *
 * Equivalente a ImprovementCommitment pero para el árbol ELEMENTO
 * en lugar de la jerarquía CRITERIO/EVIDENCIA del modelo tradicional.
 */
class ElementCommitment extends Model
{
    use HasFactory;

    protected $table = 'COMPROMISO_MEJORA_ELEMENTO';

    protected $primaryKey = 'compromiso_elemento_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'proceso_id',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    protected $appends = ['is_overdue'];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    /**
     * Pertenece a un proceso.
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     * Asignaciones de Elements vinculadas al compromiso (pivot con comentario).
     * Equivalente a assignedEvidences() del modelo tradicional.
     */
    public function assignedElements()
    {
        return $this->belongsToMany(
            ElementAssignment::class,
            'COMPROMISO_MEJORA_ELEMENTO_ASIGNACION',
            'compromiso_elemento_id',
            'elemento_asignacion_id'
        )->withPivot('comentario')->withTimestamps();
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /**
     * Verifica si el compromiso está vencido (fecha_fin pasada y no Completado).
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->estado !== 'Completado' && $this->fecha_fin < now();
    }
}
