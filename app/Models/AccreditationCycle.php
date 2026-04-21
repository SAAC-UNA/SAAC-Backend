<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @property int    $ciclo_acreditacion_id
 * @property int    $carrera_sede_id
 * @property int|null $modelo_estructura_id
 * @property string $nombre
 * @property string $estado
 */
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
    protected $fillable = ['carrera_sede_id', 'modelo_estructura_id', 'nombre', 'estado'];

    // --- Helpers de dominio: verifican el estado del ciclo ---

    /** Retorna true si el ciclo está en estado 'activo'. */
    public function isActive(): bool
    {
        return $this->estado === self::STATUS_ACTIVE;
    }

    /** Retorna true si el ciclo está en estado 'inactivo'. */
    public function isInactive(): bool
    {
        return $this->estado === self::STATUS_INACTIVE;
    }

    /** Retorna true si el ciclo finalizó (estado 'completado'). */
    public function isCompleted(): bool
    {
        return $this->estado === self::STATUS_COMPLETED;
    }

    /**
     * AC-4: Un ciclo solo permite edición mientras está activo.
     * La Policy llama este método antes de autorizar cualquier update.
     */
    public function isEditable(): bool
    {
        return $this->estado === self::STATUS_ACTIVE;
    }
 
    
    // --- Scopes: filtros reutilizables por estado ---

    /** Filtra solo ciclos activos: AccreditationCycle::active()->get() */
    public function scopeActive($query)
    {
        return $query->where('estado', self::STATUS_ACTIVE);
    }

    /** Filtra solo ciclos inactivos: AccreditationCycle::inactive()->get() */
    public function scopeInactive($query)
    {
        return $query->where('estado', self::STATUS_INACTIVE);
    }

    /** Filtra solo ciclos completados: AccreditationCycle::completed()->get() */
    public function scopeCompleted($query)
    {
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

    /**
     * Relación: Un ciclo de acreditación puede tener un informe de acreditación publicado.
     * Un ciclo solo puede tener un informe (restricción unique en BD).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function accreditationReport()
    {
        return $this->hasOne(AccreditationReport::class, 'ciclo_acreditacion_id', 'ciclo_acreditacion_id');
    }
}
