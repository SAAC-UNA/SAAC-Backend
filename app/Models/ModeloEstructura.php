<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeloEstructura extends Model
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
    const TIPO_TRADICIONAL = 'tradicional';
    const TIPO_JERARQUIA_FLEXIBLE = 'jerarquia_flexible';

    /**
     * Procesos que usan este modelo
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(Process::class, 'modelo_estructura_id', 'modelo_estructura_id');
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
    public function esJerarquiaFlexible(): bool
    {
        return $this->tipo === self::TIPO_JERARQUIA_FLEXIBLE;
    }

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
