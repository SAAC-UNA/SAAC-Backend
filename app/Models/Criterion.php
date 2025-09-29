<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'CRITERIO';

    // Clave primaria
    protected $primaryKey = 'criterio_id';

    // Indica si la clave primaria es autoincremental
    public $incrementing = true;

    // Tipo de la clave primaria
    protected $keyType = 'int';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'componente_id',
        'comentario_id',
        'descripcion',
        'nomenclatura',
        'activo'
    ];

    // --- Relaciones ---

    /**
     * Relación: Un criterio tiene muchas evidencias.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'criterio_id', 'criterio_id');
    }

    /**
     * Relación: Un criterio pertenece a un componente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function component()
    {
        return $this->belongsTo(Component::class, 'componente_id', 'componente_id');
    }

    /**
     * Relación: Un criterio pertenece a un comentario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comentario_id', 'comentario_id');
    }

    /**
     * Relación: Un criterio tiene muchos estándares.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function standards()
    {
        return $this->hasMany(Standard::class, 'criterio_id', 'criterio_id');
    }
}
