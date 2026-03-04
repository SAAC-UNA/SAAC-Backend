<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComponentService
{
    public function getAll()
    {
        return Cache::remember('componentes.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_COMPONENTES()');
            return Component::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    public function findById(int $id): ?Component
    {
        $rows = DB::select('CALL SP_BUSCAR_COMPONENTE(?)', [$id]);
        return $rows ? Component::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): Component
    {
        $rows = DB::select('CALL SP_CREAR_COMPONENTE(?, ?, ?, ?)', [
            $data['dimension_id'],
            $data['nombre'],
            $data['nomenclatura'],
            $data['activo'] ?? 1,
        ]);
        Cache::forget('componentes.all');
        return Component::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(Component $component, array $data): Component
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_COMPONENTE(?, ?, ?, ?, ?)', [
            $component->componente_id,
            $data['dimension_id'] ?? $component->dimension_id,
            $data['nombre'] ?? $component->nombre,
            $data['nomenclatura'] ?? $component->nomenclatura,
            $data['activo'] ?? $component->activo,
        ]);
        Cache::forget('componentes.all');
        return Component::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(Component $component): void
    {
        DB::statement('CALL SP_ELIMINAR_COMPONENTE(?)', [$component->componente_id]);
        Cache::forget('componentes.all');
    }
}