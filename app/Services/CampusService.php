<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Support\Facades\Cache;

class CampusService
{
    private const CACHE_ALL  = 'campuses.all';
    private const CACHE_TTL  = 300;

    /**
     * Lista sedes con filtro opcional por universidad_id.
     * Pasa NULL para obtener todas las sedes con su universidad eager-loaded.
     */
    public function getAll(?int $universidadId = null)
    {
        if ($universidadId !== null) {
            return Campus::with('university')
                ->where('universidad_id', $universidadId)
                ->orderBy('nombre')
                ->get();
        }

        return Cache::remember(self::CACHE_ALL, self::CACHE_TTL, fn () =>
            Campus::with('university')->orderBy('nombre')->get()
        );
    }

    public function findById(int $id): ?Campus
    {
        return Campus::with('university')->find($id);
    }

    public function create(array $data): Campus
    {
        $campus = Campus::create([
            'universidad_id' => $data['universidad_id'],
            'nombre'         => $data['nombre'],
            'activo'         => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_ALL);
        return $campus->load('university');
    }

    public function update(Campus $campus, array $data): Campus
    {
        $campus->update([
            'nombre' => $data['nombre'] ?? $campus->nombre,
            'activo' => $data['activo'] ?? $campus->activo,
        ]);
        Cache::forget(self::CACHE_ALL);
        return $campus->fresh('university');
    }

    public function delete(Campus $campus): void
    {
        $campus->delete();
        Cache::forget(self::CACHE_ALL);
    }
}