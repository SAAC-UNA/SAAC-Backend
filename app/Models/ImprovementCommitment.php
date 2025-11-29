<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo ImprovementCommitment que representa un compromiso de mejora.
 *
 * Registra compromisos de mejora relacionados con el proceso de acreditación,
 * vinculando entidades del repositorio (estándares, dimensiones, componentes,
 * criterios o evidencias) con evidencias específicas que requieren mejora.
 */
class ImprovementCommitment extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'COMPROMISO_MEJORA';

    // Clave primaria
    protected $primaryKey = 'compromiso_mejora_id';

    // Indica si la clave primaria es autoincremental
    public $incrementing = true;

    // Tipo de la clave primaria
    protected $keyType = 'int';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'proceso_id',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    // Conversión automática de tipos
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Atributos adicionales que se incluyen en JSON
    protected $appends = ['is_overdue', 'selecciones'];

    /**
     * Atributos ocultos en respuestas JSON (para evitar duplicación con 'selecciones')
     */
    protected $hidden = ['evidences'];

    /**
     * Relación: Un compromiso de mejora pertenece a un proceso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'proceso_id', 'proceso_id');
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assignedEvidences()
    {
        return $this->belongsToMany(
            EvidenceAssignment::class,
            'COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION',
            'compromiso_mejora_id',
            'evidencia_asignacion_id'
        )->withTimestamps();
    }

    /**
     * Relación: Un compromiso tiene muchas evidencias del repositorio (many-to-many).
     * Vincula directamente con la tabla EVIDENCIA para mostrar evidencias disponibles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function evidences()
    {
        return $this->belongsToMany(
            Evidence::class,
            'COMPROMISO_MEJORA_EVIDENCIA',
            'compromiso_mejora_id',
            'evidencia_id',
            'compromiso_mejora_id',
            'evidencia_id'
        )->withTimestamps();
    }

    /**
     * Relación: Estándar (si entidad_tipo = ESTANDAR).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function standard()
    {
        return $this->belongsTo(Standard::class, 'entidad_id', 'estandar_id');
    }

    /**
     * Relación: Dimensión (si entidad_tipo = DIMENSION).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dimension()
    {
        return $this->belongsTo(Dimension::class, 'entidad_id', 'dimension_id');
    }

    /**
     * Relación: Componente (si entidad_tipo = COMPONENTE).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function component()
    {
        return $this->belongsTo(Component::class, 'entidad_id', 'componente_id');
    }

    /**
     * Relación: Criterio (si entidad_tipo = CRITERIO).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function criterion()
    {
        return $this->belongsTo(Criterion::class, 'entidad_id', 'criterio_id');
    }

    /**
     * Relación: Evidencia (si entidad_tipo = EVIDENCIA).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'entidad_id', 'evidencia_id');
    }

    /**
     * Accessor: Verifica si el compromiso está vencido.
     * Se incluye automáticamente en JSON con el atributo 'is_overdue'.
     *
     * @return bool
     */
    public function getIsOverdueAttribute()
    {
        return $this->estado !== 'Completado' && $this->fecha_fin < now();
    }

    /**
     * Accessor: Agrupa las evidencias por su jerarquía (CRITERIO, DIMENSION, etc.)
     * Muestra cada selección con su jerarquía completa y evidencias asociadas.
     *
     * @return array
     */
    public function getSeleccionesAttribute()
    {
        // Si no hay evidencias cargadas, retornar array vacío
        if (!$this->relationLoaded('evidences') || $this->evidences->isEmpty()) {
            return [];
        }

        $selecciones = [];

        // Agrupar evidencias por criterio_id
        $evidenciasPorCriterio = $this->evidences->groupBy('criterio_id');

        foreach ($evidenciasPorCriterio as $criterioId => $evidencias) {
            $primeraEvidencia = $evidencias->first();
            
            // Cargar relación criterion si existe
            if ($primeraEvidencia && $primeraEvidencia->relationLoaded('criterion')) {
                $criterio = $primeraEvidencia->criterion;
                
                $selecciones[] = [
                    'tipo' => 'CRITERIO',
                    'criterio' => [
                        'criterio_id' => $criterio->criterio_id,
                        'nomenclatura' => $criterio->nomenclatura,
                        'descripcion' => $criterio->descripcion,
                    ],
                    'componente' => $criterio->relationLoaded('component') ? [
                        'componente_id' => $criterio->component->componente_id,
                        'nombre' => $criterio->component->nombre,
                        'nomenclatura' => $criterio->component->nomenclatura,
                    ] : null,
                    'dimension' => $criterio->relationLoaded('component') && $criterio->component->relationLoaded('dimension') ? [
                        'dimension_id' => $criterio->component->dimension->dimension_id,
                        'nombre' => $criterio->component->dimension->nombre,
                        'nomenclatura' => $criterio->component->dimension->nomenclatura,
                    ] : null,
                    'estandares' => $criterio->relationLoaded('standards') ? $criterio->standards->map(fn($std) => [
                        'estandar_id' => $std->estandar_id,
                        'descripcion' => $std->descripcion,
                    ])->toArray() : [],
                    'evidencias' => $evidencias->map(fn($e) => [
                        'evidencia_id' => $e->evidencia_id,
                        'nomenclatura' => $e->nomenclatura,
                        'descripcion' => $e->descripcion,
                    ])->values()->toArray(),
                ];
            }
        }

        return $selecciones;
    }
}
