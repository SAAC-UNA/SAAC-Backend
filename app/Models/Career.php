<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'CARRERA';

    // Clave primaria
    protected $primaryKey = 'carrera_id';

    // Indica si la clave primaria es autoincremental
    public $incrementing = true;

    // Tipo de la clave primaria
    protected $keyType = 'int';

    // Timestamps automáticos
    public $timestamps = true;

    // Campos que se pueden asignar masivamente
    protected $fillable = ['facultad_id', 'nombre' , 'activo'];

    /**
     * Relación: Una carrera pertenece a una facultad.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'facultad_id', 'facultad_id');
    }
}
