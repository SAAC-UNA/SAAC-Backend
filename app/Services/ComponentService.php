<?php

namespace App\Services;

use App\Models\Component;

class ComponentService
{
    public function getAll()
    {
        return Component::with(['dimension','comment'])
            ->orderBy('nombre')
            ->get();
    }

    public function findById(int $id): ?Component
    {
        return Component::with(['dimension','comment'])->find($id);
    }

    public function create(array $data): Component
    {
        return Component::create($data);
    }

    public function update(Component $component, array $data): Component
    {
        $component->fill($data)->save();
        return $component->load(['dimension','comment']);
    }

    public function delete(Component $component): void
    {
        $component->delete();
    }
}
