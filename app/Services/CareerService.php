<?php

namespace App\Services;

use App\Models\Career;

class CareerService
{
    /**
     * Lista todas las carreras con sus campus asociados.
     */
    public function getAll()
    {
        return Career::query()
            ->with('campuses')
            ->orderBy('nombre')
            ->get();
    }

    public function findById(int $id): ?Career
    {
        return Career::with('campuses')->find($id);
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
