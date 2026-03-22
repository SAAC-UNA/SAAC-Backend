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


    // Estados posibles del ciclo
    public const STATUS_ACTIVE = 'activo';
    public const STATUS_INACTIVE = 'inactivo';
    public const STATUS_COMPLETED = 'completado';

    // Nombre de la tabla en la base de datos
    protected $table = 'CICLO_ACREDITACION';

    // Clave primaria
    protected $primaryKey = 'ciclo_acreditacion_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['carrera_sede_id', 'nombre', 'estado'];

    //helpers de dominio para verificar el estado del ciclo
    public function isActive(): bool
    {        return $this->estado === self::STATUS_ACTIVE;
    }
    public function isInactive(): bool
    {
        return $this->estado === self::STATUS_INACTIVE;
    }
    public function isCompleted(): bool
    {        return $this->estado === self::STATUS_COMPLETED;
    }
    public function isEditable(): bool
    {
        // Solo se puede editar si el ciclo está activo
        return $this->estado === self::STATUS_ACTIVE;
    }
 
    
    //scopes para filtrar por estado
    public function scopeActive($query){
        return $query->where('estado', self::STATUS_ACTIVE);
    }
    public function scopeInactive($query){
        return $query->where('estado', self::STATUS_INACTIVE);
    }
    public function scopeCompleted($query){
        return $query->where('estado', self::STATUS_COMPLETED);
    }
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

    /**
     * Relación: Un ciclo de acreditación pertenece a un modelo de estructura SINAES.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function modeloEstructura()
    {
        return $this->belongsTo(StructureModel::class, 'modelo_estructura_id', 'modelo_estructura_id');
    }
}
