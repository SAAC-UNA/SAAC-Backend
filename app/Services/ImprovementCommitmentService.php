<?php
namespace App\Services;

use App\Models\ImprovementCommitment;
use App\Models\User;
use App\Models\Standard;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio que gestiona la logica de negocio relacionada con Compromisos de Mejora.
 * Todas las operaciones de BD se realizan via stored procedures.
 * Las operaciones de pivot (vincular/desvincular) usan SPs especificos.
 * Spatie se mantiene solo para rol-lookup en asignaciones.
 */
class ImprovementCommitmentService
{
    /**
     * Listar compromisos de mejora con paginacion obligatoria y filtros dinamicos.
     *
     * @param int $perPage Cantidad de registros por pagina (default: 10).
     * @param array $filters Filtros opcionales: search, estado, proceso_id, usuario_id
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listCommitments(int $perPage = 10, array $filters = [])
    {
        $search    = $filters['search']    ?? null;
        $status    = $filters['estado']    ?? null;
        $cycleId   = $filters['ciclo_acreditacion_id'] ?? null;
        $processId = $filters['proceso_id'] ?? null;
        $userId    = $filters['usuario_id'] ?? null;

        return ImprovementCommitment::with([
            'process.accreditationCycle.careerCampus.career',
            'process.accreditationCycle.careerCampus.campus',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user',
            ])
            ->when($search, fn($query) => $query->where('descripcion', 'like', "%{$search}%"))
            ->when($status, fn($query) => $query->where('estado', $status))
            ->when($cycleId, fn($query) => $query->whereHas('process', fn($processQuery) => $processQuery->where('ciclo_acreditacion_id', $cycleId)))
            ->when($processId, fn($query) => $query->where('proceso_id', $processId))
            ->when($userId, fn($query) => $query->whereHas('assignedEvidences', fn($subQuery) => $subQuery->where('usuario_id', $userId)))
            ->paginate($perPage);
    }

    /**
     * Obtener un compromiso de mejora especifico por ID.
     *
     * @param int $id Identificador unico del compromiso.
     * @return ImprovementCommitment|null
     */
    public function getCommitment(int $id): ?ImprovementCommitment
    {
        return ImprovementCommitment::with([
            'process.accreditationCycle.careerCampus.career',
            'process.accreditationCycle.careerCampus.campus',
            'evidences.criterion.component.dimension',
            'evidences.criterion.standards',
            'assignedEvidences.evidence',
            'assignedEvidences.user',
        ])->find($id);
    }

