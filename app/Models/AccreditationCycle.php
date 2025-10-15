<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


// Hereda de BaseCareer para aplicar automáticamente filtros
class AccreditationCycle extends BaseCareer
{
    // Habilita la generación de instancias mediante la factory correspondiente.
    /** @use HasFactory<\Database\Factories\AccreditationCycleFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'CICLO_ACREDITACION';

    // Clave primaria
    protected $primaryKey = 'ciclo_acreditacion_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['carrera_sede_id', 'nombre'];

    /**
     * Relación: Un ciclo de acreditación pertenece a una sede de carrera.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function careerCampus()
    {
        // Relación con CareerCampus
        return $this->belongsTo(CareerCampus::class, 'carrera_sede_id');
    }

    /**
     * Relación: Un ciclo de acreditación tiene muchos procesos.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function processes()
    {
        // Relación con Process
        return $this->hasMany(Process::class, 'ciclo_acreditacion_id');
    }
}
