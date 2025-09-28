<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    /** @use HasFactory<\Database\Factories\ComponentFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'COMPONENTE';

    // Clave primaria
    protected $primaryKey = 'componente_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['dimension_id', 'comentario_id', 'nombre', 'nomenclatura'];

    /**
     * Relación: Un componente pertenece a una dimensión.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dimension()
    {
        return $this->belongsTo(Dimension::class, 'dimension_id', 'dimension_id');
    }

    /**
     * Relación: Un componente pertenece a un comentario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comentario_id', 'comentario_id');
    }
}