    /**
     * Obtener compromisos de mejora donde un usuario especifico tiene asignaciones.
     *
     * @param int $userId ID del usuario.
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getCommitmentsByUser(int $userId)
    {
        return $this->listCommitments(100, ['usuario_id' => $userId]);
    }

    /**
     * Obtener compromisos de mejora donde una evidencia especifica esta asignada.
     *
     * @param int $evidenceId ID de la evidencia.
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getCommitmentsByEvidence(int $evidenceId)
    {
        return ImprovementCommitment::with([
                'process',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user',
            ])
            ->whereHas('evidences', fn($query) => $query->where('EVIDENCIA.evidencia_id', $evidenceId))
            ->paginate(15);
    }

    /**
     * Crear un nuevo compromiso de mejora con sus evidencias.
     *
     * @param array $data Datos validados del compromiso.
     * @return ImprovementCommitment|null Compromiso recien creado o null si ya existe.
     * @throws ValidationException Si las validaciones fallan.
     */
    public function createCommitment(array $data): ?ImprovementCommitment
    {
        return DB::transaction(function () use ($data) {
            // Validar selecciones (duplicados y que tengan evidencias)
            $this->validateSelections($data['selecciones']);

            // Obtener o crear proceso automaticamente
            $processId = $data['proceso_id'];

            // Validar que NO exista ya un compromiso en este proceso
            if (ImprovementCommitment::where('proceso_id', $processId)->exists()) {
                return null;
            }

            // Crear nuevo compromiso con estado inicial "Pendiente"
            $commitment = ImprovementCommitment::create([
                'proceso_id'   => $processId,
                'descripcion'  => $data['descripcion'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin'    => $data['fecha_fin'],
                'estado'       => 'Pendiente',
                'activo'       => 1,
            ]);

            $allNewEvidenceIds = [];

            // Procesar cada seleccion y obtener sus evidencias
            foreach ($data['selecciones'] as $selection) {
                $evidenceIds = $this->getEvidencesByEntity(
                    $selection['entidad_tipo'],
                    $selection['entidad_id'],
                    $processId
                );
                $allNewEvidenceIds = array_merge($allNewEvidenceIds, $evidenceIds);
            }

            // Vincular evidencias al compromiso (una sola operacion)
            $commitment->evidences()->sync(array_unique($allNewEvidenceIds));

            // Crear asignaciones de evidencias a usuarios si se proporcionan
            if (!empty($data['evidencias_asignar'])) {
                $this->createEvidenceAssignments($commitment, $data['evidencias_asignar'], $processId);
            }

            // Recargar desde BD (incluye load de relaciones via getCommitment)
            return $this->getCommitment($commitment->compromiso_mejora_id);
        });
    }

    /**
     * Actualizar un compromiso de mejora existente.
     *
     * @param ImprovementCommitment $commitment Compromiso a actualizar.
     * @param array $data Nuevos datos del compromiso.
     * @return ImprovementCommitment|null Compromiso actualizado, o null si no hubo cambios.
     * @throws ValidationException Si las validaciones fallan.
     */
    public function updateCommitment(ImprovementCommitment $commitment, array $data): ?ImprovementCommitment
    {
        return DB::transaction(function () use ($commitment, $data) {
            $hasChanges = false;

            // Validar selecciones si se proporcionan
            if (isset($data['selecciones'])) {
                $this->validateSelections($data['selecciones']);
            }

            // Determinar proceso_id para actualizacion
            $processId = $commitment->proceso_id;
            if (isset($data['proceso_id']) && $data['proceso_id'] !== $processId) {
                $processId  = $data['proceso_id'];
                $hasChanges = true;
            }

            // Detectar cambios en campos basicos
            $descripcion = null;
            $fechaFin    = null;
            $statusValue = null;

            if (isset($data['descripcion']) && $data['descripcion'] !== $commitment->descripcion) {
                $descripcion = $data['descripcion'];
                $hasChanges  = true;
            }
            $currentFechaFin = $commitment->fecha_fin?->format('Y-m-d');
            if (isset($data['fecha_fin']) && $data['fecha_fin'] !== $currentFechaFin) {
                $fechaFin   = $data['fecha_fin'];
                $hasChanges = true;
            }
            if (isset($data['estado']) && $data['estado'] !== $commitment->estado) {
                $statusValue = $data['estado'];
                $hasChanges = true;
            }

            // Procesar selecciones nuevas (reemplazar completamente las evidencias existentes)
            if (isset($data['selecciones'])) {
                $newEvidenceIds = [];
                foreach ($data['selecciones'] as $selection) {
                    $evidenceIds = $this->getEvidencesByEntity(
                        $selection['entidad_tipo'],
                        $selection['entidad_id'],
                        $processId
                    );
                    $newEvidenceIds = array_merge($newEvidenceIds, $evidenceIds);
                }
                $newEvidenceIds = array_unique($newEvidenceIds);

                // VALIDACIÓN: No permitir quitar evidencias con asignaciones en estado avanzado
                $currentEvidenceIds = $commitment->evidences()->pluck('evidencia_id')->toArray();
                $evidencesToRemove = array_diff($currentEvidenceIds, $newEvidenceIds);

                if (!empty($evidencesToRemove)) {
                    $activeAssignment = EvidenceAssignment::whereIn('evidencia_id', $evidencesToRemove)
                        ->whereIn('estado', ['En Progreso', 'Completado'])
                        ->with('evidence:evidencia_id,nomenclatura,descripcion')
                        ->first();

                    if ($activeAssignment) {
                        $evidenceName = $activeAssignment->evidence->nomenclatura;
                        throw new \Exception(
                            "No se puede quitar la evidencia '{$evidenceName}' del compromiso " .
                            "porque tiene asignaciones en estado 'En Progreso' o 'Completado'."
                        );
                    }
                }

                // Reemplazar completamente evidencias vinculadas
                $commitment->evidences()->sync($newEvidenceIds);
                $hasChanges = true;
            }

            // Retornar null si no hay cambios
            if (!$hasChanges) {
                return null;
            }

            // Aplicar cambios de campos basicos
            $changes = array_filter([
                'proceso_id'  => $processId !== $commitment->proceso_id ? $processId : null,
                'descripcion' => $descripcion,
                'fecha_fin'   => $fechaFin,
                'estado'      => $statusValue,
            ], fn($value) => $value !== null);
            if (!empty($changes)) {
                $commitment->update($changes);
            }

            // Reemplazar asignaciones si se proporcionan
            if (isset($data['evidencias_asignar'])) {
                $this->syncEvidenceAssignments($commitment, $data['evidencias_asignar'], $processId);
            }

            return $this->getCommitment($commitment->compromiso_mejora_id);
        });
    }

    /**
     * Activar o desactivar un compromiso de mejora.
     *
     * @param ImprovementCommitment $commitment Compromiso a modificar.
     * @param bool $isActive True para activar, false para desactivar.
     * @return ImprovementCommitment Compromiso actualizado.
     */
    public function setActive(ImprovementCommitment $commitment, bool $isActive): ImprovementCommitment
    {
        $commitment->update(['activo' => $isActive ? 1 : 0]);
        $commitment->refresh();
        return $commitment;
    }

    /**
     * Obtener IDs de evidencias vinculadas a un compromiso
     */
    private function getCommitmentEvidenceIds(int $commitmentId): array
    {
        return ImprovementCommitment::find($commitmentId)
            ?->evidences()->pluck('EVIDENCIA.evidencia_id')->toArray() ?? [];
    }

    /**
     * Obtener IDs de asignaciones vinculadas a un compromiso
     */
    private function getCommitmentAssignmentIds(int $commitmentId): array
    {
        return ImprovementCommitment::find($commitmentId)
            ?->assignedEvidences()->pluck('evidencia_asignacion_id')->toArray() ?? [];
    }

    /**
     * Crea asignaciones de evidencias a usuarios y/o roles.
     * IMPORTANTE: Solo asigna evidencias que YA estan vinculadas al compromiso.
     *
     * @param ImprovementCommitment $commitment
     * @param array $assignmentsData Array con datos [evidencia_id, usuarios[], roles[], fecha_limite]
     * @param int $processId
     * @throws ValidationException
     */
    private function createEvidenceAssignments(ImprovementCommitment $commitment, array $assignmentsData, int $processId): void
    {
        // Obtener evidencias vinculadas al compromiso via SP
        $commitmentEvidenceIds = $this->getCommitmentEvidenceIds($commitment->compromiso_mejora_id);

        foreach ($assignmentsData as $assignment) {
            // Validar que la evidencia este en el compromiso
            if (!in_array($assignment['evidencia_id'], $commitmentEvidenceIds)) {
                throw ValidationException::withMessages([
                    'evidencias_asignar' => "La evidencia con ID {$assignment['evidencia_id']} no esta vinculada al compromiso de mejora.",
                ]);
            }

            $evidenceId      = $assignment['evidencia_id'];
            $users           = $assignment['usuarios'] ?? [];
            $roles           = $assignment['roles']    ?? [];
            $deadline        = $assignment['fecha_limite'] ?? null;
            $comment         = $assignment['comentario']   ?? null;
            $assignmentDate  = $assignment['fecha_asignacion'] ?? now()->toDateTimeString();

            // Asignar a usuarios directamente
            foreach ($users as $userId) {
                if (EvidenceAssignment::where('proceso_id', $processId)
                        ->where('evidencia_id', $evidenceId)
                        ->where('usuario_id', $userId)
                        ->exists()) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "La evidencia con ID {$evidenceId} ya esta asignada al usuario con ID {$userId}.",
                    ]);
                }

                $newAssignment = EvidenceAssignment::create([
                    'proceso_id'       => $processId,
                    'evidencia_id'     => $evidenceId,
                    'usuario_id'       => $userId,
                    'estado'           => 'Pendiente',
                    'fecha_asignacion' => $assignmentDate,
                    'fecha_limite'     => $deadline,
                    'comentario'       => $comment,
                ]);

                $commitment->assignedEvidences()->attach($newAssignment->evidencia_asignacion_id, [
                    'comentario' => $comment,
                ]);
            }

