<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StructureModel extends Model
{
    protected $table = 'MODELO_ESTRUCTURA';
    protected $primaryKey = 'modelo_estructura_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'version',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Constantes para tipos de modelo
    // Son VALORES FIJOS del sistema, NO se inventan nuevos tipos
    const TIPO_TRADICIONAL = 'tradicional';           // Modelo SINAES 2018 → usa DIMENSION/COMPONENTE/CRITERIO
    const TIPO_ELEMENTO_FLEXIBLE = 'elemento_flexible'; // Modelo SINAES 2026+ → usa ELEMENTO (arbol con padre_id)
    
    // Tipos futuros comentados (no se usan actualmente):
    // const TIPO_HIBRIDO = 'hibrido';                   // Modelo mixto → usa ambas estructuras
    // const TIPO_CUSTOM = 'custom';                     // Modelo personalizado por institución

    /**
     * Procesos que usan este modelo
     */
    public function ciclosAcreditacion(): HasMany
    {
        return $this->hasMany(AccreditationCycle::class, 'modelo_estructura_id', 'modelo_estructura_id');
    }

    /**
     * Verificar si es modelo tradicional
     */
    public function esTradicional(): bool
    {
        return $this->tipo === self::TIPO_TRADICIONAL;
    }

    /**
     * Verificar si es modelo de jerarquía flexible
     */
    public function esElementoFlexible(): bool
    {
        return $this->tipo === self::TIPO_ELEMENTO_FLEXIBLE;
    }

    // Métodos para tipos futuros comentados (no se usan actualmente):
    
    // /**
    //  * Verificar si es modelo híbrido
    //  */
    // public function esHibrido(): bool
    // {
    //     return $this->tipo === self::TIPO_HIBRIDO;
    // }

    // /**
    //  * Verificar si es modelo personalizado
    //  */
    // public function esCustom(): bool
    // {
    //     return $this->tipo === self::TIPO_CUSTOM;
    // }

    /**
     * Scope para modelos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope por tipo
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
