<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccreditationCycle extends BaseCareer
{
    use HasFactory;

    protected $table = 'CICLO_ACREDITACION';
    protected $primaryKey = 'ciclo_acreditacion_id';

    protected $fillable = [
        'nombre',
        'carrera_sede_id'
    ];


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
