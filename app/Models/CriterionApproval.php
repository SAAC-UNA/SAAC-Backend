<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CriterionApproval extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'APROBACION_CRITERIO';

    // Clave primaria
    protected $primaryKey = 'aprobacion_criterio_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'criterio_id',
        'proceso_id',
        'usuario_id',
        'estado',
        'comentario'
    ];

    /**
     * Relación: Una aprobación pertenece a un criterio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function criterion()
    {
        return $this->belongsTo(Criterion::class, 'criterio_id', 'criterio_id');
    }

    /**
     * Relación: Una aprobación pertenece a un proceso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     * Relación: Una aprobación pertenece a un usuario (quien aprobó/rechazó).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación: Una aprobación de criterio tiene muchas aprobaciones de evidencias.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evidenceApprovals()
    {
        return $this->hasMany(EvidenceApproval::class, 'criterio_aprobacion_id', 'aprobacion_criterio_id');
    }
}
