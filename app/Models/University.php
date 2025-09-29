<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'UNIVERSIDAD';

    // Clave primaria
    protected $primaryKey = 'universidad_id';

    // Indica si la clave primaria es autoincremental
    public $incrementing = true;

    // Tipo de la clave primaria
    protected $keyType = 'int';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['nombre'];

    // --- Relaciones ---

    /**
     * Relación: Una universidad tiene muchas facultades.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function faculties()
    {
        return $this->hasMany(Faculty::class, 'universidad_id', 'universidad_id');
    }
}
