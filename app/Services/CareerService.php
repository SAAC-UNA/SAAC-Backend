<?php

namespace App\Services;

use App\Models\Career;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CareerService
{
    public function getAll()
    {
        return Cache::remember('carreras.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_CARRERAS()');
            return Career::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    public function findById(int $id): ?Career
    {
        $rows = DB::select('CALL SP_BUSCAR_CARRERA(?)', [$id]);
        return $rows ? Career::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): Career
    {
        $rows = DB::select('CALL SP_CREAR_CARRERA(?, ?)', [
            $data['nombre'],
            $data['activo'] ?? 1,
        ]);
        Cache::forget('carreras.all');
        return Career::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(Career $career, array $data): Career
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_CARRERA(?, ?, ?)', [
            $career->carrera_id,
            $data['nombre'] ?? $career->nombre,
            $data['activo'] ?? $career->activo,
        ]);
        Cache::forget('carreras.all');
        return Career::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(Career $career): void
    {
        DB::statement('CALL SP_ELIMINAR_CARRERA(?)', [$career->carrera_id]);
        Cache::forget('carreras.all');
    }
}