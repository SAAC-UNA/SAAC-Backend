<?php

namespace App\Services;

use App\Models\AccreditationCycle;

class AccreditationCycleService
{
    /**
     * Obtener todos los ciclos
     * El filtro por carrera_sede se aplica automáticamente via BaseCareer
     */
    public function getAll(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 15;

        return AccreditationCycle::with('careerCampus', 'modeloEstructura')->paginate($perPage);
    }

    /**
     * Buscar ciclo por ID
     */
    public function findById(int $id): ?AccreditationCycle
    {
        return AccreditationCycle::with('careerCampus', 'modeloEstructura')->find($id);
    }

    /**
     * Crear un nuevo ciclo
     */
    public function create(array $data): AccreditationCycle
    {
        $cycle = AccreditationCycle::create([
            'carrera_sede_id'      => $data['carrera_sede_id'],
            'modelo_estructura_id' => $data['modelo_estructura_id'],
            'nombre'               => $data['nombre'],
            'estado'               => $data['estado'] ?? AccreditationCycle::STATUS_ACTIVE,
        ]);

        return $cycle->load(['careerCampus', 'modeloEstructura']);
    }

    /**
     * Actualizar un ciclo existente
     * AC-4: Solo se puede editar si está activo
     */
    public function update(AccreditationCycle $cycle, array $data): AccreditationCycle
    {
        // quehace este metodo? AC-4: Solo se puede editar si el ciclo está activo
        $cycle->update([
            'carrera_sede_id'      => $data['carrera_sede_id']      ?? $cycle->carrera_sede_id,
            'modelo_estructura_id' => $data['modelo_estructura_id'] ?? $cycle->modelo_estructura_id,
            'nombre'               => $data['nombre']               ?? $cycle->nombre,
            'estado'               => $data['estado']               ?? $cycle->estado,
        ]);

        return $cycle->fresh(['careerCampus', 'modeloEstructura']);
    }

    /**
     * Eliminar un ciclo
     * AC-4: No se puede eliminar si tiene procesos asociados
     */
    public function delete(AccreditationCycle $cycle): void
    {
        if ($cycle->processes()->exists()) {
            throw new \Exception('No se puede eliminar un ciclo que tiene procesos asociados.');
        }

        $cycle->delete();
    }
    
}