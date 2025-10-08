<?php

namespace App\Services;

use App\Models\AccreditationCycle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class AccreditationCycleService
{
    /**
     * Obtener todos los ciclos de acreditación.
     */
    public function getAll()
    {
        // Retorna todos los registros (puedes aplicar filtros o scopes si los agregas luego)
        return AccreditationCycle::orderBy('ciclo_acreditacion_id', 'desc')->get();
    }

    /**
     * Obtener un ciclo por su ID.
     *
     * @throws ModelNotFoundException
     */
    public function getById(int $id)
    {
        return AccreditationCycle::findOrFail($id);
    }

    /**
     * Crear un nuevo ciclo de acreditación.
     */
    public function create(array $data): AccreditationCycle
    {
        try {
            $cycle = AccreditationCycle::create($data);

            Log::info(' Ciclo de acreditación creado', [
                'id' => $cycle->ciclo_acreditacion_id,
                'nombre' => $cycle->nombre,
            ]);

            return $cycle;
        } catch (\Exception $e) {
            Log::error(' Error al crear ciclo de acreditación: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar un ciclo existente.
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): AccreditationCycle
    {
        try {
            $cycle = AccreditationCycle::findOrFail($id);
            $cycle->update($data);

            Log::info(' Ciclo de acreditación actualizado', [
                'id' => $cycle->ciclo_acreditacion_id,
                'nombre' => $cycle->nombre,
            ]);

            return $cycle;
        } catch (ModelNotFoundException $e) {
            Log::warning(' Intento de actualizar un ciclo inexistente: ' . $id);
            throw $e;
        } catch (\Exception $e) {
            Log::error(' Error al actualizar ciclo de acreditación: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Eliminar un ciclo de acreditación.
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        try {
            $cycle = AccreditationCycle::findOrFail($id);
            $cycle->delete();

            Log::info('🗑 Ciclo de acreditación eliminado', [
                'id' => $id,
                'nombre' => $cycle->nombre,
            ]);

            return true;
        } catch (ModelNotFoundException $e) {
            Log::warning(' Intento de eliminar un ciclo inexistente: ' . $id);
            throw $e;
        } catch (\Exception $e) {
            Log::error(' Error al eliminar ciclo de acreditación: ' . $e->getMessage());
            throw $e;
        }
    }
}
