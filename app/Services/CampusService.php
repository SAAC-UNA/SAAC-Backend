<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CampusService
{
    /**
     * Lista sedes con filtro opcional por universidad_id.
     * Pasa NULL para obtener todas las sedes.
     */
    public function getAll(?int $universidadId = null)
    {
        if ($universidadId !== null) {
            $rows = DB::select('CALL SP_OBTENER_SEDES(?)', [$universidadId]);
            return Campus::hydrate(array_map(fn($r) => (array) $r, $rows));
        }

        return Cache::remember('campuses.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_SEDES(?)', [null]);
            return Campus::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    public function findById(int $id): ?Campus
    {
        $rows = DB::select('CALL SP_BUSCAR_SEDE(?)', [$id]);
        return $rows ? Campus::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): Campus
    {
        $rows = DB::select('CALL SP_CREAR_SEDE(?, ?, ?)', [
            $data['universidad_id'],
            $data['nombre'],
            $data['activo'] ?? 1,
        ]);
        Cache::forget('campuses.all');
        return Campus::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(Campus $campus, array $data): Campus
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_SEDE(?, ?, ?)', [
            $campus->sede_id,
            $data['nombre'] ?? $campus->nombre,
            $data['activo'] ?? $campus->activo,
        ]);
        Cache::forget('campuses.all');
        return Campus::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(Campus $campus): void
    {
        DB::statement('CALL SP_ELIMINAR_SEDE(?)', [$campus->sede_id]);
        Cache::forget('campuses.all');
    }
}