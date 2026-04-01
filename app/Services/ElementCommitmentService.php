<?php

namespace App\Services;

use App\Models\ElementCommitment;
use App\Models\ElementAssignment;
use App\Models\StructureElement;
use App\Models\Process;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de Compromisos de Mejora — modelo flexible (ELEMENTO).
 * Tabla principal: COMPROMISO_MEJORA_ELEMENTO.
 * Pivot: COMPROMISO_MEJORA_ELEMENTO_ASIGNACION.
 *
 * Equivalente a ImprovementCommitmentService pero usando el árbol ELEMENTO
 * en lugar de EVIDENCIA/CRITERIO del modelo tradicional.
 *
 * CASCADE: al crear/actualizar con un elemento_id, el servicio recolecta
 * automáticamente todos los descendientes activos (collectDescendantIds)
 * y vincula sus ELEMENTO_ASIGNACION activas al compromiso.
 */
class ElementCommitmentService
{
    
    /**
     * Lista compromisos de mejora (modelo flexible) con paginación y filtros.
     *
     * @param int   $perPage
     * @param array $filters  [search, estado, proceso_id, elemento_id, usuario_id]
     */
    public function listCommitments(int $perPage = 10, array $filters = [])
    {
        $search     = $filters['search']     ?? null;
        $estado     = $filters['estado']     ?? null;
        $procesoId  = $filters['proceso_id'] ?? null;
        $elementoId = $filters['elemento_id'] ?? null;
        $usuarioId  = $filters['usuario_id'] ?? null;

        return ElementCommitment::with(['process', 'assignedElements.element'])
            ->when($search, fn($q) => $q->where('descripcion', 'like', "%{$search}%"))
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->when($procesoId, fn($q) => $q->where('proceso_id', $procesoId))
            ->when($elementoId, function ($q) use ($elementoId) {
                // Compromisos que tienen asignaciones del elemento o sus descendientes
                $allIds = $this->collectDescendantIds($elementoId);
                $q->whereHas('assignedElements', fn($sub) =>
                    $sub->whereIn('ELEMENTO_ASIGNACION.elemento_id', $allIds)
                );
            })
            ->when($usuarioId, fn($q) => $q->whereHas('assignedElements', fn($sub) =>
                $sub->where('ELEMENTO_ASIGNACION.usuario_id', $usuarioId)
            ))
            ->paginate($perPage);
    }

    /**
     * Obtiene un compromiso específico por ID, con sus relaciones cargadas.
     */
    public function getCommitment(int $id): ?ElementCommitment
    {
        return ElementCommitment::with([
            'process',
            'assignedElements.element',
        ])->find($id);
    }

    /**
     * Compromisos donde un usuario tiene asignaciones vinculadas.
     */
    public function getCommitmentsByUser(int $usuarioId)
    {
        return $this->listCommitments(100, ['usuario_id' => $usuarioId]);
    }

    /**
     * Compromisos vinculados a un elemento (o cualquiera de sus descendientes).
     */
    public function getCommitmentsByElemento(int $elementoId)
    {
        $allIds = $this->collectDescendantIds($elementoId);

        return ElementCommitment::with(['process', 'assignedElements.element'])
            ->whereHas('assignedElements', fn($q) =>
                $q->whereIn('ELEMENTO_ASIGNACION.elemento_id', $allIds)
            )
            ->paginate(15);
    }

    

