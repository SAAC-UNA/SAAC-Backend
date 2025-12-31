<?php

namespace App\Services;

use App\Models\ImprovementCommitment;
use App\Models\EvidenceAssignment;
use App\Models\Process;
use App\Models\Evidence;
use App\Models\Dimension;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio que gestiona la lógica de negocio relacionada con Compromisos de Mejora.
 * Incluye operaciones para listar, obtener, crear, actualizar y eliminar compromisos,
 * así como la gestión de evidencias asociadas automáticamente según la entidad seleccionada.
 */
class ImprovementCommitmentService
{
    public function __construct()
    {
        // Preparado para futuras dependencias (ej: logger, auditoría, etc.)
    }

    /**
     * Listar compromisos de mejora con paginación obligatoria y filtros dinámicos.
     *
     * @param int $perPage Cantidad de registros por página (default: 10).
     * @param array $filters Filtros opcionales: search, estado, proceso_id, usuario_id
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listCommitments(int $perPage = 10, array $filters = [])
    {
        $query = ImprovementCommitment::query()
            ->with([
                'process',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user'
            ]);

        // Filtro de búsqueda por descripción
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('descripcion', 'like', "%{$search}%");
        }

        // Filtro por estado
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        // Filtro por proceso
        if (!empty($filters['proceso_id'])) {
            $query->where('proceso_id', $filters['proceso_id']);
        }

        // Filtro por usuario asignado (compromisos que tienen asignaciones a ese usuario)
        if (!empty($filters['usuario_id'])) {
            $query->whereHas('assignedEvidences', function ($q) use ($filters) {
                $q->where('usuario_id', $filters['usuario_id']);
            });
        }

        // Ordenar por fecha de creación (más recientes primero)
        $query->orderBy('created_at', 'desc');

        // SIEMPRE retornar paginado (estándar del equipo)
        return $query->paginate($perPage);
    }

    /**
     * Obtener un compromiso de mejora específico por ID con sus relaciones.
     *
     * @param int $id Identificador único del compromiso.
     * @return ImprovementCommitment|null Retorna el compromiso o null si no existe.
     */
    public function getCommitment(int $id): ?ImprovementCommitment
    {
        return ImprovementCommitment::with([
            'process',
            'evidences.criterion.component.dimension',
            'assignedEvidences.evidence',
            'assignedEvidences.user'
        ])->find($id);
    }

