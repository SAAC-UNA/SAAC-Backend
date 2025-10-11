<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Standard extends  Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'ESTANDAR';

    // Clave primaria
    protected $primaryKey = 'estandar_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['criterio_id', 'descripcion', 'activo'];

    // Timestamps automáticos
    public $timestamps = true;
    /**
     * Relación: Un estándar pertenece a un criterio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function criterion()
    {
        return $this->belongsTo(Criterion::class, 'criterio_id', 'criterio_id');
    }
}
