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
        $estado    = $filters['estado']    ?? null;
        $cycleId   = $filters['ciclo_acreditacion_id'] ?? null;
        $procesoId = $filters['proceso_id'] ?? null;
        $usuarioId = $filters['usuario_id'] ?? null;

        return ImprovementCommitment::with([
            'process.accreditationCycle.careerCampus.career',
            'process.accreditationCycle.careerCampus.campus',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user',
            ])
            ->when($search, fn($q) => $q->where('descripcion', 'like', "%{$search}%"))
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->when($cycleId, fn($q) => $q->whereHas('process', fn($processQuery) => $processQuery->where('ciclo_acreditacion_id', $cycleId)))
            ->when($procesoId, fn($q) => $q->where('proceso_id', $procesoId))
            ->when($usuarioId, fn($q) => $q->whereHas('assignedEvidences', fn($sub) => $sub->where('usuario_id', $usuarioId)))
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
     * @param int $usuarioId ID del usuario.
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getCommitmentsByUser(int $usuarioId)
    {
        return $this->listCommitments(100, ['usuario_id' => $usuarioId]);
    }

    /**
     * Obtener compromisos de mejora donde una evidencia especifica esta asignada.
     *
     * @param int $evidenciaId ID de la evidencia.
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getCommitmentsByEvidence(int $evidenciaId)
    {
        return ImprovementCommitment::with([
                'process',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user',
            ])
            ->whereHas('evidences', fn($q) => $q->where('evidencia_id', $evidenciaId))
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
            foreach ($data['selecciones'] as $seleccion) {
                $evidenceIds = $this->getEvidencesByEntity(
                    $seleccion['entidad_tipo'],
                    $seleccion['entidad_id'],
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
            $estado      = null;

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
                $estado     = $data['estado'];
                $hasChanges = true;
            }

            // Procesar selecciones nuevas (reemplazar completamente las evidencias existentes)
            if (isset($data['selecciones'])) {
                $newEvidenceIds = [];
                foreach ($data['selecciones'] as $seleccion) {
                    $evidenceIds = $this->getEvidencesByEntity(
                        $seleccion['entidad_tipo'],
                        $seleccion['entidad_id'],
                        $processId
                    );
                    $newEvidenceIds = array_merge($newEvidenceIds, $evidenceIds);
                }
                $newEvidenceIds = array_unique($newEvidenceIds);

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
                'estado'      => $estado,
            ], fn($v) => $v !== null);
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
     * @param bool $activo True para activar, false para desactivar.
     * @return ImprovementCommitment Compromiso actualizado.
     */
    public function setActive(ImprovementCommitment $commitment, bool $activo): ImprovementCommitment
    {
        $commitment->update(['activo' => $activo ? 1 : 0]);
        $commitment->refresh();
        return $commitment;
    }

    /**
     * Obtener IDs de evidencias vinculadas a un compromiso
     */
    private function getCommitmentEvidenceIds(int $compromisoId): array
    {
        return ImprovementCommitment::find($compromisoId)
            ?->evidences()->pluck('EVIDENCIA.evidencia_id')->toArray() ?? [];
    }

    /**
     * Obtener IDs de asignaciones vinculadas a un compromiso
     */
    private function getCommitmentAssignmentIds(int $compromisoId): array
    {
        return ImprovementCommitment::find($compromisoId)
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

            $evidenciaId  = $assignment['evidencia_id'];
            $usuarios     = $assignment['usuarios'] ?? [];
            $roles        = $assignment['roles']    ?? [];
            $fechaLimite  = $assignment['fecha_limite'] ?? null;
            $comentario   = $assignment['comentario']   ?? null;
            $fechaAsignacion = $assignment['fecha_asignacion'] ?? now()->toDateTimeString();

            // Asignar a usuarios directamente
            foreach ($usuarios as $usuarioId) {
                if (EvidenceAssignment::where('proceso_id', $processId)
                        ->where('evidencia_id', $evidenciaId)
                        ->where('usuario_id', $usuarioId)
                        ->exists()) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "La evidencia con ID {$evidenciaId} ya esta asignada al usuario con ID {$usuarioId}.",
                    ]);
                }

                $newAssignment = EvidenceAssignment::create([
                    'proceso_id'       => $processId,
                    'evidencia_id'     => $evidenciaId,
                    'usuario_id'       => $usuarioId,
                    'estado'           => 'Pendiente',
                    'fecha_asignacion' => $fechaAsignacion,
                    'fecha_limite'     => $fechaLimite,
                    'comentario'       => $comentario,
                ]);

                $commitment->assignedEvidences()->attach($newAssignment->evidencia_asignacion_id, [
                    'comentario' => $comentario,
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

                $usuariosConRol = User::role($role->name)->active()->get();

                foreach ($usuariosConRol as $usuario) {
                    if (EvidenceAssignment::where('proceso_id', $processId)
                            ->where('evidencia_id', $evidenciaId)
                            ->where('usuario_id', $usuario->usuario_id)
                            ->exists()) {
                        continue;
                    }

                    $newAssignment = EvidenceAssignment::create([
                        'proceso_id'       => $processId,
                        'evidencia_id'     => $evidenciaId,
                        'usuario_id'       => $usuario->usuario_id,
                        'estado'           => 'Pendiente',
                        'fecha_asignacion' => $fechaAsignacion,
                        'fecha_limite'     => $fechaLimite,
                        'comentario'       => $comentario,
                    ]);

                    $commitment->assignedEvidences()->attach($newAssignment->evidencia_asignacion_id, [
                        'comentario' => $comentario,
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

            $evidenciaId  = $assignment['evidencia_id'];
            $usuarios     = $assignment['usuarios'] ?? [];
            $roles        = $assignment['roles']    ?? [];
            $fechaLimite  = $assignment['fecha_limite'] ?? null;
            $comentario   = $assignment['comentario']   ?? null;
            $fechaAsignacion = $assignment['fecha_asignacion'] ?? now()->toDateTimeString();

            foreach ($usuarios as $usuarioId) {
                $existing = EvidenceAssignment::where('proceso_id', $processId)
                    ->where('evidencia_id', $evidenciaId)
                    ->where('usuario_id', $usuarioId)
                    ->first();

                if (!$existing) {
                    $existing = EvidenceAssignment::create([
                        'proceso_id'       => $processId,
                        'evidencia_id'     => $evidenciaId,
                        'usuario_id'       => $usuarioId,
                        'estado'           => 'Pendiente',
                        'fecha_asignacion' => $fechaAsignacion,
                        'fecha_limite'     => $fechaLimite,
                        'comentario'       => $comentario,
                    ]);
                } elseif (isset($assignment['fecha_limite'])) {
                    $existing->update(['fecha_limite' => $fechaLimite, 'comentario' => $comentario]);
                }

                $commitment->assignedEvidences()->attach($existing->evidencia_asignacion_id, [
                    'comentario' => $comentario,
                ]);
            }

            foreach ($roles as $roleId) {
                $role = \App\Models\Role::find($roleId);
                if (!$role) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "El rol con ID {$roleId} no existe.",
                    ]);
                }

                $usuariosConRol = User::role($role->name)->active()->get();

                foreach ($usuariosConRol as $usuario) {
                    $existing = EvidenceAssignment::where('proceso_id', $processId)
                        ->where('evidencia_id', $evidenciaId)
                        ->where('usuario_id', $usuario->usuario_id)
                        ->first();

                    if (!$existing) {
                        $existing = EvidenceAssignment::create([
                            'proceso_id'       => $processId,
                            'evidencia_id'     => $evidenciaId,
                            'usuario_id'       => $usuario->usuario_id,
                            'estado'           => 'Pendiente',
                            'fecha_asignacion' => $fechaAsignacion,
                            'fecha_limite'     => $fechaLimite,
                            'comentario'       => $comentario,
                        ]);
                    } elseif (isset($assignment['fecha_limite'])) {
                        $existing->update(['fecha_limite' => $fechaLimite, 'comentario' => $comentario]);
                    }

                    $commitment->assignedEvidences()->attach($existing->evidencia_asignacion_id, [
                        'comentario' => $comentario,
                    ]);
                }
            }
        }
    }

    /**
     * Valida las selecciones antes de crear o actualizar un compromiso.
     *
     * @param array $selecciones
     * @throws ValidationException
     */
    private function validateSelections(array $selecciones): void
    {
        // Validar duplicados en selecciones
        $seen = [];
        foreach ($selecciones as $seleccion) {
            $key = $seleccion['entidad_tipo'] . '_' . $seleccion['entidad_id'];
            if (in_array($key, $seen)) {
                throw ValidationException::withMessages([
                    'selecciones' => "La seleccion {$seleccion['entidad_tipo']} con ID {$seleccion['entidad_id']} esta duplicada.",
                ]);
            }
            $seen[] = $key;
        }

        // Validar que cada seleccion tenga evidencias
        foreach ($selecciones as $seleccion) {
            $evidenceIds = $this->getEvidencesByEntity(
                $seleccion['entidad_tipo'],
                $seleccion['entidad_id'],
                0
            );
            if (empty($evidenceIds)) {
                throw ValidationException::withMessages([
                    'selecciones' => "La seleccion {$seleccion['entidad_tipo']} con ID {$seleccion['entidad_id']} no tiene evidencias asociadas.",
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
        $criterioId = Standard::where('estandar_id', $standardId)->value('criterio_id');
        if (!$criterioId) {
            return [];
        }
        return Evidence::where('criterio_id', $criterioId)->pluck('evidencia_id')->toArray();
    }

    private function getEvidencesByDimension(int $dimensionId): array
    {
        return Evidence::active()
            ->whereHas('criterion.component', fn($q) => $q->where('dimension_id', $dimensionId))
            ->pluck('evidencia_id')
            ->toArray();
    }

    private function getEvidencesByComponent(int $componentId): array
    {
        return Evidence::active()
            ->whereHas('criterion', fn($q) => $q->where('componente_id', $componentId))
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
}