    /**
     * Obtener compromisos de mejora donde un usuario específico tiene asignaciones.
     *
     * @param int $usuarioId ID del usuario.
     * @return \Illuminate\Support\Collection Compromisos donde el usuario tiene asignaciones.
     */
    public function getCommitmentsByUser(int $usuarioId)
    {
        return ImprovementCommitment::with([
            'process.accreditationCycle.careerCampus.career',
            'process.accreditationCycle.careerCampus.campus',
            'evidences.criterion.component.dimension',
            'evidences.criterion.standards',
            'assignedEvidences' => function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            },
            'assignedEvidences.evidence',
            'assignedEvidences.user'
        ])
        ->whereHas('assignedEvidences', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        })
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Obtener compromisos de mejora donde una evidencia específica está asignada.
     * Solo carga las asignaciones que corresponden a esa evidencia.
     *
     * @param int $evidenciaId ID de la evidencia.
     * @return \Illuminate\Support\Collection Compromisos donde la evidencia está asignada.
     */
    public function getCommitmentsByEvidence(int $evidenciaId)
    {
        return ImprovementCommitment::with([
            'process.accreditationCycle.careerCampus.career',
            'process.accreditationCycle.careerCampus.campus',
            'evidences.criterion.component.dimension',
            'evidences.criterion.standards',
            'assignedEvidences' => function ($query) use ($evidenciaId) {
                $query->where('evidencia_id', $evidenciaId);
            },
            'assignedEvidences.evidence',
            'assignedEvidences.user'
        ])
        ->whereHas('assignedEvidences', function ($query) use ($evidenciaId) {
            $query->where('evidencia_id', $evidenciaId);
        })
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**                                          
     * Crear un nuevo compromiso de mejora con sus evidencias.
     * Se usa transacción para garantizar atomicidad en el proceso.
     *
     * @param array<string,mixed> $data Datos validados del compromiso.
     * @return ImprovementCommitment|null Compromiso recién creado o null si ya existe.
     * @throws BusinessValidationException Si validaciones fallan
     */
    public function createCommitment(array $data): ?ImprovementCommitment
    {
        return DB::transaction(function () use ($data) {
            // Validar selecciones (duplicados y que tengan evidencias)
            $this->validateSelections($data['selecciones']);

            // Obtener o crear proceso automáticamente
            $processId = $data['proceso_id'] ?? $this->getOrCreateImprovementProcess($data['ciclo_acreditacion_id']);

            // Validar que NO exista ya un compromiso en este proceso
            // Como cada ciclo_acreditacion_id pertenece a una carrera específica (carrera_sede_id),
            // validar por proceso_id ya garantiza: un compromiso por carrera por ciclo
            $existingCommitment = ImprovementCommitment::where('proceso_id', $processId)->first();
            
            if ($existingCommitment) {
                return null; // Retorna null si ya existe un compromiso en este proceso
            }

            // Crear nuevo compromiso con estado inicial "Pendiente"
            $commitment = ImprovementCommitment::create([
                'proceso_id' => $processId,
                'descripcion' => $data['descripcion'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'estado' => 'Pendiente', // Siempre Pendiente al crear
            ]);

            $allNewEvidenceIds = [];

            // Procesar cada selección y obtener sus evidencias
            foreach ($data['selecciones'] as $seleccion) {
                $evidenceIds = $this->getEvidencesByEntity(
                    $seleccion['entidad_tipo'],
                    $seleccion['entidad_id'],
                    $processId
                );
                $allNewEvidenceIds = array_merge($allNewEvidenceIds, $evidenceIds);
            }

            // Eliminar duplicados
            $allNewEvidenceIds = array_unique($allNewEvidenceIds);

            // Vincular evidencias al compromiso
            if (!empty($allNewEvidenceIds)) {
                $commitment->evidences()->attach($allNewEvidenceIds);
            }

            // Crear asignaciones de evidencias a usuarios si se proporcionan
            if (!empty($data['evidencias_asignar'])) {
                $this->createEvidenceAssignments($commitment, $data['evidencias_asignar'], $processId);
            }

            return $commitment->refresh()->load([
                'process.accreditationCycle.careerCampus.career',
                'process.accreditationCycle.careerCampus.campus',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user'
            ]);
        });
    }

    /**
     * Actualizar un compromiso de mejora existente.
     * Solo se ejecuta la actualización si hay cambios reales.
     *
     * @param ImprovementCommitment $commitment Compromiso a actualizar.
     * @param array<string,mixed> $data Nuevos datos del compromiso.
     * @return ImprovementCommitment Compromiso actualizado.
     * @throws BusinessValidationException Si no hay cambios o validaciones fallan
     */
    public function updateCommitment(ImprovementCommitment $commitment, array $data): ImprovementCommitment
    {
        return DB::transaction(function () use ($commitment, $data) {
            $hasChanges = false;

            // Validar selecciones si se proporcionan
            if (isset($data['selecciones'])) {
                $this->validateSelections($data['selecciones']);
            }

            // Determinar proceso_id para actualización
            $processId = $commitment->proceso_id;
            if (isset($data['ciclo_acreditacion_id'])) {
                $newProcessId = $this->getOrCreateImprovementProcess($data['ciclo_acreditacion_id']);
                if ($newProcessId !== $processId) {
                    $processId = $newProcessId;
                    $hasChanges = true;
                }
            } elseif (isset($data['proceso_id']) && $data['proceso_id'] !== $processId) {
                $processId = $data['proceso_id'];
                $hasChanges = true;
            }

            // Detectar cambios en campos básicos
            $fieldsToUpdate = [];
            if (isset($data['descripcion']) && $data['descripcion'] !== $commitment->descripcion) {
                $fieldsToUpdate['descripcion'] = $data['descripcion'];
                $hasChanges = true;
            }
            if (isset($data['fecha_inicio']) && $data['fecha_inicio'] !== $commitment->fecha_inicio->format('Y-m-d')) {
                $fieldsToUpdate['fecha_inicio'] = $data['fecha_inicio'];
                $hasChanges = true;
            }
            if (isset($data['fecha_fin']) && $data['fecha_fin'] !== $commitment->fecha_fin->format('Y-m-d')) {
                $fieldsToUpdate['fecha_fin'] = $data['fecha_fin'];
                $hasChanges = true;
            }
            if (isset($data['estado']) && $data['estado'] !== $commitment->estado) {
                $fieldsToUpdate['estado'] = $data['estado'];
                $hasChanges = true;
            }
            if ($processId !== $commitment->proceso_id) {
                $fieldsToUpdate['proceso_id'] = $processId;
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

                // Usar sync() para reemplazar completamente las evidencias
                $commitment->evidences()->sync($newEvidenceIds);
                $hasChanges = true;
            }

            // Retornar null si no hay cambios
            if (!$hasChanges) {
                return null;
            }

            // Aplicar cambios
            if (!empty($fieldsToUpdate)) {
                $commitment->update($fieldsToUpdate);
            }

            // Reemplazar asignaciones si se proporcionan (usando sync)
            // Nota: isset permite detectar si el campo fue enviado, incluso si es array vacío
            if (isset($data['evidencias_asignar'])) {
                $this->syncEvidenceAssignments($commitment, $data['evidencias_asignar'], $processId);
                $hasChanges = true;
            }

            return $commitment->refresh()->load([
                'process',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user'
            ]);
        });
    }

    /**
     * Activar o desactivar un compromiso de mejora.
     * Cuando se desactiva (activo=false), el compromiso se oculta pero preserva todos sus datos.
     *
     * @param ImprovementCommitment $commitment Compromiso a modificar.
     * @param bool $activo True para activar, false para desactivar.
     * @return ImprovementCommitment Compromiso actualizado.
     */
    public function setActive(ImprovementCommitment $commitment, bool $activo): ImprovementCommitment
    {
        $commitment->activo = $activo;
        $commitment->save();
        
        return $commitment->fresh([
            'process.accreditationCycle.careerCampus.career',
            'process.accreditationCycle.careerCampus.campus',
            'evidences',
            'assignedEvidences.evidence',
            'assignedEvidences.user'
        ]);
    }

    /**
     * Crea asignaciones de evidencias a usuarios y/o roles.
     * IMPORTANTE: Solo asigna evidencias que YA están vinculadas al compromiso.
     *
     * @param ImprovementCommitment $commitment
     * @param array $assignmentsData Array con datos [evidencia_id, usuarios[], roles[], fecha_limite (opcional)]
     * @param int $processId
     * @return void
     * @throws ValidationException Si se intenta asignar una evidencia que no está en el compromiso o ya está asignada
     */
    private function createEvidenceAssignments(ImprovementCommitment $commitment, array $assignmentsData, int $processId): void
    {
        // Obtener evidencias vinculadas al compromiso
        $commitmentEvidenceIds = $commitment->evidences()
            ->pluck('COMPROMISO_MEJORA_EVIDENCIA.evidencia_id')
            ->toArray();
        
        $assignmentIds = [];
        
        foreach ($assignmentsData as $assignment) {
            // Validar que la evidencia esté en el compromiso
            if (!in_array($assignment['evidencia_id'], $commitmentEvidenceIds)) {
                throw ValidationException::withMessages([
                    'evidencias_asignar' => "La evidencia con ID {$assignment['evidencia_id']} no está vinculada al compromiso de mejora. Solo se pueden asignar evidencias que pertenezcan al compromiso."
                ]);
            }

            $evidenciaId = $assignment['evidencia_id'];
            $usuarios = $assignment['usuarios'] ?? [];
            $roles = $assignment['roles'] ?? [];
            $fechaLimite = $assignment['fecha_limite'] ?? null;
            $comentario = $assignment['comentario'] ?? null;
            
            // Asignar a usuarios directamente
            foreach ($usuarios as $usuarioId) {
                // Verificar si ya existe una asignación para esta evidencia Y usuario (evitar duplicados exactos)
                $existingAssignment = EvidenceAssignment::where('proceso_id', $processId)
                    ->where('evidencia_id', $evidenciaId)
                    ->where('usuario_id', $usuarioId)
                    ->first();

                if ($existingAssignment) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "La evidencia con ID {$evidenciaId} ya está asignada al usuario con ID {$usuarioId} en este proceso. No se pueden crear asignaciones duplicadas."
                    ]);
                }
                
                $evidenceAssignment = EvidenceAssignment::create([
                    'proceso_id' => $processId,
                    'evidencia_id' => $evidenciaId,
                    'usuario_id' => $usuarioId,
                    'fecha_asignacion' => $assignment['fecha_asignacion'] ?? now(),
                    'fecha_limite' => $fechaLimite,
                    'estado' => 'Pendiente',
                ]);
                
                // Usar evidencia_asignacion_id como clave y guardar comentario
                $assignmentIds[$evidenceAssignment->evidencia_asignacion_id] = [
                    'comentario' => $comentario
                ];
            }
            
            // Asignar a usuarios que tienen los roles especificados
            foreach ($roles as $roleId) {
                $role = \App\Models\Role::find($roleId);
                if (!$role) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "El rol con ID {$roleId} no existe."
                    ]);
                }

                // Obtener usuarios activos que tienen este rol usando Spatie
                $usuariosConRol = User::role($role->name)->active()->get();
                
                foreach ($usuariosConRol as $usuario) {
                    // Verificar si ya existe una asignación para esta evidencia Y usuario
                    $existingAssignment = EvidenceAssignment::where('proceso_id', $processId)
                        ->where('evidencia_id', $evidenciaId)
                        ->where('usuario_id', $usuario->usuario_id)
                        ->first();

                    if ($existingAssignment) {
                        // Si ya existe, omitir silenciosamente (puede haber sido asignado directamente o por otro rol)
                        continue;
                    }
                    
                    $evidenceAssignment = EvidenceAssignment::create([
                        'proceso_id' => $processId,
                        'evidencia_id' => $evidenciaId,
                        'usuario_id' => $usuario->usuario_id,
                        'fecha_asignacion' => $assignment['fecha_asignacion'] ?? now(),
                        'fecha_limite' => $fechaLimite,
                        'estado' => 'Pendiente',
                    ]);
                    
                    // Usar evidencia_asignacion_id como clave y guardar comentario
                    $assignmentIds[$evidenceAssignment->evidencia_asignacion_id] = [
                        'comentario' => $comentario
                    ];
                }
            }
        }
        
        // Vincular las asignaciones creadas al compromiso con comentarios
        if (!empty($assignmentIds)) {
            $commitment->assignedEvidences()->attach($assignmentIds);
        }
    }

    /**
     * Sincroniza (reemplaza) las asignaciones de evidencias a usuarios y/o roles en UPDATE.
     * IMPORTANTE: Solo asigna evidencias que YA están vinculadas al compromiso.
     *
     * @param ImprovementCommitment $commitment
     * @param array $assignmentsData Array con datos [evidencia_id, usuarios[], roles[], fecha_limite (opcional)]
     * @param int $processId
     * @return void
     * @throws ValidationException Si se intenta asignar una evidencia que no está en el compromiso
     */
    private function syncEvidenceAssignments(ImprovementCommitment $commitment, array $assignmentsData, int $processId): void
    {
        // Obtener evidencias vinculadas al compromiso
        $commitmentEvidenceIds = $commitment->evidences()
            ->pluck('COMPROMISO_MEJORA_EVIDENCIA.evidencia_id')
            ->toArray();
        
        $assignmentIds = [];
        
        foreach ($assignmentsData as $assignment) {
            // Validar que la evidencia esté en el compromiso
            if (!in_array($assignment['evidencia_id'], $commitmentEvidenceIds)) {
                throw ValidationException::withMessages([
                    'evidencias_asignar' => "La evidencia con ID {$assignment['evidencia_id']} no está vinculada al compromiso de mejora. Solo se pueden asignar evidencias que pertenezcan al compromiso."
                ]);
            }

            $evidenciaId = $assignment['evidencia_id'];
            $usuarios = $assignment['usuarios'] ?? [];
            $roles = $assignment['roles'] ?? [];
            $fechaLimite = $assignment['fecha_limite'] ?? null;
            $comentario = $assignment['comentario'] ?? null;
            
            // Asignar a usuarios directamente
            foreach ($usuarios as $usuarioId) {
                // Buscar o crear la asignación
                $evidenceAssignment = EvidenceAssignment::where('proceso_id', $processId)
                    ->where('evidencia_id', $evidenciaId)
                    ->where('usuario_id', $usuarioId)
                    ->first();

                if (!$evidenceAssignment) {
                    $evidenceAssignment = EvidenceAssignment::create([
                        'proceso_id' => $processId,
                        'evidencia_id' => $evidenciaId,
                        'usuario_id' => $usuarioId,
                        'fecha_asignacion' => $assignment['fecha_asignacion'] ?? now(),
                        'fecha_limite' => $fechaLimite,
                        'estado' => 'Pendiente',
                    ]);
                } else {
                    // Actualizar fecha_limite si cambió
                    if (isset($assignment['fecha_limite'])) {
                        $evidenceAssignment->update(['fecha_limite' => $fechaLimite]);
                    }
                }
                
                // Usar evidencia_asignacion_id como clave y guardar comentario
                $assignmentIds[$evidenceAssignment->evidencia_asignacion_id] = [
                    'comentario' => $comentario
                ];
            }
            
            // Asignar a usuarios que tienen los roles especificados
            foreach ($roles as $roleId) {
                $role = \App\Models\Role::find($roleId);
                if (!$role) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "El rol con ID {$roleId} no existe."
                    ]);
                }

                // Obtener usuarios activos que tienen este rol usando Spatie
                $usuariosConRol = User::role($role->name)->active()->get();
                
                foreach ($usuariosConRol as $usuario) {
                    // Buscar o crear la asignación
                    $evidenceAssignment = EvidenceAssignment::where('proceso_id', $processId)
                        ->where('evidencia_id', $evidenciaId)
                        ->where('usuario_id', $usuario->usuario_id)
                        ->first();

                    if (!$evidenceAssignment) {
                        $evidenceAssignment = EvidenceAssignment::create([
                            'proceso_id' => $processId,
                            'evidencia_id' => $evidenciaId,
                            'usuario_id' => $usuario->usuario_id,
                            'fecha_asignacion' => $assignment['fecha_asignacion'] ?? now(),
                            'fecha_limite' => $fechaLimite,
                            'estado' => 'Pendiente',
                        ]);
                    } else {
                        // Actualizar fecha_limite si cambió
                        if (isset($assignment['fecha_limite'])) {
                            $evidenceAssignment->update(['fecha_limite' => $fechaLimite]);
                        }
                    }
                    
                    // Usar evidencia_asignacion_id como clave y guardar comentario
                    $assignmentIds[$evidenceAssignment->evidencia_asignacion_id] = [
                        'comentario' => $comentario
                    ];
                }
            }
        }
        
        // Usar sync() con comentarios para reemplazar completamente las asignaciones
        if (!empty($assignmentIds)) {
            $commitment->assignedEvidences()->sync($assignmentIds);
        } else {
            $commitment->assignedEvidences()->detach();
        }
    }

    /**
     * Valida las selecciones antes de crear o actualizar un compromiso.
     * 
     * @param array $selecciones Array de selecciones con entidad_tipo y entidad_id
     * @throws BusinessValidationException Si hay duplicados o entidades sin evidencias
     */
    private function validateSelections(array $selecciones): void
    {
        // Validar duplicados en selecciones
        $seen = [];
        foreach ($selecciones as $index => $seleccion) {
            $key = $seleccion['entidad_tipo'] . '_' . $seleccion['entidad_id'];
            
            if (in_array($key, $seen)) {
                throw ValidationException::withMessages([
                    'selecciones' => "La selección {$seleccion['entidad_tipo']} con ID {$seleccion['entidad_id']} está duplicada. No se pueden seleccionar las mismas entidades más de una vez."
                ]);
            }
            $seen[] = $key;
        }

        // Validar que cada selección tenga evidencias
        foreach ($selecciones as $seleccion) {
            $evidenceIds = $this->getEvidencesByEntity(
                $seleccion['entidad_tipo'],
                $seleccion['entidad_id'],
                0 // processId no se usa en la búsqueda
            );

            if (empty($evidenceIds)) {
                throw ValidationException::withMessages([
                    'selecciones' => "La selección {$seleccion['entidad_tipo']} con ID {$seleccion['entidad_id']} no tiene evidencias asociadas. Todas las selecciones deben contener al menos una evidencia."
                ]);
            }
        }
    }

    /**
     * Obtiene o crea un proceso de tipo "Compromiso de mejora" para el ciclo dado.
     * REGLA: Solo puede existir 1 proceso de "Compromiso de mejora" por ciclo.
     * - Si ya existe: lo reutiliza
     * - Si no existe: lo crea
     *
     * @param int $cycleId ID del ciclo de acreditación.
     * @return int ID del proceso.
     */
    private function getOrCreateImprovementProcess(int $cycleId): int
    {
        $process = Process::where('ciclo_acreditacion_id', $cycleId)
            ->where('tipo_proceso', 'Compromiso de mejora')
            ->first();

        if (!$process) {
            $process = Process::create([
                'ciclo_acreditacion_id' => $cycleId,
                'tipo_proceso' => 'Compromiso de mejora',
            ]);
        }

        return $process->proceso_id;
    }

    /**
     * Obtiene IDs de evidencias según el tipo y ID de entidad seleccionada desde el repositorio.
     * NOTA: Métodos separados por nivel porque cada uno navega diferente en la jerarquía.
     * Optimizado para usar índices de BD y evitar queries innecesarias.
     *
     * @param string $entityType Tipo de entidad (ESTANDAR, DIMENSION, COMPONENTE, CRITERIO, EVIDENCIA).
     * @param int $entityId ID de la entidad.
     * @param int $processId ID del proceso de acreditación (no usado, se mantiene por compatibilidad).
     * @return array<int> Array de IDs de evidencias del repositorio.
     */
    private function getEvidencesByEntity(string $entityType, int $entityId, int $processId): array
    {
        return match($entityType) {
            'ESTANDAR' => $this->getEvidencesByStandard($entityId),
            'DIMENSION' => $this->getEvidencesByDimension($entityId),
            'COMPONENTE' => $this->getEvidencesByComponent($entityId),
            'CRITERIO' => $this->getEvidencesByCriterion($entityId),
            'EVIDENCIA' => [$entityId],
            default => [],
        };
    }

    private function getEvidencesByStandard(int $standardId): array
    {
        // ESTANDAR → criterio_id, buscar evidencias de ese criterio
        $standard = \App\Models\Standard::find($standardId);
        if (!$standard) return [];
        
        return Evidence::where('criterio_id', $standard->criterio_id)->pluck('evidencia_id')->toArray();
    }

    private function getEvidencesByDimension(int $dimensionId): array
    {
        // DIMENSION → COMPONENTE → CRITERIO → EVIDENCIA
        return Evidence::whereHas('criterion.component.dimension', function ($query) use ($dimensionId) {
            $query->where('dimension_id', $dimensionId);
        })->pluck('evidencia_id')->toArray();
    }

    private function getEvidencesByComponent(int $componentId): array
    {                              
        // COMPONENTE → CRITERIO → EVIDENCIA
        return Evidence::whereHas('criterion.component', function ($query) use ($componentId) {
            $query->where('componente_id', $componentId);
        })->pluck('evidencia_id')->toArray();
    }

    private function getEvidencesByCriterion(int $criterionId): array
    {
        // CRITERIO → EVIDENCIA (directo)
        return Evidence::where('criterio_id', $criterionId)->pluck('evidencia_id')->toArray();
    }
}
