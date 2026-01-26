<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends  Model
{
    /** @use HasFactory<\Database\Factories\ComponentFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'COMPONENTE';

    // Clave primaria
    protected $primaryKey = 'componente_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['dimension_id', 'nombre', 'nomenclatura', 'activo'];

    // --- Relaciones ---

    /**
     * Relación: Un componente tiene muchos criterios.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function criteria()
    {
        return $this->hasMany(Criterion::class, 'componente_id', 'componente_id');
    }

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
     * Relación polimórfica: Un componente puede tener muchos comentarios.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
