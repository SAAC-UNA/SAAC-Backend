<?php

namespace App\Services;

use App\Models\Process;

class ProcessService
{
    /**
     *  Obtener todos los procesos con sus relaciones.
     */
    public function getAll()
    {
        return Process::with('accreditationCycle.careerCampus.career', 'accreditationCycle.careerCampus.campus')
            ->orderBy('proceso_id', 'asc')
            ->get();
    }

    /**
     *  Obtener un proceso por su ID con relaciones completas.
     */
    public function getById($id)
    {
        return Process::with('accreditationCycle.careerCampus.career', 'accreditationCycle.careerCampus.campus')
            ->findOrFail($id);
    }

    /**
     * ➕ Crear un nuevo proceso.
     */
    public function create(array $data)
    {
        return Process::create($data);
    }

    /**
     *  Actualizar un proceso existente.
     */
    public function update($id, array $data)
    {
        $proceso = Process::findOrFail($id);
        $proceso->update($data);
        return $proceso;
    }

    /**
     * 🗑 Eliminar un proceso.
     */
    public function delete($id)
    {
        $proceso = Process::findOrFail($id);
        $proceso->delete();
        return true;
    }
}
