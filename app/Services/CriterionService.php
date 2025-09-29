<?php

namespace App\Services;

use App\Models\Criterion;

class CriterionService
{
    public function getAll()
    {
        // Igual que tu index: sin with()
        return Criterion::orderBy('nomenclatura')->get();
    }

    public function findById(int $id): ?Criterion
    {
        // Igual que tu show: sin with()
        return Criterion::find($id);
    }

    public function create(array $data): Criterion
    {
        return Criterion::create($data);
    }

    public function update(Criterion $criterion, array $data): Criterion
    {
        $criterion->fill($data)->save();
        return $criterion;
    }

    public function delete(Criterion $criterion): void
    {
        $criterion->delete();
    }
}
