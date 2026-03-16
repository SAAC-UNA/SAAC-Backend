<?php

namespace App\Services;

use App\Models\ModeloEstructura;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ModeloEstructuraService
{
    /**
     * Obtener todos los modelos de estructura
     */
    public function getAll()
    {
        return Cache::remember('modelos_estructura.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_MODELOS_ESTRUCTURA()');
            return ModeloEstructura::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    /**
     * Obtener solo modelos activos
     */
    public function getActive()
    {
        return Cache::remember('modelos_estructura.active', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_MODELOS_ACTIVOS()');
            return ModeloEstructura::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    /**
     * Buscar modelo específico por ID
     */
    public function findById(int $id): ?ModeloEstructura
    {
        $rows = DB::select('CALL SP_BUSCAR_MODELO_ESTRUCTURA(?)', [$id]);
        return $rows ? ModeloEstructura::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    /**
     * COMENTADO: No se permite crear modelos - ya están predefinidos en migración
     * (SINAES 2018 tradicional y SINAES 2026 jerarquia_flexible)
     */
    // public function create(array $data): ModeloEstructura
    // {
    //     $model = ModeloEstructura::create($data);
    //     $this->clearCache();
    //     return $model;
    // }

    /**
     * COMENTADO: No se permite editar modelos - son predefinidos del sistema
     * Los únicos cambios permitidos son: activo (vía toggleActive)
     */
    // public function update(ModeloEstructura $model, array $data): ModeloEstructura
    // {
    //     $model->update($data);
    //     $this->clearCache();
    //     return $model->fresh();
    // }

    /**
     * Activar/Desactivar modelo
     * 
     * NOTA IMPORTANTE (TODO - PENDIENTE):
     * ⚠️ Actualmente solo cambia el campo 'activo' del modelo, NO desactiva en cascada su estructura.
     * 
     * FALTA IMPLEMENTAR:
     * - Validar que no haya procesos activos antes de desactivar
     * - Desactivar en cascada la estructura asociada:
     *   * Si esTradicional(): DIMENSION → COMPONENTE → CRITERIO → ESTANDAR
     *   * Si esJerarquiaFlexible(): JERARQUIA (WHERE modelo_estructura_id)
     * - NO desactivar EVIDENCIA (las evidencias deben permanecer activas)
     * 
     * Referencia código pendiente:
     * if ($model->activo && !$newState) {
     *     // Validar procesos activos
     *     if ($model->procesos()->where('activo', true)->exists()) {
     *         throw new \Exception('No se puede desactivar - tiene procesos activos');
     *     }
     *     // Desactivar estructura
     *     if ($model->esTradicional()) {
     *         DB::table('DIMENSION')->update(['activo' => false]);
     *         DB::table('COMPONENTE')->update(['activo' => false]);
     *         DB::table('CRITERIO')->update(['activo' => false]);
     *         DB::table('ESTANDAR')->update(['activo' => false]);
     *     } elseif ($model->esJerarquiaFlexible()) {
     *         DB::table('JERARQUIA')->where('modelo_estructura_id', $model->modelo_estructura_id)->update(['activo' => false]);
     *     }
     * }
     */
    public function toggleActive(ModeloEstructura $model): ModeloEstructura
    {
        $model->activo = !$model->activo;
        $model->save();
        $this->clearCache();
        return $model;
    }

    /**
     * Limpiar caché de modelos de estructura
     */
    private function clearCache(): void
    {
        Cache::forget('modelos_estructura.all');
        Cache::forget('modelos_estructura.active');
    }
}
