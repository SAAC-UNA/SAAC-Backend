<?php

namespace App\Services;

use App\Models\StructureModel;
use Illuminate\Support\Facades\Cache;

class StructureModelService
{
    /**
     * Obtener todos los modelos de estructura
     */
    public function getAll()
    {
        return Cache::remember('modelos_estructura.all', 300, function () {
            return StructureModel::orderBy('modelo_estructura_id')->get();
        });
    }

    /**
     * Obtener solo modelos activos
     */
    public function getActive()
    {
        return Cache::remember('modelos_estructura.active', 300, function () {
            return StructureModel::where('activo', true)->orderBy('modelo_estructura_id')->get();
        });
    }

    /**
     * Buscar modelo específico por ID
     */
    public function findById(int $id): ?StructureModel
    {
        return StructureModel::find($id);
    }

    /**
     * Crear nuevo modelo de estructura
     */
    public function create(array $data): StructureModel
    {
        $model = StructureModel::create($data);
        $this->clearCache();
        return $model;
    }

    /**
     * Actualizar metadata del modelo (nombre, descripcion, version)
     */
    public function update(StructureModel $model, array $data): StructureModel
    {
        $model->update($data);
        $this->clearCache();
        return $model->fresh();
    }

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
     *   * Si esElementoFlexible(): ELEMENTO (WHERE modelo_estructura_id)
     * - NO desactivar EVIDENCIA (las evidencias deben permanecer activas)
     * 
     * Referencia código pendiente:
     * if ($model->activo && !$newState) {
     *     // Validar procesos activos
     *     if ($model->ciclosAcreditacion()->whereHas('processes', fn($q) => $q->where('activo', true))->exists()) {
     *         throw new \Exception('No se puede desactivar - tiene procesos activos');
     *     }
     *     // Desactivar estructura
     *     if ($model->esTradicional()) {
     *         DB::table('DIMENSION')->update(['activo' => false]);
     *         DB::table('COMPONENTE')->update(['activo' => false]);
     *         DB::table('CRITERIO')->update(['activo' => false]);
     *         DB::table('ESTANDAR')->update(['activo' => false]);
     *     } elseif ($model->esElementoFlexible()) {
     *         DB::table('ELEMENTO')->where('modelo_estructura_id', $model->modelo_estructura_id)->update(['activo' => false]);
     *     }
     * }
     */
    public function toggleActive(StructureModel $model): StructureModel
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
