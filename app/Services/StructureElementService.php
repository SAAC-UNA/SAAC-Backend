<?php

namespace App\Services;

use App\Models\StructureElement;
use Illuminate\Support\Facades\Cache;

class StructureElementService
{
    /**
     * Obtener todos los Elements, opcionalmente filtrados por tipo y/o modelo
     */
    public function getAll(?string $type = null, ?int $modelId = null)
    {
        $cacheKey = "elements.type.{$type}.model.{$modelId}";

        return Cache::remember($cacheKey, 300, function () use ($type, $modelId) {
            $query = StructureElement::orderBy('elemento_id');

            // Al consultar por modelo específico (vista de gestión) se devuelven todos los
            // Elements sin importar si están activos o no.
            // Al consultar sin modelo (selectores/lookups) solo se devuelven activos.
            if ($modelId === null) {
                $query->where('activo', true);
            }

            if ($type !== null) {
                $query->where('tipo', $type);
            }

            if ($modelId !== null) {
                $query->where('modelo_estructura_id', $modelId);
            }

            return $query->get();
        });
    }

    /**
     * Buscar por ID.
     *
     * NOTA ARQUITECTURA B:
     * El modelo flexible NO usa la tabla EVIDENCIA.
     * ELEMENTO apunta directamente a ARCHIVO (tabla ARCHIVO tiene elemento_id).
     * La tabla EVIDENCIA solo pertenece al modelo tradicional (CRITERIO → EVIDENCIA).
     */
    public function findById(int $id): ?StructureElement
    {
        return StructureElement::find($id);
    }

    /**
     * Crear nuevo elemento. El orden de listado es por elemento_id (orden de creación).
     *
     * NOTA ARQUITECTURA B:
     * El modelo flexible NO crea evidencias asociadas al elemento.
     * Si se necesitan documentos requeridos, se usan asignaciones directas a ARCHIVO
     * con elemento_id (no evidencia_id).
     */
    public function create(array $data): StructureElement
    {
        $element = StructureElement::create([
            'modelo_estructura_id' => $data['modelo_estructura_id'],
            'padre_id'             => $data['padre_id'] ?? null,
            'tipo'                 => $data['tipo'],
            'nombre'               => $data['nombre'] ?? null,
            'categoria'            => $data['categoria'] ?? null,
            'nomenclatura'         => $data['nomenclatura'] ?? null,
            'descripcion'          => $data['descripcion'] ?? null,
            'activo'               => $data['activo'] ?? true,
        ]);

        $this->clearCache($data['tipo'] ?? null, $data['modelo_estructura_id'] ?? null);

        return $element;
    }

    /**
     * Actualizar elemento existente
     */
    public function update(StructureElement $element, array $data): StructureElement
    {
        $element->update([
            'padre_id' => $data['padre_id'] ?? $element->padre_id,
            'tipo' => $data['tipo'] ?? $element->tipo,
            'nombre' => array_key_exists('nombre', $data) ? $data['nombre'] : $element->nombre,
            'categoria' => $data['categoria'] ?? $element->categoria,
            'nomenclatura' => $data['nomenclatura'] ?? $element->nomenclatura,
            'descripcion' => $data['descripcion'] ?? $element->descripcion,
            'activo' => $data['activo'] ?? $element->activo,
        ]);

        $this->clearCache($element->tipo, $element->modelo_estructura_id);

        return $element->fresh();
    }

    /**
     * Eliminar elemento
     */
    public function delete(StructureElement $element): void
    {
        $type = $element->tipo;
        $modelId = $element->modelo_estructura_id;
        $element->delete();
        $this->clearCache($type, $modelId);
    }

    /**
     * Activar/Desactivar elemento con cascada bidireccional.
     * Igual que la jerarquía tradicional (DimensionController):
     * - Al ACTIVAR: activa en cascada todos los hijos recursivamente
     * - Al DESACTIVAR: desactiva en cascada todos los hijos recursivamente
     */
    public function setActiveWithCascade(StructureElement $element, bool $active): void
    {
        $element->activo = $active;
        $element->saveQuietly();
        $this->clearCache($element->tipo, $element->modelo_estructura_id);

        foreach ($element->children as $child) {
            $this->setActiveWithCascade($child, $active);
        }
    }

    /**
     * Obtener árbol completo (recursivo)
     * COMENTADO: Funcionalidad de árbol no se usa actualmente
     * El frontend maneja jerarquías como lista plana con parent_id
     * Descomentar si se implementa visualización tipo árbol en el futuro
     */
    // public function getTree(?int $rootId = null, ?int $structureModelId = null)
    // {
    //     $cacheKey = "elements.tree.{$rootId}.model.{$structureModelId}";
    //
    //     return Cache::remember($cacheKey, 300, function () use ($rootId, $structureModelId) {
    //         $rows = DB::select('CALL SP_OBTENER_ARBOL_JERARQUIA(?, ?)', [$rootId, $structureModelId]);
    //         return array_map(fn($r) => (array) $r, $rows);
    //     });
    // }

    /**
     * Limpiar caché
     */
    private function clearCache(?string $type = null, ?int $modelId = null): void
    {
        Cache::forget('elements.all');
        Cache::forget('elements.tree.all');
        // Llave sin modelo (tipo solamente)
        Cache::forget("elements.type.{$type}.model.");

        if ($type) {
            Cache::forget("elements.type.{$type}");
        }

        // Llave con modelo específico
        if ($modelId) {
            Cache::forget("elements.type..model.{$modelId}");
            if ($type) {
                Cache::forget("elements.type.{$type}.model.{$modelId}");
            }
        }
    }
}
