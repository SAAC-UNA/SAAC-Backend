<?php

namespace App\Services;

use App\Models\Criterion;
use Illuminate\Support\Facades\Cache;

class CriterionService
{
    private const CACHE_KEY = 'criterios.all';
    private const CACHE_TTL = 300;

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            Criterion::with('component.dimension')->orderBy('nomenclatura')->get()
        );
    }

    public function findById(int $id): ?Criterion
    {
        return Criterion::with('component.dimension')->find($id);
    }

    public function create(array $data): Criterion
    {
        $criterion = Criterion::create([
            'componente_id' => $data['componente_id'],
            'descripcion'   => $data['descripcion'],
            'nomenclatura'  => $data['nomenclatura'],
            'activo'        => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $criterion->load('component.dimension');
    }

    public function update(Criterion $criterion, array $data): Criterion
    {
        $criterion->update([
            'componente_id' => $data['componente_id'] ?? $criterion->componente_id,
            'descripcion'   => $data['descripcion']   ?? $criterion->descripcion,
            'nomenclatura'  => $data['nomenclatura']  ?? $criterion->nomenclatura,
            'activo'        => $data['activo']        ?? $criterion->activo,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $criterion->fresh('component.dimension');
    }

    public function delete(Criterion $criterion): void
    {
        $criterion->delete();
        Cache::forget(self::CACHE_KEY);
    }
}