    /**
     * Crea un compromiso de mejora y crea las asignaciones de elementos dentro del compromiso.
     *
     * @param array $data Datos validados de ElementCommitmentRequest.
     * @return ElementCommitment El compromiso recién creado con relaciones cargadas.
     * @throws ValidationException
     */
    public function createCommitment(array $data): ElementCommitment
    {
        return DB::transaction(function () use ($data) {
            $procesoId  = $data['proceso_id'];
            $elementoId = $data['elemento_id'];

            if (!Process::find($procesoId)) {
                throw ValidationException::withMessages([
                    'proceso_id' => 'El proceso especificado no existe.',
                ]);
            }

            if (!StructureElement::find($elementoId)) {
                throw ValidationException::withMessages([
                    'elemento_id' => 'El elemento especificado no existe.',
                ]);
            }

            $commitment = ElementCommitment::create([
                'proceso_id'   => $procesoId,
                'descripcion'  => $data['descripcion'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin'    => $data['fecha_fin'],
                'estado'       => $data['estado'] ?? 'Pendiente',
                'activo'       => true,
            ]);

            if (!empty($data['elementos_asignar'])) {
                $arbolIds = $this->collectDescendantIds($elementoId);
                $this->createElementAssignments($commitment, $data['elementos_asignar'], $arbolIds, $procesoId);
            }

            return $this->getCommitment($commitment->compromiso_elemento_id);
        });
    }

    
    /**
     * Actualiza un compromiso existente. Solo aplica cambios detectados.
     */
    public function updateCommitment(ElementCommitment $commitment, array $data): ?ElementCommitment
    {
        return DB::transaction(function () use ($commitment, $data) {
            $hasChanges = false;
            $changes    = [];

            if (isset($data['proceso_id']) && $data['proceso_id'] !== $commitment->proceso_id) {
                $changes['proceso_id'] = $data['proceso_id'];
                $hasChanges = true;
            }

            if (isset($data['descripcion']) && $data['descripcion'] !== $commitment->descripcion) {
                $changes['descripcion'] = $data['descripcion'];
                $hasChanges = true;
            }

            $currentFechaFin = $commitment->fecha_fin?->format('Y-m-d');
            if (isset($data['fecha_fin']) && $data['fecha_fin'] !== $currentFechaFin) {
                $changes['fecha_fin'] = $data['fecha_fin'];
                $hasChanges = true;
            }

            $currentFechaInicio = $commitment->fecha_inicio?->format('Y-m-d');
            if (isset($data['fecha_inicio']) && $data['fecha_inicio'] !== $currentFechaInicio) {
                $changes['fecha_inicio'] = $data['fecha_inicio'];
                $hasChanges = true;
            }

            if (isset($data['estado']) && $data['estado'] !== $commitment->estado) {
                $changes['estado'] = $data['estado'];
                $hasChanges = true;
            }

            if (isset($data['elementos_asignar'])) {
                $procesoId  = $data['proceso_id'] ?? $commitment->proceso_id;
                $elementoId = $data['elemento_id'] ?? null;
                $arbolIds   = $elementoId ? $this->collectDescendantIds($elementoId) : [];
                $this->syncElementAssignments($commitment, $data['elementos_asignar'], $arbolIds, $procesoId);
                $hasChanges = true;
            }

            if (!$hasChanges) {
                return null;
            }

            if (!empty($changes)) {
                $commitment->update($changes);
            }

            return $this->getCommitment($commitment->compromiso_elemento_id);
        });
    }


    public function setActive(ElementCommitment $commitment, bool $activo): ElementCommitment
    {
        $commitment->update(['activo' => $activo ? 1 : 0]);
        $commitment->refresh();
        return $commitment;
    }


    /**
     * Recolecta recursivamente el ID del elemento raíz + todos sus descendientes activos.
     * Reutiliza la misma lógica de ElementApprovalService.
     *
     * @param int $elementoId
     * @return int[]
     */
    private function collectDescendantIds(int $elementoId): array
    {
        $ids      = [$elementoId];
        $children = StructureElement::where('padre_id', $elementoId)
            ->where('activo', true)
            ->pluck('elemento_id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->collectDescendantIds($childId));
        }

        return $ids;
    }

    /**
     * Crea ElementAssignment por cada usuario de cada elemento y los vincula al pivot del compromiso.
     * Solo crea asignaciones para elementos que pertenecen al árbol del elemento raíz.
     */
    private function createElementAssignments(
        ElementCommitment $commitment,
        array $assignmentsData,
        array $arbolElementIds,
        int $procesoId
    ): void {
        foreach ($assignmentsData as $assignment) {
            $elementoId  = $assignment['elemento_id'];
            $fechaLimite = $assignment['fecha_limite'] ?? null;
            $comentario  = $assignment['comentario']   ?? null;
            $usuarios    = $assignment['usuarios']     ?? [];

            if (!empty($arbolElementIds) && !in_array($elementoId, $arbolElementIds)) {
                throw ValidationException::withMessages([
                    'elementos_asignar' => "El elemento con ID {$elementoId} no pertenece al árbol del elemento raíz.",
                ]);
            }

            foreach ($usuarios as $usuarioId) {
                if (ElementAssignment::where('proceso_id', $procesoId)
                        ->where('elemento_id', $elementoId)
                        ->where('usuario_id', $usuarioId)
                        ->exists()) {
                    throw ValidationException::withMessages([
                        'elementos_asignar' => "El elemento ID {$elementoId} ya está asignado al usuario ID {$usuarioId} en este proceso.",
                    ]);
                }

                $newAssignment = ElementAssignment::create([
                    'elemento_id'  => $elementoId,
                    'usuario_id'   => $usuarioId,
                    'proceso_id'   => $procesoId,
                    'estado'       => ElementAssignment::ESTADO_PENDIENTE,
                    'fecha_limite' => $fechaLimite,
                    'comentario'   => $comentario,
                ]);

                $commitment->assignedElements()->attach($newAssignment->elemento_asignacion_id, [
                    'comentario' => $comentario,
                ]);
            }
        }
    }

    /**
     * Reemplaza las asignaciones del compromiso (para UPDATE).
     * Desvincula el pivot existente; crea nuevas asignaciones o reutiliza las ya existentes.
     */
    private function syncElementAssignments(
        ElementCommitment $commitment,
        array $assignmentsData,
        array $arbolElementIds,
        int $procesoId
    ): void {
        $commitment->assignedElements()->detach();

        if (empty($assignmentsData)) {
            return;
        }

        foreach ($assignmentsData as $assignment) {
            $elementoId  = $assignment['elemento_id'];
            $fechaLimite = $assignment['fecha_limite'] ?? null;
            $comentario  = $assignment['comentario']   ?? null;
            $usuarios    = $assignment['usuarios']     ?? [];

            if (!empty($arbolElementIds) && !in_array($elementoId, $arbolElementIds)) {
                throw ValidationException::withMessages([
                    'elementos_asignar' => "El elemento con ID {$elementoId} no pertenece al árbol del elemento raíz.",
                ]);
            }

            foreach ($usuarios as $usuarioId) {
                $existing = ElementAssignment::where('proceso_id', $procesoId)
                    ->where('elemento_id', $elementoId)
                    ->where('usuario_id', $usuarioId)
                    ->first();

                if (!$existing) {
                    $existing = ElementAssignment::create([
                        'elemento_id'  => $elementoId,
                        'usuario_id'   => $usuarioId,
                        'proceso_id'   => $procesoId,
                        'estado'       => ElementAssignment::ESTADO_PENDIENTE,
                        'fecha_limite' => $fechaLimite,
                        'comentario'   => $comentario,
                    ]);
                } elseif ($fechaLimite) {
                    $existing->update(['fecha_limite' => $fechaLimite]);
                }

                $commitment->assignedElements()->attach($existing->elemento_asignacion_id, [
                    'comentario' => $comentario,
                ]);
            }
        }
    }
}
