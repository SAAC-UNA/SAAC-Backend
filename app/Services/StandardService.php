<?php

namespace App\Services;

use App\Models\Standard;
use Illuminate\Support\Facades\Cache;

class StandardService
{
    private const CACHE_KEY = 'estandares.all';
    private const CACHE_TTL = 300;

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            Standard::with('criterion')->orderBy('estandar_id')->get()
        );
    }

    public function findById(int $id): ?Standard
    {
        return Standard::with('criterion')->find($id);
    }

    public function create(array $data): Standard
    {
        $standard = Standard::create([
            'criterio_id' => $data['criterio_id'],
            'descripcion' => $data['descripcion'],
            'activo'      => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $standard->load('criterion');
    }

    public function update(Standard $std, array $data): Standard
    {
        $std->update([
            'criterio_id' => $data['criterio_id'] ?? $std->criterio_id,
            'descripcion' => $data['descripcion'] ?? $std->descripcion,
            'activo'      => $data['activo']      ?? $std->activo,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $std->fresh('criterion');
    }

    public function delete(Standard $std): void
    {
        $std->delete();
        Cache::forget(self::CACHE_KEY);
    }
}