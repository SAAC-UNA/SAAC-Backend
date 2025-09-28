<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'SEDE';

    // Clave primaria
    protected $primaryKey = 'sede_id';

    // Indica si la clave primaria es autoincremental
    public $incrementing = true;

    // Tipo de la clave primaria
    protected $keyType = 'int';

    // Timestamps automáticos
    public $timestamps = true;

    // Campos que se pueden asignar masivamente
    protected $fillable = ['universidad_id', 'nombre'];

    /**
     * Relación: Un campus pertenece a una universidad.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function university()
    {
        return $this->belongsTo(University::class, 'universidad_id', 'universidad_id');
    }

    /**
     * Relación: Un campus tiene muchas facultades.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function faculties()
    {
        return $this->hasMany(Faculty::class, 'sede_id', 'sede_id');
    }
}
