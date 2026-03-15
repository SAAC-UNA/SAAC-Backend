<?php

namespace App\Services;

use App\Models\Jerarquia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JerarquiaService
{
    /**
     * Obtener todos los elementos, opcionalmente filtrados por tipo y/o modelo
     */
    public function getAll(?string $tipo = null, ?int $modeloEstructuraId = null)
    {
        $cacheKey = "jerarquias.tipo.{$tipo}.modelo.{$modeloEstructuraId}";
        
        return Cache::remember($cacheKey, 300, function () use ($tipo, $modeloEstructuraId) {
            $rows = DB::select('CALL SP_OBTENER_JERARQUIAS(?, ?)', [$tipo, $modeloEstructuraId]);
            return Jerarquia::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    /**
     * Buscar por ID
     */
    public function findById(int $id): ?Jerarquia
    {
        $rows = DB::select('CALL SP_BUSCAR_JERARQUIA(?)', [$id]);
        return $rows ? Jerarquia::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    /**
     * Crear nuevo elemento
     */
    public function create(array $data): Jerarquia
    {
        $rows = DB::select('CALL SP_CREAR_JERARQUIA(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $data['modelo_estructura_id'],
            $data['parent_id'] ?? null,
            $data['nombre'],
            $data['tipo'],
            $data['categoria'] ?? null,
            $data['nomenclatura'] ?? null,
            $data['descripcion'] ?? null,
            $data['orden'] ?? 0,
            $data['activo'] ?? 1,
        ]);

        $this->clearCache($data['tipo'] ?? null);
        
        return Jerarquia::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    /**
     * Actualizar elemento existente
     */
    public function update(Jerarquia $jerarquia, array $data): Jerarquia
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_JERARQUIA(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $jerarquia->jerarquia_id,
            $data['parent_id'] ?? $jerarquia->parent_id,
            $data['nombre'] ?? $jerarquia->nombre,
            $data['tipo'] ?? $jerarquia->tipo,
            $data['categoria'] ?? $jerarquia->categoria,
            $data['nomenclatura'] ?? $jerarquia->nomenclatura,
            $data['descripcion'] ?? $jerarquia->descripcion,
            $data['orden'] ?? $jerarquia->orden,
            $data['activo'] ?? $jerarquia->activo,
        ]);

        $this->clearCache($jerarquia->tipo);
        
        return Jerarquia::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    /**
     * Eliminar elemento
     */
    public function delete(Jerarquia $jerarquia): void
    {
        DB::statement('CALL SP_ELIMINAR_JERARQUIA(?)', [$jerarquia->jerarquia_id]);
        $this->clearCache($jerarquia->tipo);
    }

    /**
     * Obtener árbol completo (recursivo)
     */
    public function getTree(?int $rootId = null, ?int $modeloEstructuraId = null)
    {
        $cacheKey = "jerarquia.tree.{$rootId}.modelo.{$modeloEstructuraId}";
        
        return Cache::remember($cacheKey, 300, function () use ($rootId, $modeloEstructuraId) {
            $rows = DB::select('CALL SP_OBTENER_ARBOL_JERARQUIA(?, ?)', [$rootId, $modeloEstructuraId]);
            return array_map(fn($r) => (array) $r, $rows);
        });
    }

    /**
     * Limpiar caché
     */
    private function clearCache(?string $tipo = null): void
    {
        Cache::forget('jerarquias.all');
        Cache::forget('jerarquia.tree.all');
        
        if ($tipo) {
            Cache::forget("jerarquias.tipo.{$tipo}");
        }
    }
}
