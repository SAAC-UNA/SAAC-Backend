<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'FACULTAD';

    // Clave primaria
    protected $primaryKey = 'facultad_id';

    // Indica si la clave primaria es autoincremental
    public $incrementing = true;

    // Tipo de la clave primaria
    protected $keyType = 'int';

    // Timestamps automáticos
    public $timestamps = true;

    // Campos que se pueden asignar masivamente
    protected $fillable = ['universidad_id', 'sede_id', 'nombre'];

    /**
     * Relación: Una facultad pertenece a una universidad.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function university()
    {
        return $this->belongsTo(University::class, 'universidad_id', 'universidad_id');
    }

    /**
     * Relación: Una facultad pertenece a un campus.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class, 'sede_id', 'sede_id');
    }

    /**
     * Relación: Una facultad tiene muchas carreras.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function careers()
    {
        return $this->hasMany(Career::class, 'facultad_id', 'facultad_id');
    }
}
