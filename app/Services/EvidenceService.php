<?php

namespace App\Services;

use App\Models\Evidence;

class EvidenceService
{
    public function getAll()
    {
        // Igual que tu index: sin with()
        return Evidence::orderBy('nomenclatura')->get();
    }

    public function findById(int $id): ?Evidence
    {
        return Evidence::find($id); // Igual que tu show
    }

    public function create(array $data): Evidence
    {
        return Evidence::create($data);
    }

    public function update(Evidence $evidence, array $data): Evidence
    {
        $evidence->fill($data)->save();
        return $evidence;
    }

    public function delete(Evidence $evidence): void
    {
        $evidence->delete();
    }
}
