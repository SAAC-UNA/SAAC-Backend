<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    /** @use HasFactory<\Database\Factories\DimensionFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'DIMENSION';

    // Clave primaria
    protected $primaryKey = 'dimension_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['comentario_id', 'nombre', 'nomenclatura'];

    /**
     * Relación: Una dimensión tiene muchos componentes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function components()
    {
        return $this->hasMany(Component::class, 'dimension_id', 'dimension_id');
    }
}
