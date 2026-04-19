<?php

namespace App\Services;

use App\Events\ElementAssigned;
use App\Models\ElementCommitment;
use App\Models\ElementAssignment;
use App\Models\StructureElement;
use App\Models\Process;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $status     = $filters['estado']     ?? null;
        $processId  = $filters['proceso_id'] ?? null;
        $elementId  = $filters['elemento_id'] ?? null;
        $userId     = $filters['usuario_id'] ?? null;

        return ElementCommitment::with(['process', 'assignedElements.element', 'assignedElements.user'])
            ->when($search, fn($query) => $query->where('descripcion', 'like', "%{$search}%"))
            ->when($status, fn($query) => $query->where('estado', $status))
            ->when($processId, fn($query) => $query->where('proceso_id', $processId))
            ->when($elementId, function ($query) use ($elementId) {
                // Compromisos que tienen asignaciones del elemento o sus descendientes
                $allIds = $this->collectDescendantIds($elementId);
                $query->whereHas('assignedElements', fn($subQuery) =>
                    $subQuery->whereIn('ELEMENTO_ASIGNACION.elemento_id', $allIds)
                );
            })
            ->when($userId, fn($query) => $query->whereHas('assignedElements', fn($subQuery) =>
                $subQuery->where('ELEMENTO_ASIGNACION.usuario_id', $userId)
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
            'assignedElements.user',
        ])->find($id);
    }

    /**
     * Compromisos donde un usuario tiene asignaciones vinculadas.
     */
    public function getCommitmentsByUser(int $userId)
    {
        return $this->listCommitments(100, ['usuario_id' => $userId]);
    }

    /**
     * Compromisos vinculados a un elemento (o cualquiera de sus descendientes).
     */
    public function getCommitmentsByElement(int $elementId)
    {
        $allIds = $this->collectDescendantIds($elementId);

        return ElementCommitment::with(['process', 'assignedElements.element'])
            ->whereHas('assignedElements', fn($query) =>
                $query->whereIn('ELEMENTO_ASIGNACION.elemento_id', $allIds)
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
            $processId  = $data['proceso_id'];
            $elementId  = $data['elemento_id'];

            if (!Process::find($processId)) {
                throw ValidationException::withMessages([
                    'proceso_id' => 'El proceso especificado no existe.',
                ]);
            }

            if (!StructureElement::find($elementId)) {
                throw ValidationException::withMessages([
                    'elemento_id' => 'El elemento especificado no existe.',
                ]);
            }

            $commitment = ElementCommitment::create([
                'proceso_id'   => $processId,
                'descripcion'  => $data['descripcion'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin'    => $data['fecha_fin'],
                'estado'       => $data['estado'] ?? 'Pendiente',
                'activo'       => true,
            ]);

            if (!empty($data['elementos_asignar'])) {
                $descendantIds = $this->collectDescendantIds($elementId);
                $this->createElementAssignments($commitment, $data['elementos_asignar'], $descendantIds, $processId);
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
                $processId     = $data['proceso_id'] ?? $commitment->proceso_id;
                $elementId     = $data['elemento_id'] ?? null;
                $descendantIds = $elementId ? $this->collectDescendantIds($elementId) : [];
                $this->syncElementAssignments($commitment, $data['elementos_asignar'], $descendantIds, $processId);
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


    public function setActive(ElementCommitment $commitment, bool $isActive): ElementCommitment
    {
        $commitment->update(['activo' => $isActive ? 1 : 0]);
        $commitment->refresh();
        return $commitment;
    }


    /**
     * Recolecta recursivamente el ID del elemento raíz + todos sus descendientes activos.
     * Reutiliza la misma lógica de ElementApprovalService.
     *
     * @param int $elementId
     * @return int[]
     */
    private function collectDescendantIds(int $elementId): array
    {
        $ids      = [$elementId];
        $children = StructureElement::where('padre_id', $elementId)
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
        array $treeElementIds,
        int $processId
    ): void {
        foreach ($assignmentsData as $assignment) {
            $elementId  = $assignment['elemento_id'];
            $deadline   = $assignment['fecha_limite'] ?? null;
            $comment    = $assignment['comentario']   ?? null;
            $users      = $assignment['usuarios']     ?? [];

            if (!empty($treeElementIds) && !in_array($elementId, $treeElementIds)) {
                throw ValidationException::withMessages([
                    'elementos_asignar' => "El elemento con ID {$elementId} no pertenece al árbol del elemento raíz.",
                ]);
            }

            foreach ($users as $userId) {
                $newAssignment = ElementAssignment::where('proceso_id', $processId)
                    ->where('elemento_id', $elementId)
                    ->where('usuario_id', $userId)
                    ->first();

                if (!$newAssignment) {
                    $newAssignment = ElementAssignment::create([
                        'elemento_id'  => $elementId,
                        'usuario_id'   => $userId,
                        'proceso_id'   => $processId,
                        'asignado_por' => Auth::id(),
                        'estado'       => ElementAssignment::ESTADO_PENDIENTE,
                        'fecha_limite' => $deadline,
                        'comentario'   => $comment,
                    ]);

                    $this->emitElementAssignedEvent($newAssignment);
                } elseif ($deadline) {
                    $newAssignment->update(['fecha_limite' => $deadline, 'comentario' => $comment]);
                }

                $this->clearElementAssignmentCaches($newAssignment);

                $commitment->assignedElements()->attach($newAssignment->elemento_asignacion_id, [
                    'comentario' => $comment,
                ]);
            }

            // Asignar a todos los usuarios que tienen los roles indicados
            $roles = $assignment['roles'] ?? [];
            foreach ($roles as $roleId) {
                $role = Role::find($roleId);
                if (!$role) {
                    throw ValidationException::withMessages([
                        'elementos_asignar' => "El rol con ID {$roleId} no existe.",
                    ]);
                }

                $usersWithRole = User::role($role->name)->active()->get();

                foreach ($usersWithRole as $userObj) {
                    if (ElementAssignment::where('proceso_id', $processId)
                            ->where('elemento_id', $elementId)
                            ->where('usuario_id', $userObj->usuario_id)
                            ->exists()) {
                        continue; // Duplicado — saltar sin error
                    }

                    $newAssignment = ElementAssignment::create([
                        'elemento_id'  => $elementId,
                        'usuario_id'   => $userObj->usuario_id,
                        'proceso_id'   => $processId,
                        'asignado_por' => Auth::id(),
                        'estado'       => ElementAssignment::ESTADO_PENDIENTE,
                        'fecha_limite' => $deadline,
                        'comentario'   => $comment,
                    ]);

                    $this->emitElementAssignedEvent($newAssignment);
                    $this->clearElementAssignmentCaches($newAssignment);

                    $commitment->assignedElements()->attach($newAssignment->elemento_asignacion_id, [
                        'comentario' => $comment,
                    ]);
                }
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
        array $treeElementIds,
        int $processId
    ): void {
        // VALIDACIÓN 1: No permitir quitar elementos con asignaciones activas
        $currentElementIds = $commitment->assignedElements()
            ->pluck('ELEMENTO_ASIGNACION.elemento_id')
            ->unique()
            ->toArray();

        $newElementIds = collect($assignmentsData)->pluck('elemento_id')->unique()->toArray();
        $elementsToRemove = array_diff($currentElementIds, $newElementIds);

        if (!empty($elementsToRemove)) {
            $activeAssignments = ElementAssignment::whereIn('elemento_id', $elementsToRemove)
                ->whereIn('estado', ['En Progreso', 'Completado'])
                ->with('element:elemento_id,nombre,nomenclatura')
                ->first();

            if ($activeAssignments) {
                $elementName = $activeAssignments->element->nomenclatura ?? $activeAssignments->element->nombre;
                throw new \Exception(
                    "No se puede quitar el elemento '{$elementName}' del compromiso " .
                    "porque tiene asignaciones en estado 'En Progreso' o 'Completado'."
                );
            }
        }

        // VALIDACIÓN 2: No permitir quitar asignaciones individuales (usuario+elemento) en estado activo
        $currentAssignmentIds = $commitment->assignedElements()->pluck('elemento_asignacion_id')->toArray();
        $newAssignmentIds = $this->collectNewAssignmentIds($assignmentsData, $processId);
        $assignmentsToRemove = array_diff($currentAssignmentIds, $newAssignmentIds);

        if (!empty($assignmentsToRemove)) {
            $activeAssignment = ElementAssignment::whereIn('elemento_asignacion_id', $assignmentsToRemove)
                ->whereIn('estado', ['En Progreso', 'Completado'])
                ->with(['element:elemento_id,nombre,nomenclatura', 'user:usuario_id,nombre'])
                ->first();

            if ($activeAssignment) {
                $elementName = $activeAssignment->element->nomenclatura ?? $activeAssignment->element->nombre;
                $userName = $activeAssignment->user->nombre ?? 'usuario';
                throw new \Exception(
                    "No se puede quitar la asignación de '{$userName}' al elemento '{$elementName}' " .
                    "porque ya está en estado '{$activeAssignment->estado}'."
                );
            }
        }

        // Desvincular todas las asignaciones actuales
        $commitment->assignedElements()->detach();

        if (empty($assignmentsData)) {
            return;
        }

        foreach ($assignmentsData as $assignment) {
            $elementId  = $assignment['elemento_id'];
            $deadline   = $assignment['fecha_limite'] ?? null;
            $comment    = $assignment['comentario']   ?? null;
            $users      = $assignment['usuarios']     ?? [];

            if (!empty($treeElementIds) && !in_array($elementId, $treeElementIds)) {
                throw ValidationException::withMessages([
                    'elementos_asignar' => "El elemento con ID {$elementId} no pertenece al árbol del elemento raíz.",
                ]);
            }

            foreach ($users as $userId) {
                $existing = ElementAssignment::where('proceso_id', $processId)
                    ->where('elemento_id', $elementId)
                    ->where('usuario_id', $userId)
                    ->first();

                if (!$existing) {
                    $existing = ElementAssignment::create([
                        'elemento_id'  => $elementId,
                        'usuario_id'   => $userId,
                        'proceso_id'   => $processId,
                        'asignado_por' => Auth::id(),
                        'estado'       => ElementAssignment::ESTADO_PENDIENTE,
                        'fecha_limite' => $deadline,
                        'comentario'   => $comment,
                    ]);

                    $this->emitElementAssignedEvent($existing);
                } elseif ($deadline) {
                    $existing->update(['fecha_limite' => $deadline]);
                }

                $this->clearElementAssignmentCaches($existing);

                $commitment->assignedElements()->attach($existing->elemento_asignacion_id, [
                    'comentario' => $comment,
                ]);
            }

            // Asignar a todos los usuarios que tienen los roles indicados
            $roles = $assignment['roles'] ?? [];
            foreach ($roles as $roleId) {
                $role = Role::find($roleId);
                if (!$role) {
                    throw ValidationException::withMessages([
                        'elementos_asignar' => "El rol con ID {$roleId} no existe.",
                    ]);
                }

                $usersWithRole = User::role($role->name)->active()->get();

                foreach ($usersWithRole as $userObj) {
                    $existing = ElementAssignment::where('proceso_id', $processId)
                        ->where('elemento_id', $elementId)
                        ->where('usuario_id', $userObj->usuario_id)
                        ->first();

                    if (!$existing) {
                        $existing = ElementAssignment::create([
                            'elemento_id'  => $elementId,
                            'usuario_id'   => $userObj->usuario_id,
                            'proceso_id'   => $processId,
                            'asignado_por' => Auth::id(),
                            'estado'       => ElementAssignment::ESTADO_PENDIENTE,
                            'fecha_limite' => $deadline,
                        'comentario'   => $comment,
                    ]);

                    $this->emitElementAssignedEvent($existing);
                    } elseif ($deadline) {
                        $existing->update(['fecha_limite' => $deadline]);
                    }

                    $this->clearElementAssignmentCaches($existing);

                    $commitment->assignedElements()->attach($existing->elemento_asignacion_id, [
                        'comentario' => $comment,
                    ]);
                }
            }
        }
    }

    /**
     * Dispara el evento de asignación para crear la notificación al responsable.
     */
    private function emitElementAssignedEvent(ElementAssignment $assignment): void
    {
        $assignment->loadMissing(['element', 'user', 'process', 'assignedBy']);
        event(new ElementAssigned($assignment));
    }

    /**     * Recolecta IDs de asignaciones que se mantendrán después de la sincronización.
     */
    private function collectNewAssignmentIds(array $assignmentsData, int $processId): array
    {
        $assignmentIds = [];
        
        foreach ($assignmentsData as $assignment) {
            $elementId = $assignment['elemento_id'];
            $users = $assignment['usuarios'] ?? [];
            
            foreach ($users as $userId) {
                $existing = ElementAssignment::where('proceso_id', $processId)
                    ->where('elemento_id', $elementId)
                    ->where('usuario_id', $userId)
                    ->value('elemento_asignacion_id');
                
                if ($existing) {
                    $assignmentIds[] = $existing;
                }
            }

            $roles = $assignment['roles'] ?? [];
            foreach ($roles as $roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $usersWithRole = User::role($role->name)->active()->pluck('usuario_id');
                    $existingIds = ElementAssignment::where('proceso_id', $processId)
                        ->where('elemento_id', $elementId)
                        ->whereIn('usuario_id', $usersWithRole)
                        ->pluck('elemento_asignacion_id')
                        ->toArray();
                    
                    $assignmentIds = array_merge($assignmentIds, $existingIds);
                }
            }
        }
        
        return array_unique($assignmentIds);
    }

    /**     * Limpia cachés de listados para que “Mis Entregas” refleje cambios inmediatamente.
     */
    private function clearElementAssignmentCaches(ElementAssignment $assignment): void
    {
        Cache::forget('element-assignments.all');
        Cache::forget("element-assignments.user.{$assignment->usuario_id}");
        Cache::forget("element-assignments.element.{$assignment->elemento_id}");
        Cache::forget("element-assignments.process.{$assignment->proceso_id}");
    }
}
