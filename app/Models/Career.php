<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    protected $fillable = ['nombre', 'activo', 'universidad_id'];

    /**
     * Relación: Una carrera pertenece a una universidad.
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'universidad_id', 'universidad_id');
    }

    /**
     * Relación: Una carrera tiene muchas entradas carrera-sede (pivot).
     */
    public function careerCampuses(): HasMany
    {
        return $this->hasMany(CareerCampus::class, 'carrera_id', 'carrera_id');
    }

}
