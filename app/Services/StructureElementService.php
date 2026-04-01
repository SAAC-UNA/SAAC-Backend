<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\StructureElement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StructureElementService
{
    /**
     * Obtener todos los Elements, opcionalmente filtrados por tipo y/o modelo
     */
    public function getAll(?string $tipo = null, ?int $modeloEstructuraId = null)
    {
        $cacheKey = "Elements.tipo.{$tipo}.modelo.{$modeloEstructuraId}";
        
        return Cache::remember($cacheKey, 300, function () use ($tipo, $modeloEstructuraId) {
            $query = StructureElement::orderBy('elemento_id');

            // Al consultar por modelo específico (vista de gestión) se devuelven todos los
            // Elements sin importar si están activos o no.
            // Al consultar sin modelo (selectores/lookups) solo se devuelven activos.
            if ($modeloEstructuraId === null) {
                $query->where('activo', true);
            }

            if ($tipo !== null) {
                $query->where('tipo', $tipo);
            }

            if ($modeloEstructuraId !== null) {
                $query->where('modelo_estructura_id', $modeloEstructuraId);
            }

            return $query->get();
        });
    }

    /**
     * Buscar por ID, incluyendo sus evidencias si las tiene.
     *
     * HU-012 (escritura flexible) — Gap 5a:
     * ANTES: retornaba el ELEMENTO crudo sin relaciones.
     * DESPUÉS: carga 'evidencias' en eager-load para que GET /Elements/{id}
     *          muestre los documentos requeridos asociados al nodo.
     *          En Elements del modelo tradicional la colección llega vacía
     *          (correcto — sus evidencias pertenecen a CRITERIO, no a ELEMENTO).
     */
    public function findById(int $id): ?StructureElement
    {
        return StructureElement::with('evidencias')->find($id);
    }

    /**
     * Crear nuevo elemento. El orden de listado es por elemento_id (orden de creación).
     *
     * HU-012 (escritura flexible) — Gap 5b:
     * ANTES: solo creaba el ELEMENTO sin soporte para evidencias.
     * DESPUÉS: si el request incluye 'evidencias[]' (campo opcional del modelo flexible),
     *          todo se ejecuta en una transacción SQL atómica:
     *            1. INSERT en ELEMENTO
     *            2. INSERT en EVIDENCIA (una por cada item de evidencias[])
     *          Si cualquier INSERT falla, rollback completo — no quedan Elements
     *          huérfanos ni evidencias sin elemento.
     *
     * La transacción no cambia el comportamiento para el modelo tradicional
     * (create sigue funcionando igual cuando 'evidencias' no viene).
     */
    public function create(array $data): StructureElement
    {
        return DB::transaction(function () use ($data) {
            $elemento = StructureElement::create([
                'modelo_estructura_id' => $data['modelo_estructura_id'],
                'padre_id'             => $data['padre_id'] ?? null,
                'tipo'                 => $data['tipo'],
                'categoria'            => $data['categoria'] ?? null,
                'nomenclatura'         => $data['nomenclatura'] ?? null,
                'descripcion'          => $data['descripcion'] ?? null,
                'activo'               => $data['activo'] ?? true,
            ]);



            $this->clearCache($data['tipo'] ?? null);

            // Retornar elemento con evidencias ya cargadas para el response del controller
            return $elemento->load('evidencias');
        });
    }

    /**
     * Actualizar elemento existente
     */
    public function update(StructureElement $elemento, array $data): StructureElement
    {
        $elemento->update([
            'padre_id'     => $data['padre_id'] ?? $elemento->padre_id,
            'tipo'         => $data['tipo'] ?? $elemento->tipo,
            'categoria'    => $data['categoria'] ?? $elemento->categoria,
            'nomenclatura' => $data['nomenclatura'] ?? $elemento->nomenclatura,
            'descripcion'  => $data['descripcion'] ?? $elemento->descripcion,
            'activo'       => $data['activo'] ?? $elemento->activo,
        ]);

        $this->clearCache($elemento->tipo, $elemento->modelo_estructura_id);

        return $elemento->fresh();
    }

    /**
     * Eliminar elemento
     */
    public function delete(StructureElement $elemento): void
    {
        $tipo = $elemento->tipo;
        $modeloId = $elemento->modelo_estructura_id;
        $elemento->delete();
        $this->clearCache($tipo, $modeloId);
    }

    /**
     * Activar/Desactivar elemento con cascada bidireccional.
     * Igual que la jerarquía tradicional (DimensionController):
     * - Al ACTIVAR: activa en cascada todos los hijos recursivamente
     * - Al DESACTIVAR: desactiva en cascada todos los hijos recursivamente
     */
    public function setActiveWithCascade(StructureElement $elemento, bool $active): void
    {
        $elemento->activo = $active;
        $elemento->saveQuietly();
        $this->clearCache($elemento->tipo, $elemento->modelo_estructura_id);

        foreach ($elemento->children as $child) {
            $this->setActiveWithCascade($child, $active);
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
    private function clearCache(?string $tipo = null, ?int $modeloEstructuraId = null): void
    {
        Cache::forget('Elements.all');
        Cache::forget('elemento.tree.all');
        // Llave sin modelo (tipo solamente)
        Cache::forget("Elements.tipo.{$tipo}.modelo.");

        if ($tipo) {
            Cache::forget("Elements.tipo.{$tipo}");
        }

        // Llave con modelo específico
        if ($modeloEstructuraId) {
            Cache::forget("Elements.tipo..modelo.{$modeloEstructuraId}");
            if ($tipo) {
                Cache::forget("Elements.tipo.{$tipo}.modelo.{$modeloEstructuraId}");
            }
        }
    }
}
