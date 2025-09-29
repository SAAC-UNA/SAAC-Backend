<?php

namespace App\Services;

use App\Models\Standard;

class StandardService
{
    public function getAll()
    {
        return Standard::orderBy('estandar_id')->get();
    }

    public function findById(int $id): ?Standard
    {
        return Standard::find($id);
    }

    public function create(array $data): Standard
    {
        return Standard::create($data);
    }

    public function update(Standard $std, array $data): Standard
    {
        $std->update($data);
        return $std;
    }

    public function delete(Standard $std): void
    {
        $std->delete();
    }
}
