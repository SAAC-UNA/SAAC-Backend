<?php

namespace App\Services;

use App\Models\EvidenceState;

class EvidenceStateService
{
    public function getAll()
    {
        return EvidenceState::orderBy('nombre')->get();
    }

    public function findById(int $id): ?EvidenceState
    {
        return EvidenceState::find($id);
    }

    public function create(array $data): EvidenceState
    {
        return EvidenceState::create($data);
    }

    public function update(EvidenceState $estado, array $data): EvidenceState
    {
        $estado->update($data);
        return $estado;
    }

    public function delete(EvidenceState $estado): void
    {
        $estado->delete();
    }
}
