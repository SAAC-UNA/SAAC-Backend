<?php

namespace App\Services;

use App\Models\Faculty;

class FacultyService
{
    /**
     * Lista facultades con filtros opcionales por universidad y sede.
     */
    public function getAll(?int $universidadId = null, ?int $sedeId = null)
    {
        $q = Faculty::query()
            ->with(['university','campus'])
            ->orderBy('nombre');

        if (!is_null($universidadId)) {
            $q->where('universidad_id', $universidadId);
        }
        if (!is_null($sedeId)) {
            $q->where('sede_id', $sedeId);
        }

        return $q->get();
    }

    public function findById(int $id): ?Faculty
    {
        return Faculty::with(['university','campus'])->find($id);
    }

    public function create(array $data): Faculty
    {
        return Faculty::create($data);
    }

    public function update(Faculty $faculty, array $data): Faculty
    {
        $faculty->update($data);
        return $faculty;
    }

    public function delete(Faculty $faculty): void
    {
        $faculty->delete();
    }
}
