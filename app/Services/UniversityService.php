<?php

namespace App\Services;

use App\Models\University;
use Illuminate\Support\Facades\Cache;

class UniversityService
{
    private const CACHE_KEY = 'universidades.all';
    private const CACHE_TTL = 300;

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            University::orderBy('nombre')->get()
        );
    }

    public function findById(int $id): ?University
    {
        return University::find($id);
    }

    public function create(array $data): University
    {
        $university = University::create([
            'nombre' => $data['nombre'],
            'activo' => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $university;
    }

    public function update(University $university, array $data): University
    {
        $university->update([
            'nombre' => $data['nombre'] ?? $university->nombre,
            'activo' => $data['activo'] ?? $university->activo,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $university->fresh();
    }

    public function delete(University $university): void
    {
        $university->delete();
        Cache::forget(self::CACHE_KEY);
    }
}