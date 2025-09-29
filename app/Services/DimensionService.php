<?php

namespace App\Services;

use App\Models\Dimension;

class DimensionService
{
    public function getAll()
    {
        return Dimension::orderBy('nomenclatura')->get();
    }

    public function findById(int $id): ?Dimension
    {
        return Dimension::find($id);
    }

    public function create(array $data): Dimension
    {
        return Dimension::create($data);
    }

    public function update(Dimension $dimension, array $data): Dimension
    {
        $dimension->update($data);
        return $dimension;
    }

    public function delete(Dimension $dimension): void
    {
        $dimension->delete();
    }
}
