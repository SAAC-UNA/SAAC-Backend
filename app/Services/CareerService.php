<?php

namespace App\Services;

use App\Models\Career;

class CareerService
{
    /**
     * Lista carreras con filtro opcional por facultad_id.
     */
    public function getAll(?int $facultadId = null)
    {
        $q = Career::query()
            ->with('faculty')
            ->orderBy('nombre');

        if (!is_null($facultadId)) {
            $q->where('facultad_id', $facultadId);
        }

        return $q->get();
    }

    public function findById(int $id): ?Career
    {
        return Career::with('faculty')->find($id);
    }

    public function create(array $data): Career
    {
        return Career::create($data);
    }

    public function update(Career $career, array $data): Career
    {
        $career->update($data);
        return $career;
    }

    public function delete(Career $career): void
    {
        $career->delete();
    }
}
