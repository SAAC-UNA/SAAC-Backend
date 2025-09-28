<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImprovementCommitment extends Model
{
    /** @use HasFactory<\Database\Factories\ImprovementCommitmentFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'COMPROMISO_MEJORA';

    // Clave primaria
    protected $primaryKey = 'compromiso_mejora_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['proceso_id', 'fecha_inicio', 'fecha_fin'];

    /**
     * Relación: Un compromiso de mejora pertenece a un proceso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }
}
