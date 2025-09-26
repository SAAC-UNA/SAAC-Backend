<?php

namespace App\Services;

use App\Models\Campus;

class CampusService
{
    /**
     * Lista campus ordenados por nombre, con filtro opcional por universidad_id.
     */
    public function getAll(?int $universidadId = null)
    {
        $q = Campus::query()->with('university')->orderBy('nombre');

        if (!is_null($universidadId)) {
            $q->where('universidad_id', $universidadId);
        }

        return $q->get();
    }

    public function findById(int $id): ?Campus
    {
        return Campus::with('university')->find($id);
    }

    public function create(array $data): Campus
    {
        return Campus::create($data);
    }

    public function update(Campus $campus, array $data): Campus
    {
        $campus->update($data);
        return $campus;
    }

    public function delete(Campus $campus): void
    {
        $campus->delete();
    }
}
