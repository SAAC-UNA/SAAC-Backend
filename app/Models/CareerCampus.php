<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerCampus extends Model
{
    /** @use HasFactory<\Database\Factories\CareerCampusFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'CARRERA_SEDE';

    // Clave primaria
    protected $primaryKey = 'carrera_sede_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['carrera_id', 'sede_id'];

    /**
     * Relación: Una sede de carrera pertenece a una carrera.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function career()
    {
        return $this->belongsTo(Career::class, 'carrera_id');
    }

    /**
     * Relación: Una sede de carrera pertenece a un campus.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class, 'sede_id');
    }

    /**
     * Relación: Una sede de carrera tiene muchos ciclos de acreditación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accreditationCycles()
    {
        return $this->hasMany(AccreditationCycle::class, 'carrera_sede_id');
    }

    /**
     * Relación: Usuarios asignados a esta carrera-sede.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'CARRERA_USUARIO', 'carrera_sede_id', 'usuario_id');
    }
}
