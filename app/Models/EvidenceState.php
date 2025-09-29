<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenceState extends Model
{
    /** @use HasFactory<\Database\Factories\EvidenceStateFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'ESTADO_EVIDENCIA';

    // Clave primaria
    protected $primaryKey = 'estado_evidencia_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['nombre'];

    // Timestamps automáticos
    public $timestamps = true;

    /**
     * Relación: Un estado de evidencia puede estar en muchas evidencias.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'estado_evidencia_id', 'estado_evidencia_id');
    }
}
