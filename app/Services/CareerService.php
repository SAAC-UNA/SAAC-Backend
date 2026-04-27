<?php

namespace App\Services;

use App\Models\Career;
use Illuminate\Support\Facades\Cache;

class CareerService
{
    private const CACHE_KEY = 'carreras.all';
    private const CACHE_TTL = 300;

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            Career::with('university')->orderBy('nombre')->get()
        );
    }

    public function findById(int $id): ?Career
    {
        return Career::with('university')->find($id);
    }

    public function create(array $data): Career
    {
        $career = Career::create([
            'nombre'         => $data['nombre'],
            'activo'         => $data['activo'] ?? true,
            'universidad_id' => $data['universidad_id'],
        ]);
        Cache::forget(self::CACHE_KEY);
        return $career->load('university');
    }

    public function update(Career $career, array $data): Career
    {
        $career->update([
            'nombre'         => $data['nombre'] ?? $career->nombre,
            'activo'         => $data['activo'] ?? $career->activo,
            'universidad_id' => $data['universidad_id'] ?? $career->universidad_id,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $career->fresh('university');
    }

    public function delete(Career $career): void
    {
        $career->delete();
        Cache::forget(self::CACHE_KEY);
    }
}