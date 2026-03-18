<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Support\Facades\Cache;

class ComponentService
{
    private const CACHE_KEY = 'componentes.all';
    private const CACHE_TTL = 300;

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            Component::with('dimension')->orderBy('nombre')->get()
        );
    }

    public function findById(int $id): ?Component
    {
        return Component::with('dimension')->find($id);
    }

    public function create(array $data): Component
    {
        $component = Component::create([
            'dimension_id' => $data['dimension_id'],
            'nombre'       => $data['nombre'],
            'nomenclatura' => $data['nomenclatura'],
            'activo'       => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $component->load('dimension');
    }

    public function update(Component $component, array $data): Component
    {
        $component->update([
            'dimension_id' => $data['dimension_id'] ?? $component->dimension_id,
            'nombre'       => $data['nombre']       ?? $component->nombre,
            'nomenclatura' => $data['nomenclatura'] ?? $component->nomenclatura,
            'activo'       => $data['activo']       ?? $component->activo,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $component->fresh('dimension');
    }

    public function delete(Component $component): void
    {
        $component->delete();
        Cache::forget(self::CACHE_KEY);
    }
}