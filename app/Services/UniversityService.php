<?php

namespace App\Services;

use App\Models\University;

class UniversityService
{
    /**
     * Lista todas las universidades ordenadas por nombre.
     */
    public function getAll()
    {
        return University::orderBy('nombre')->get();
    }

    // Lógica para show
    public function findById($id)
    {
    return University::find($id);
    }
    //Store
    public function create(array $data): University
    {
        return University::create($data);
    }

    public function update(University $university, array $data): University
    {
    $university->update($data);
    return $university;
    }

    //delete
    public function delete(University $university): void
    {
        // Delegamos la eliminación directa al modelo
        $university->delete();
    }



}