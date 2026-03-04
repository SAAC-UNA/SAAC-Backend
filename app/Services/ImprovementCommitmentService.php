<?php
namespace App\Services;

use App\Models\ImprovementCommitment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $page     = request()->get('page', 1);
        $offset   = ($page - 1) * $perPage;
        $search   = $filters['search']    ?? null;
        $estado   = $filters['estado']    ?? null;
        $procesoId  = $filters['proceso_id']  ?? null;
        $usuarioId  = $filters['usuario_id']  ?? null;

        $total = DB::select('CALL SP_CONTAR_COMPROMISOS_MEJORA(?, ?, ?, ?)', [
            $search, $estado, $procesoId, $usuarioId,
        ])[0]->total ?? 0;

        $rows = DB::select('CALL SP_OBTENER_COMPROMISOS_MEJORA(?, ?, ?, ?, ?, ?)', [
            $search, $estado, $procesoId, $usuarioId, $offset, $perPage,
        ]);

        $items = ImprovementCommitment::hydrate(array_map(fn($r) => (array) $r, $rows));

        // Cargar relaciones para mantener la estructura de respuesta original
        if ($items->isNotEmpty()) {
            $items->load([
                'process',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user',
            ]);
        }

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
        ]);
    }

    /**
     * Obtener un compromiso de mejora especifico por ID.
     *
     * @param int $id Identificador unico del compromiso.
     * @return ImprovementCommitment|null
     */
    public function getCommitment(int $id): ?ImprovementCommitment
    {
        $rows = DB::select('CALL SP_BUSCAR_COMPROMISO_MEJORA(?)', [$id]);
        if (empty($rows)) {
            return null;
        }
        $commitment = ImprovementCommitment::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
        $commitment->load([
            'process.accreditationCycle.careerCampus.career',
            'process.accreditationCycle.careerCampus.campus',
            'evidences.criterion.component.dimension',
            'evidences.criterion.standards',
            'assignedEvidences.evidence',
            'assignedEvidences.user',
        ]);
        return $commitment;
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
        // Not directly supported by SP; use listCommitments which supports
        // proceso_id/usuario_id filters; for evidence filtering we fall back
        // to full list and filter in PHP (evidence-level filter is uncommon).
        $page   = request()->get('page', 1);
        $offset = 0;

        $total = DB::select('CALL SP_CONTAR_COMPROMISOS_MEJORA(?, ?, ?, ?)', [null, null, null, null])[0]->total ?? 0;
        $rows  = DB::select('CALL SP_OBTENER_COMPROMISOS_MEJORA(?, ?, ?, ?, ?, ?)', [null, null, null, null, $offset, $total ?: 1]);

        $items = ImprovementCommitment::hydrate(array_map(fn($r) => (array) $r, $rows))
            ->filter(function (ImprovementCommitment $c) use ($evidenciaId) {
                $evidenceIds = $this->getCommitmentEvidenceIds($c->compromiso_mejora_id);
                return in_array($evidenciaId, $evidenceIds);
            })->values();

        // Cargar relaciones para mantener la estructura de respuesta original
        if ($items->isNotEmpty()) {
            $items->load([
                'process',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user',
            ]);
        }

        return new LengthAwarePaginator($items, $items->count(), $items->count() ?: 1, 1, [
            'path' => request()->url(),
        ]);
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
            $processId = $data['proceso_id'] ?? $this->getOrCreateImprovementProcess($data['ciclo_acreditacion_id']);

            // Validar que NO exista ya un compromiso en este proceso
            $existingRows = DB::select('CALL SP_BUSCAR_COMPROMISO_MEJORA_POR_PROCESO(?)', [$processId]);
            if (!empty($existingRows)) {
                return null;
            }

            // Crear nuevo compromiso con estado inicial "Pendiente"
            $rows = DB::select('CALL SP_CREAR_COMPROMISO_MEJORA(?, ?, ?, ?, ?, ?)', [
                $processId,
                $data['descripcion'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                'Pendiente',
                1,
            ]);
            $commitment = ImprovementCommitment::hydrate(array_map(fn($r) => (array) $r, $rows))->first();

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

            // Eliminar duplicados
            $allNewEvidenceIds = array_unique($allNewEvidenceIds);

            // Vincular evidencias al compromiso via SP (una por una)
            foreach ($allNewEvidenceIds as $evidenciaId) {
                DB::statement('CALL SP_VINCULAR_EVIDENCIA_COMPROMISO(?, ?)', [
                    $commitment->compromiso_mejora_id,
                    $evidenciaId,
                ]);
            }

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
            if (isset($data['ciclo_acreditacion_id'])) {
                $newProcessId = $this->getOrCreateImprovementProcess($data['ciclo_acreditacion_id']);
                if ($newProcessId !== $processId) {
                    $processId  = $newProcessId;
                    $hasChanges = true;
                }
            } elseif (isset($data['proceso_id']) && $data['proceso_id'] !== $processId) {
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

                // Reemplazar completamente: desvincular → vincular
                DB::statement('CALL SP_DESVINCULAR_EVIDENCIAS_COMPROMISO(?)', [$commitment->compromiso_mejora_id]);
                foreach ($newEvidenceIds as $evidenciaId) {
                    DB::statement('CALL SP_VINCULAR_EVIDENCIA_COMPROMISO(?, ?)', [
                        $commitment->compromiso_mejora_id,
                        $evidenciaId,
                    ]);
                }
                $hasChanges = true;
            }

            // Retornar null si no hay cambios
            if (!$hasChanges) {
                return null;
            }

            // Aplicar cambios de campos basicos
            $rows = DB::select('CALL SP_ACTUALIZAR_COMPROMISO_MEJORA(?, ?, ?, ?, ?)', [
                $commitment->compromiso_mejora_id,
                $processId !== $commitment->proceso_id ? $processId : null,
                $descripcion,
                $fechaFin,
                $estado,
            ]);

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
        $rows = DB::select('CALL SP_ESTABLECER_ACTIVO_COMPROMISO_MEJORA(?, ?)', [
            $commitment->compromiso_mejora_id,
            $activo ? 1 : 0,
        ]);
        return ImprovementCommitment::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    /**
     * Obtener IDs de evidencias vinculadas a un compromiso
     */
    private function getCommitmentEvidenceIds(int $compromisoId): array
    {
        $rows = DB::select('CALL SP_OBTENER_IDS_EVIDENCIAS_COMPROMISO(?)', [$compromisoId]);
        return array_map(fn($r) => $r->evidencia_id, $rows);
    }

    /**
     * Obtener IDs de asignaciones vinculadas a un compromiso
     */
    private function getCommitmentAssignmentIds(int $compromisoId): array
    {
        $rows = DB::select('CALL SP_OBTENER_IDS_ASIGNACIONES_COMPROMISO(?)', [$compromisoId]);
        return array_map(fn($r) => $r->evidencia_asignacion_id, $rows);
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
                // Verificar duplicado via SP
                $dup = DB::select('CALL SP_VERIFICAR_DUPLICADO_ASIGNACION(?, ?, ?)', [
                    $processId, $evidenciaId, $usuarioId,
                ]);
                if (($dup[0]->existe ?? 0)) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "La evidencia con ID {$evidenciaId} ya esta asignada al usuario con ID {$usuarioId}.",
                    ]);
                }

                $assignRows = DB::select('CALL SP_CREAR_ASIGNACION_EVIDENCIA(?, ?, ?, ?, ?, ?, ?)', [
                    $processId, $evidenciaId, $usuarioId, 'Pendiente',
                    $fechaAsignacion, $fechaLimite, $comentario,
                ]);
                $assignmentObj = !empty($assignRows) ? $assignRows[0] : null;
                $assignmentId  = $assignmentObj->evidencia_asignacion_id ?? null;

                if ($assignmentId) {
                    DB::statement('CALL SP_VINCULAR_ASIGNACION_COMPROMISO(?, ?, ?)', [
                        $commitment->compromiso_mejora_id,
                        $assignmentId,
                        $comentario,
                    ]);
                }
            }

            // Asignar a usuarios que tienen los roles especificados
            foreach ($roles as $roleId) {
                $role = \App\Models\Role::find($roleId);
                if (!$role) {
                    throw ValidationException::withMessages([
                        'evidencias_asignar' => "El rol con ID {$roleId} no existe.",
                    ]);
                }

                // Obtener usuarios activos con ese rol usando Spatie
                $usuariosConRol = User::role($role->name)->active()->get();

                foreach ($usuariosConRol as $usuario) {
                    $dup = DB::select('CALL SP_VERIFICAR_DUPLICADO_ASIGNACION(?, ?, ?)', [
                        $processId, $evidenciaId, $usuario->usuario_id,
                    ]);
                    if ($dup[0]->existe ?? 0) {
                        continue; // Omitir silenciosamente
                    }

                    $assignRows = DB::select('CALL SP_CREAR_ASIGNACION_EVIDENCIA(?, ?, ?, ?, ?, ?, ?)', [
                        $processId, $evidenciaId, $usuario->usuario_id, 'Pendiente',
                        $fechaAsignacion, $fechaLimite, $comentario,
                    ]);
                    $assignmentObj = !empty($assignRows) ? $assignRows[0] : null;
                    $assignmentId  = $assignmentObj->evidencia_asignacion_id ?? null;

                    if ($assignmentId) {
                        DB::statement('CALL SP_VINCULAR_ASIGNACION_COMPROMISO(?, ?, ?)', [
                            $commitment->compromiso_mejora_id,
                            $assignmentId,
                            $comentario,
                        ]);
                    }
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
        DB::statement('CALL SP_DESVINCULAR_ASIGNACIONES_COMPROMISO(?)', [$commitment->compromiso_mejora_id]);

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
                // Buscar asignacion existente via SP
                $existingRows = DB::select('CALL SP_VERIFICAR_DUPLICADO_ASIGNACION(?, ?, ?)', [
                    $processId, $evidenciaId, $usuarioId,
                ]);
                $existe = $existingRows[0]->existe ?? 0;

                if (!$existe) {
                    $assignRows = DB::select('CALL SP_CREAR_ASIGNACION_EVIDENCIA(?, ?, ?, ?, ?, ?, ?)', [
                        $processId, $evidenciaId, $usuarioId, 'Pendiente',
                        $fechaAsignacion, $fechaLimite, $comentario,
                    ]);
                    $assignmentId = $assignRows[0]->evidencia_asignacion_id ?? null;
                } else {
                    // Obtener ID de asignacion existente para vincular
                    $asignRows    = DB::select('CALL SP_OBTENER_ASIGNACIONES_EVIDENCIA(?, ?, ?)', [$processId, $usuarioId, $evidenciaId]);
                    $assignmentId = !empty($asignRows) ? $asignRows[0]->evidencia_asignacion_id : null;

                    // Actualizar fecha_limite si cambio
                    if ($assignmentId && isset($assignment['fecha_limite'])) {
                        DB::statement('CALL SP_ACTUALIZAR_ASIGNACION_EVIDENCIA(?, ?, ?, ?)', [
                            $assignmentId, null, $fechaLimite, $comentario,
                        ]);
                    }
                }

                if ($assignmentId) {
                    DB::statement('CALL SP_VINCULAR_ASIGNACION_COMPROMISO(?, ?, ?)', [
                        $commitment->compromiso_mejora_id,
                        $assignmentId,
                        $comentario,
                    ]);
                }
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
                    $existingRows = DB::select('CALL SP_VERIFICAR_DUPLICADO_ASIGNACION(?, ?, ?)', [
                        $processId, $evidenciaId, $usuario->usuario_id,
                    ]);
                    $existe = $existingRows[0]->existe ?? 0;

                    if (!$existe) {
                        $assignRows = DB::select('CALL SP_CREAR_ASIGNACION_EVIDENCIA(?, ?, ?, ?, ?, ?, ?)', [
                            $processId, $evidenciaId, $usuario->usuario_id, 'Pendiente',
                            $fechaAsignacion, $fechaLimite, $comentario,
                        ]);
                        $assignmentId = $assignRows[0]->evidencia_asignacion_id ?? null;
                    } else {
                        $asignRows    = DB::select('CALL SP_OBTENER_ASIGNACIONES_EVIDENCIA(?, ?, ?)', [$processId, $usuario->usuario_id, $evidenciaId]);
                        $assignmentId = !empty($asignRows) ? $asignRows[0]->evidencia_asignacion_id : null;

                        if ($assignmentId && isset($assignment['fecha_limite'])) {
                            DB::statement('CALL SP_ACTUALIZAR_ASIGNACION_EVIDENCIA(?, ?, ?, ?)', [
                                $assignmentId, null, $fechaLimite, $comentario,
                            ]);
                        }
                    }

                    if ($assignmentId) {
                        DB::statement('CALL SP_VINCULAR_ASIGNACION_COMPROMISO(?, ?, ?)', [
                            $commitment->compromiso_mejora_id,
                            $assignmentId,
                            $comentario,
                        ]);
                    }
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
     * Obtiene o crea un proceso de tipo "Compromiso de mejora" para el ciclo dado.
     *
     * @param int $cycleId ID del ciclo de acreditacion.
     * @return int ID del proceso.
     */
    private function getOrCreateImprovementProcess(int $cycleId): int
    {
        $result = DB::select('CALL SP_OBTENER_O_CREAR_PROCESO_MEJORA(?)', [$cycleId]);
        return $result[0]->proceso_id;
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
        $rows = DB::select('CALL SP_OBTENER_EVIDENCIAS_POR_ESTANDAR(?)', [$standardId]);
        return array_map(fn($r) => $r->evidencia_id, $rows);
    }

    private function getEvidencesByDimension(int $dimensionId): array
    {
        $rows = DB::select('CALL SP_OBTENER_EVIDENCIAS_POR_DIMENSION(?)', [$dimensionId]);
        return array_map(fn($r) => $r->evidencia_id, $rows);
    }

    private function getEvidencesByComponent(int $componentId): array
    {
        $rows = DB::select('CALL SP_OBTENER_EVIDENCIAS_POR_COMPONENTE(?)', [$componentId]);
        return array_map(fn($r) => $r->evidencia_id, $rows);
    }

    private function getEvidencesByCriterion(int $criterionId): array
    {
        $rows = DB::select('CALL SP_OBTENER_EVIDENCIAS_POR_CRITERIO(?)', [$criterionId]);
        return array_map(fn($r) => $r->evidencia_id, $rows);
    }
}