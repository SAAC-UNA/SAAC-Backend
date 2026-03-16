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
     * 
     * NOTA sobre 'orden': Aunque no usamos árbol visual, el campo 'orden' es NECESARIO para:
     * - Ordenar elementos hermanos (mismo parent_id) en listados
     * - Controlar secuencia de dimensiones/criterios (ej: "Dimensión 1, 2, 3...")
     * - Prioridad de visualización en frontend (independiente del árbol)
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
            $data['orden'] ?? 0,  // Orden para sorting, NO para árbol
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
     * Activar/Desactivar elemento (toggle)
     * Siguiendo patrón Service como ModeloEstructuraService
     * 
     * LÓGICA DE CASCADA (igual que modelo tradicional):
     * - Al DESACTIVAR: desactiva en cascada todos los hijos recursivamente
     * - Al ACTIVAR: solo activa el elemento actual (no toca hijos)
     */
    public function toggleActive(Jerarquia $jerarquia): Jerarquia
    {
        $newActiveState = !$jerarquia->activo;
        
        $jerarquia->activo = $newActiveState;
        $jerarquia->save();

        // Si desactivamos, aplicar cascada a todos los hijos
        if (!$newActiveState) {
            $this->deactivateChildrenRecursively($jerarquia);
        }

        $this->clearCache($jerarquia->tipo);
        return $jerarquia;
    }

    /**
     * Desactivar hijos recursivamente
     * Recorre todos los descendientes y los desactiva en cascada
     */
    private function deactivateChildrenRecursively(Jerarquia $parent): void
    {
        foreach ($parent->children as $child) {
            $child->activo = false;
            $child->save();
            
            // Recursión: si el hijo tiene hijos, desactivarlos también
            if ($child->hasChildren()) {
                $this->deactivateChildrenRecursively($child);
            }
        }
    }

    /**
     * Obtener árbol completo (recursivo)
     * COMENTADO: Funcionalidad de árbol no se usa actualmente
     * El frontend maneja jerarquías como lista plana con parent_id
     * Descomentar si se implementa visualización tipo árbol en el futuro
     */
    // public function getTree(?int $rootId = null, ?int $modeloEstructuraId = null)
    // {
    //     $cacheKey = "jerarquia.tree.{$rootId}.modelo.{$modeloEstructuraId}";
    //     
    //     return Cache::remember($cacheKey, 300, function () use ($rootId, $modeloEstructuraId) {
    //         $rows = DB::select('CALL SP_OBTENER_ARBOL_JERARQUIA(?, ?)', [$rootId, $modeloEstructuraId]);
    //         return array_map(fn($r) => (array) $r, $rows);
    //     });
    // }

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
