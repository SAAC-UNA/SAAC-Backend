<?php

namespace App\Services;

use App\Models\Standard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StandardService
{
    public function getAll()
    {
        return Cache::remember('estandares.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_ESTANDARES()');
            return Standard::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    public function findById(int $id): ?Standard
    {
        $rows = DB::select('CALL SP_BUSCAR_ESTANDAR(?)', [$id]);
        return $rows ? Standard::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): Standard
    {
        $rows = DB::select('CALL SP_CREAR_ESTANDAR(?, ?, ?)', [
            $data['criterio_id'],
            $data['descripcion'],
            $data['activo'] ?? 1,
        ]);
        Cache::forget('estandares.all');
        return Standard::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(Standard $std, array $data): Standard
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_ESTANDAR(?, ?, ?, ?)', [
            $std->estandar_id,
            $data['criterio_id'] ?? $std->criterio_id,
            $data['descripcion'] ?? $std->descripcion,
            $data['activo'] ?? $std->activo,
        ]);
        Cache::forget('estandares.all');
        return Standard::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(Standard $std): void
    {
        DB::statement('CALL SP_ELIMINAR_ESTANDAR(?)', [$std->estandar_id]);
        Cache::forget('estandares.all');
    }
}