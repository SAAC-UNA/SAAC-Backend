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

        return AccreditationCycle::with('careerCampus.career', 'careerCampus.campus', 'modeloEstructura')->paginate($perPage);
    }

    /**
     * Buscar ciclo por ID
     */
    public function findById(int $id): ?AccreditationCycle
    {
        return AccreditationCycle::with('careerCampus.career', 'careerCampus.campus', 'modeloEstructura')->find($id);
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

        return $cycle->load(['careerCampus.career', 'careerCampus.campus', 'modeloEstructura']);
    }

    /**
     * Actualizar un ciclo existente.
     * Solo actualiza los campos que vienen en $data; los demás conservan su valor actual.
     * La restricción AC-4 (solo editable si activo) se aplica en la Policy antes de llegar aquí.
     *
     * HU-030 (modelo flexible) — Protección de coherencia:
     * Si se intenta cambiar modelo_estructura_id en un ciclo que ya tiene procesos,
     * se lanza una excepción. El motivo: los procesos ya existentes tienen evidencias
     * ancladas al modelo anterior (criterio_id o elemento_id). Cambiar el modelo
     * dejaría esas evidencias en un estado inconsistente con el nuevo tipo de árbol.
     */
    public function update(AccreditationCycle $cycle, array $data): AccreditationCycle
    {
        $nuevoModelo = $data['modelo_estructura_id'] ?? null;

        if ($nuevoModelo && (int)$nuevoModelo !== (int)$cycle->modelo_estructura_id) {
            if ($cycle->processes()->exists()) {
                throw new \InvalidArgumentException(
                    'No se puede cambiar el modelo de estructura de un ciclo que ya tiene procesos asociados. ' .
                    'El ciclo contiene evidencias vinculadas al modelo actual y cambiar el tipo generaría inconsistencias.'
                );
            }
        }

        $cycle->update([
            'carrera_sede_id'      => $data['carrera_sede_id']      ?? $cycle->carrera_sede_id,
            'modelo_estructura_id' => $nuevoModelo                  ?? $cycle->modelo_estructura_id,
            'nombre'               => $data['nombre']               ?? $cycle->nombre,
            'estado'               => $data['estado']               ?? $cycle->estado,
        ]);

        return $cycle->fresh(['careerCampus.career', 'careerCampus.campus', 'modeloEstructura']);
    }

    /**
     * Eliminar un ciclo.
     * Lanza una excepción si el ciclo tiene procesos asociados (integridad referencial).
     * Nota: en la práctica este método no se usa porque la Policy bloquea el DELETE físico.
     */
    public function delete(AccreditationCycle $cycle): void
    {
        if ($cycle->processes()->exists()) {
            throw new \InvalidArgumentException('No se puede eliminar un ciclo que tiene procesos asociados.');
        }

        $cycle->delete();
    }
    
}