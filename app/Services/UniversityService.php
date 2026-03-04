<?php

namespace App\Services;

use App\Models\University;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UniversityService
{
    public function getAll()
    {
        return Cache::remember('universidades.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_UNIVERSIDADES()');
            return University::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    public function findById($id): ?University
    {
        $rows = DB::select('CALL SP_BUSCAR_UNIVERSIDAD(?)', [$id]);
        return $rows ? University::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): University
    {
        $rows = DB::select('CALL SP_CREAR_UNIVERSIDAD(?, ?)', [
            $data['nombre'],
            $data['activo'] ?? 1,
        ]);
        Cache::forget('universidades.all');
        return University::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(University $university, array $data): University
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_UNIVERSIDAD(?, ?, ?)', [
            $university->universidad_id,
            $data['nombre'] ?? $university->nombre,
            $data['activo'] ?? $university->activo,
        ]);
        Cache::forget('universidades.all');
        return University::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(University $university): void
    {
        DB::statement('CALL SP_ELIMINAR_UNIVERSIDAD(?)', [$university->universidad_id]);
        Cache::forget('universidades.all');
    }
}