<?php

namespace App\Services;

use App\Models\Dimension;
use Illuminate\Support\Facades\Cache;

class DimensionService
{
    private const CACHE_KEY = 'dimensiones.all';
    private const CACHE_TTL = 300;

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            Dimension::orderBy('nomenclatura')->get()
        );
    }

    public function findById(int $id): ?Dimension
    {
        return Dimension::find($id);
    }

    public function create(array $data): Dimension
    {
        $dimension = Dimension::create([
            'nombre'       => $data['nombre'],
            'nomenclatura' => $data['nomenclatura'],
            'activo'       => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $dimension;
    }

    public function update(Dimension $dimension, array $data): Dimension
    {
        $dimension->update([
            'nombre'       => $data['nombre']       ?? $dimension->nombre,
            'nomenclatura' => $data['nomenclatura'] ?? $dimension->nomenclatura,
            'activo'       => $data['activo']       ?? $dimension->activo,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $dimension->fresh();
    }

    public function delete(Dimension $dimension): void
    {
        $dimension->delete();
        Cache::forget(self::CACHE_KEY);
    }
}