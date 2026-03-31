<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evidence extends BaseCareer
{
    /** @use HasFactory<\Database\Factories\EvidenceFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'EVIDENCIA';

    // Clave primaria
    protected $primaryKey = 'evidencia_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['criterio_id', 'elemento_id', 'estado', 'descripcion', 'nomenclatura', 'activo'];

    /** Valores válidos del enum estado */
    public const ESTADOS = ['Pendiente', 'En Proceso', 'Completado', 'Vencido', 'Aprobado', 'Rechazado', 'Observada', 'Validada'];

    // --- Scopes ---

    /** Filtra solo evidencias activas */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /** Filtra por estado de evidencia */
    public function scopeByState($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /** Filtra por criterio */
    public function scopeByCriterion($query, int $criterioId)
    {
        return $query->where('criterio_id', $criterioId);
    }

    /**
     * Relación: Una evidencia pertenece a un criterio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function criterion()
    {
        return $this->belongsTo(Criterion::class, 'criterio_id', 'criterio_id');
    }

    /**
     * Relación: Una evidencia (flexible) pertenece a un elemento del árbol.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function elemento()
    {
        return $this->belongsTo(StructureElement::class, 'elemento_id', 'elemento_id');
    }

    /**
     * Relación: Una evidencia tiene muchas asignaciones.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignments()
    {
        return $this->hasMany(EvidenceAssignment::class, 'evidencia_id', 'evidencia_id');
    }

    /**
     * Relación: Una evidencia tiene muchas asignaciones activas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeAssignments()
    {
        return $this->hasMany(EvidenceAssignment::class, 'evidencia_id', 'evidencia_id')
            ->whereIn('estado', ['Pendiente', 'En Progreso']);
    }

    /**
     * Relación: Una evidencia tiene muchos archivos/enlaces.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files()
    {
        return $this->hasMany(File::class, 'evidencia_id', 'evidencia_id');
    }

    /**
     * Relación: El último archivo subido (tipo=archivo) de la evidencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function latestFile()
    {
        return $this->hasOne(File::class, 'evidencia_id', 'evidencia_id')
            ->where('tipo', 'archivo')
            ->latest('fecha_subida');
    }

    /*
     * Relación polimórfica: Una evidencia puede tener muchos comentarios (HU-013).
     * Los comentarios se guardan en COMENTARIO con commentable_type = Evidence::class.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable', 'commentable_type', 'commentable_id', 'evidencia_id');
    }

}