            // Asignar a usuarios que tienen los roles especificados
            foreach ($roles as $roleId) {
                $role = \App\Models\Role::find($roleId);
                if (!$role) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "El rol con ID {$roleId} no existe.",
                    ]);
                }

                $usersWithRole = User::role($role->name)->active()->get();

                foreach ($usersWithRole as $user) {
                    if (EvidenceAssignment::where('proceso_id', $processId)
                            ->where('evidencia_id', $evidenceId)
                            ->where('usuario_id', $user->usuario_id)
                            ->exists()) {
                        continue;
                    }

                    $newAssignment = EvidenceAssignment::create([
                        'proceso_id'       => $processId,
                        'evidencia_id'     => $evidenceId,
                        'usuario_id'       => $user->usuario_id,
                        'estado'           => 'Pendiente',
                        'fecha_asignacion' => $assignmentDate,
                        'fecha_limite'     => $deadline,
                        'comentario'       => $comment,
                    ]);

                    $commitment->assignedEvidences()->attach($newAssignment->evidencia_asignacion_id, [
                        'comentario' => $comment,
                    ]);
                }
            }
        }
    }

    /**
     * Sincroniza (reemplaza) las asignaciones de evidencias en UPDATE.
     *
     * @param ImprovementCommitment $commitment
     * @param array $assignmentsData
     * @param int $processId
     * @throws ValidationException
     */
    private function syncEvidenceAssignments(ImprovementCommitment $commitment, array $assignmentsData, int $processId): void
    {
        // Obtener evidencias vinculadas al compromiso
        $commitmentEvidenceIds = $this->getCommitmentEvidenceIds($commitment->compromiso_mejora_id);

        // VALIDACIÓN: No permitir quitar asignaciones individuales en estado activo
        $currentAssignmentIds = $commitment->assignedEvidences()->pluck('evidencia_asignacion_id')->toArray();
        $newAssignmentIds = $this->collectNewAssignmentIds($assignmentsData, $processId);
        $assignmentsToRemove = array_diff($currentAssignmentIds, $newAssignmentIds);

        if (!empty($assignmentsToRemove)) {
            $activeAssignment = EvidenceAssignment::whereIn('evidencia_asignacion_id', $assignmentsToRemove)
                ->whereIn('estado', ['En Progreso', 'Completado'])
                ->with(['evidence:evidencia_id,nomenclatura,descripcion', 'user:usuario_id,nombre'])
                ->first();

            if ($activeAssignment) {
                $evidenceName = $activeAssignment->evidence->nomenclatura ?? 'evidencia';
                $userName = $activeAssignment->user->nombre ?? 'usuario';
                throw new \Exception(
                    "No se puede quitar la asignación de '{$userName}' a la evidencia '{$evidenceName}' " .
                    "porque ya está en estado '{$activeAssignment->estado}'."
                );
            }
        }

        // Desvincular asignaciones existentes del compromiso
        $commitment->assignedEvidences()->detach();

        if (empty($assignmentsData)) {
            return;
        }

        foreach ($assignmentsData as $assignment) {
            if (!in_array($assignment['evidencia_id'], $commitmentEvidenceIds)) {
                throw ValidationException::withMessages([
                    'evidencias_asignar' => "La evidencia con ID {$assignment['evidencia_id']} no esta vinculada al compromiso de mejora.",
                ]);
            }

            $evidenceId      = $assignment['evidencia_id'];
            $users           = $assignment['usuarios'] ?? [];
            $roles           = $assignment['roles']    ?? [];
            $deadline        = $assignment['fecha_limite'] ?? null;
            $comment         = $assignment['comentario']   ?? null;
            $assignmentDate  = $assignment['fecha_asignacion'] ?? now()->toDateTimeString();

            foreach ($users as $userId) {
                $existing = EvidenceAssignment::where('proceso_id', $processId)
                    ->where('evidencia_id', $evidenceId)
                    ->where('usuario_id', $userId)
                    ->first();

                if (!$existing) {
                    $existing = EvidenceAssignment::create([
                        'proceso_id'       => $processId,
                        'evidencia_id'     => $evidenceId,
                        'usuario_id'       => $userId,
                        'estado'           => 'Pendiente',
                        'fecha_asignacion' => $assignmentDate,
                        'fecha_limite'     => $deadline,
                        'comentario'       => $comment,
                    ]);
                } elseif (isset($assignment['fecha_limite'])) {
                    $existing->update(['fecha_limite' => $deadline, 'comentario' => $comment]);
                }

                $commitment->assignedEvidences()->attach($existing->evidencia_asignacion_id, [
                    'comentario' => $comment,
                ]);
            }

            foreach ($roles as $roleId) {
                $role = \App\Models\Role::find($roleId);
                if (!$role) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "El rol con ID {$roleId} no existe.",
                    ]);
                }

                $usersWithRole = User::role($role->name)->active()->get();

                foreach ($usersWithRole as $user) {
                    $existing = EvidenceAssignment::where('proceso_id', $processId)
                        ->where('evidencia_id', $evidenceId)
                        ->where('usuario_id', $user->usuario_id)
                        ->first();

                    if (!$existing) {
                        $existing = EvidenceAssignment::create([
                            'proceso_id'       => $processId,
                            'evidencia_id'     => $evidenceId,
                            'usuario_id'       => $user->usuario_id,
                            'estado'           => 'Pendiente',
                            'fecha_asignacion' => $assignmentDate,
                            'fecha_limite'     => $deadline,
                            'comentario'       => $comment,
                        ]);
                    } elseif (isset($assignment['fecha_limite'])) {
                        $existing->update(['fecha_limite' => $deadline, 'comentario' => $comment]);
                    }

                    $commitment->assignedEvidences()->attach($existing->evidencia_asignacion_id, [
                        'comentario' => $comment,
                    ]);
                }
            }
        }
    }

    /**
     * Valida las selecciones antes de crear o actualizar un compromiso.
     *
     * @param array $selections
     * @throws ValidationException
     */
    private function validateSelections(array $selections): void
    {
        // Validar duplicados en selecciones
        $seen = [];
        foreach ($selections as $selection) {
            $key = $selection['entidad_tipo'] . '_' . $selection['entidad_id'];
            if (in_array($key, $seen)) {
                throw ValidationException::withMessages([
                    'selecciones' => "La seleccion {$selection['entidad_tipo']} con ID {$selection['entidad_id']} esta duplicada.",
                ]);
            }
            $seen[] = $key;
        }

        // Validar que cada seleccion tenga evidencias
        foreach ($selections as $selection) {
            $evidenceIds = $this->getEvidencesByEntity(
                $selection['entidad_tipo'],
                $selection['entidad_id'],
                0
            );
            if (empty($evidenceIds)) {
                throw ValidationException::withMessages([
                    'selecciones' => "La seleccion {$selection['entidad_tipo']} con ID {$selection['entidad_id']} no tiene evidencias asociadas.",
                ]);
            }
        }
    }

    /**
     * Obtiene IDs de evidencias segun el tipo y ID de entidad seleccionada.
     *
     * @param string $entityType ESTANDAR|DIMENSION|COMPONENTE|CRITERIO|EVIDENCIA
     * @param int $entityId
     * @param int $processId (no usado, por compatibilidad)
     * @return array<int>
     */
    private function getEvidencesByEntity(string $entityType, int $entityId, int $processId): array
    {
        return match($entityType) {
            'ESTANDAR'   => $this->getEvidencesByStandard($entityId),
            'DIMENSION'  => $this->getEvidencesByDimension($entityId),
            'COMPONENTE' => $this->getEvidencesByComponent($entityId),
            'CRITERIO'   => $this->getEvidencesByCriterion($entityId),
            'EVIDENCIA'  => [$entityId],
            default      => [],
        };
    }

    private function getEvidencesByStandard(int $standardId): array
    {
        $criterionId = Standard::where('estandar_id', $standardId)->value('criterio_id');
        if (!$criterionId) {
            return [];
        }
        return Evidence::where('criterio_id', $criterionId)->pluck('evidencia_id')->toArray();
    }

    private function getEvidencesByDimension(int $dimensionId): array
    {
        return Evidence::active()
            ->whereHas('criterion.component', fn($query) => $query->where('dimension_id', $dimensionId))
            ->pluck('evidencia_id')
            ->toArray();
    }

    private function getEvidencesByComponent(int $componentId): array
    {
        return Evidence::active()
            ->whereHas('criterion', fn($query) => $query->where('componente_id', $componentId))
            ->pluck('evidencia_id')
            ->toArray();
    }

    private function getEvidencesByCriterion(int $criterionId): array
    {
        return Evidence::active()
            ->where('criterio_id', $criterionId)
            ->pluck('evidencia_id')
            ->toArray();
    }

    /**
     * Recolecta IDs de asignaciones que se mantendrán después de la sincronización.
     */
    private function collectNewAssignmentIds(array $assignmentsData, int $processId): array
    {
        $assignmentIds = [];
        
        foreach ($assignmentsData as $assignment) {
            $evidenceId = $assignment['evidencia_id'];
            $users = $assignment['usuarios'] ?? [];
            
            foreach ($users as $userId) {
                $existing = EvidenceAssignment::where('proceso_id', $processId)
                    ->where('evidencia_id', $evidenceId)
                    ->where('usuario_id', $userId)
                    ->value('evidencia_asignacion_id');
                
                if ($existing) {
                    $assignmentIds[] = $existing;
                }
            }

            $roles = $assignment['roles'] ?? [];
            foreach ($roles as $roleId) {
                $role = \App\Models\Role::find($roleId);
                if ($role) {
                    $usersWithRole = User::role($role->name)->active()->pluck('usuario_id');
                    $existingIds = EvidenceAssignment::where('proceso_id', $processId)
                        ->where('evidencia_id', $evidenceId)
                        ->whereIn('usuario_id', $usersWithRole)
                        ->pluck('evidencia_asignacion_id')
                        ->toArray();
                    
                    $assignmentIds = array_merge($assignmentIds, $existingIds);
                }
            }
        }
        
        return array_unique($assignmentIds);
    }
}