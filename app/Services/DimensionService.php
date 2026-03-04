<?php

namespace App\Services;

use App\Models\Dimension;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DimensionService
{
    public function getAll()
    {
        return Cache::remember('dimensiones.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_DIMENSIONES()');
            return Dimension::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    public function findById(int $id): ?Dimension
    {
        $rows = DB::select('CALL SP_BUSCAR_DIMENSION(?)', [$id]);
        return $rows ? Dimension::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): Dimension
    {
        $rows = DB::select('CALL SP_CREAR_DIMENSION(?, ?, ?)', [
            $data['nombre'],
            $data['nomenclatura'],
            $data['activo'] ?? 1,
        ]);
        Cache::forget('dimensiones.all');
        return Dimension::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(Dimension $dimension, array $data): Dimension
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_DIMENSION(?, ?, ?, ?)', [
            $dimension->dimension_id,
            $data['nombre'] ?? $dimension->nombre,
            $data['nomenclatura'] ?? $dimension->nomenclatura,
            $data['activo'] ?? $dimension->activo,
        ]);
        Cache::forget('dimensiones.all');
        return Dimension::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(Dimension $dimension): void
    {
        DB::statement('CALL SP_ELIMINAR_DIMENSION(?)', [$dimension->dimension_id]);
        Cache::forget('dimensiones.all');
    }
}