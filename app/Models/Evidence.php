<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    /** @use HasFactory<\Database\Factories\EvidenceFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'EVIDENCIA';

    // Clave primaria
    protected $primaryKey = 'evidencia_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['criterio_id', 'estado_evidencia_id', 'descripcion', 'nomenclatura', 'activo'];

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
     * Relación: Una evidencia pertenece a un estado de evidencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evidenceState()
    {
        return $this->belongsTo(EvidenceState::class, 'estado_evidencia_id', 'estado_evidencia_id');
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
        return $this->hasMany(EvidenceAssignment::class, 'evidencia_id', 'evidencia_id')->where('activo', true);
    }
}
