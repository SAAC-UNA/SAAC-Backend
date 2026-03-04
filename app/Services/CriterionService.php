<?php

namespace App\Services;

use App\Models\Criterion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CriterionService
{
    public function getAll()
    {
        return Cache::remember('criterios.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_CRITERIOS()');
            return Criterion::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    public function findById(int $id): ?Criterion
    {
        $rows = DB::select('CALL SP_BUSCAR_CRITERIO(?)', [$id]);
        return $rows ? Criterion::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): Criterion
    {
        $rows = DB::select('CALL SP_CREAR_CRITERIO(?, ?, ?, ?)', [
            $data['componente_id'],
            $data['descripcion'],
            $data['nomenclatura'],
            $data['activo'] ?? 1,
        ]);
        Cache::forget('criterios.all');
        return Criterion::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(Criterion $criterion, array $data): Criterion
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_CRITERIO(?, ?, ?, ?, ?)', [
            $criterion->criterio_id,
            $data['componente_id'] ?? $criterion->componente_id,
            $data['descripcion'] ?? $criterion->descripcion,
            $data['nomenclatura'] ?? $criterion->nomenclatura,
            $data['activo'] ?? $criterion->activo,
        ]);
        Cache::forget('criterios.all');
        return Criterion::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(Criterion $criterion): void
    {
        DB::statement('CALL SP_ELIMINAR_CRITERIO(?)', [$criterion->criterio_id]);
        Cache::forget('criterios.all');
    }
}