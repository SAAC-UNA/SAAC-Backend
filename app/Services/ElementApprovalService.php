<?php

namespace App\Services;

use App\Models\ElementApproval;
use App\Models\ElementAssignment;
use App\Models\StructureElement;
use App\Models\Notification;
use App\Events\ElementApproved;
use App\Events\ElementRejected;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

/**
 * Servicio de aprobación de bloques — modelo flexible (ELEMENTO directo).
 * Tabla: APROBACION_ELEMENTO.
 * No interviene EVIDENCIA ni APROBACION_EVIDENCIA.
 */
class ElementApprovalService
{
    public function __construct() {}

    // -----------------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------------

    /**
     * Lista aprobaciones de Elements.
     * Si el usuario es Profesor, muestra todas las de su proceso
     * (filtro por ELEMENTO_ASIGNACION pendiente HU-009).
     */
    public function listApprovals(?string $estado = null)
    {
        $query = ElementApproval::with(['elemento', 'process', 'user']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && $user->hasRole('Profesor')) {
            $query->whereHas(
                'elemento.assignments',
                fn($q) => $q->where('usuario_id', $user->usuario_id)
            );
        }

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        $procesoId = request()->query('proceso_id');
        if ($procesoId !== null) {
            $query->where('proceso_id', (int) $procesoId);
        }

        return $query->get();
    }

    /**
     * Obtiene una aprobación específica por ID.
     */
    public function getApproval(int $aprobacionId): ?ElementApproval
    {
        $approval = ElementApproval::with(['elemento', 'process', 'user'])->find($aprobacionId);

        if ($approval) {
            $childIds = $approval->elemento->children->pluck('elemento_id');
            $approval->hijos = ElementApproval::with(['elemento'])
                ->whereIn('elemento_id', $childIds)
                ->where('proceso_id', $approval->proceso_id)
                ->get();
        }

        return $approval;
    }

    /**
     * Obtiene la aprobación de un elemento en un proceso específico.
     */
    public function getApprovalByElementAndProcess(int $elementoId, int $procesoId): ?ElementApproval
    {
        return ElementApproval::where('elemento_id', $elementoId)
            ->where('proceso_id', $procesoId)
            ->first();
    }

    // -----------------------------------------------------------------------
    // Aprobar / rechazar — con cascada hacia hijos
    // -----------------------------------------------------------------------

    /**
     * Recolecta recursivamente los IDs de un elemento y todos sus descendientes activos.
     * Ejemplo: pauta → [pauta_id, fuente1_id, fuente2_id]
     */
    private function collectDescendantIds(int $elementoId): array
    {
        $ids = [$elementoId];
        $children = StructureElement::where('padre_id', $elementoId)
            ->where('activo', true)
            ->pluck('elemento_id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->collectDescendantIds($childId));
        }

        return $ids;
    }

    private function getTargetAssigneeIds(int $elementoId, int $procesoId, ?int $targetUserId): array
    {
        $query = ElementAssignment::where('elemento_id', $elementoId)
            ->where('proceso_id', $procesoId);

        if ($targetUserId !== null) {
            $query->where('usuario_id', $targetUserId);
        }

        $userIds = $query->pluck('usuario_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if ($targetUserId !== null && empty($userIds)) {
            throw new \InvalidArgumentException(
                'El responsable seleccionado no tiene asignado este elemento en el proceso indicado.'
            );
        }

        return $userIds;
    }

    /**
     * @return array<int, ElementApproval>
     */
    private function getLatestApprovalsByUser(Collection $approvals): array
    {
        $latest = [];

        $approvals->groupBy('usuario_id')->each(function (Collection $group, $userId) use (&$latest) {
            $ordered = $group->sortByDesc(
                fn(ElementApproval $approval) => $approval->updated_at?->getTimestamp() ?? $approval->aprobacion_elemento_id
            );
            $latest[(int) $userId] = $ordered->first();
        });

        return $latest;
    }

    private function resolveDecisionSummary(
        array $targetUserIds,
        Collection $approvals,
        bool $allowIncomplete
    ): string {
        $latestByUser = $this->getLatestApprovalsByUser($approvals);
        $states = [];

        if (!empty($targetUserIds)) {
            foreach ($targetUserIds as $targetUserId) {
                $states[] = $latestByUser[$targetUserId]->estado ?? 'pendiente';
            }
        } else {
            foreach ($latestByUser as $approval) {
                $states[] = $approval->estado;
            }
        }

        $total = count($states);
        if ($total === 0) {
            return 'pendiente';
        }

        $approvedCount = count(array_filter($states, fn($state) => $state === 'aprobado'));
        $rejectedCount = count(array_filter($states, fn($state) => $state === 'rechazado'));

        if ($approvedCount === $total) {
            return 'aprobado';
        }

        if ($rejectedCount === $total) {
            return 'rechazado';
        }

        if ($allowIncomplete && $rejectedCount >= 1) {
            return 'incompleto';
        }

        if ($rejectedCount >= 1) {
            return 'rechazado';
        }

        return 'pendiente';
    }

    /**
     * Recalcula y persiste el estado del bloque padre según las decisiones
     * individuales de sus hijos directos activos.
     *
     * Reglas (equivalente a recalculateBlockState en CriterionApprovalService):
     *   - Todos aprobados                         → 'aprobado'
     *   - Todos rechazados                        → 'rechazado'
     *   - Al menos 1 rechazado (no todos iguales) → 'incompleto'
     *   - Ninguna decisión tomada aún             → 'pendiente'
     */
    public function recalculateParentState(int $padreId, int $procesoId): void
    {
        $block = ElementApproval::where('elemento_id', $padreId)
            ->where('proceso_id', $procesoId)
            ->first();

        if (!$block) {
            return;
        }

        $childIds = StructureElement::where('padre_id', $padreId)
            ->where('activo', true)
            ->pluck('elemento_id');

        $total = $childIds->count();

        if ($total === 0) {
            $block->estado = 'pendiente';
            $block->save();
            return;
        }

        $assignmentsByElementId = ElementAssignment::where('proceso_id', $procesoId)
            ->whereIn('elemento_id', $childIds)
            ->get()
            ->groupBy('elemento_id');

        $approvalsByElementId = ElementApproval::where('proceso_id', $procesoId)
            ->whereIn('elemento_id', $childIds)
            ->get()
            ->groupBy('elemento_id');

        $approved = 0;
        $rejected = 0;
        $incomplete = 0;

        foreach ($childIds as $childId) {
            $assigneeIds = $assignmentsByElementId
                ->get($childId, collect())
                ->pluck('usuario_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            $status = $this->resolveDecisionSummary(
                $assigneeIds,
                $approvalsByElementId->get($childId, collect()),
                true
            );

            if ($status === 'aprobado') {
                $approved++;
                continue;
            }

            if ($status === 'rechazado') {
                $rejected++;
                continue;
            }

            if ($status === 'incompleto') {
                $incomplete++;
            }
        }

        if ($approved === $total) {
            $block->estado = 'aprobado';
        } elseif ($rejected === $total) {
            $block->estado = 'rechazado';
        } elseif ($incomplete > 0 || $rejected > 0) {
            $block->estado = 'incompleto';
        } else {
            $block->estado = 'pendiente';
        }

        $block->save();
    }

    /**
     * Aprueba un elemento y en cascada todos sus descendientes activos.
     *
     * - Si pasas una FUENTE (hoja): aprueba solo esa fuente.
     * - Si pasas una PAUTA:        aprueba la pauta + todas sus fuentes.
     * - Si pasas una DIMENSIÓN:    aprueba la dimensión + pautas + fuentes.
     *
     * Devuelve la aprobación del elemento raíz solicitado.
     */
    public function approveElemento(
        int $elementoId,
        int $procesoId,
        ?string $comentario = null
    ): array {
        $elemento = StructureElement::find($elementoId);
        if (!$elemento) {
            throw new \InvalidArgumentException('El elemento especificado no existe.');
        }

        $existing = $this->getApprovalByElementAndProcess($elementoId, $procesoId);
        if ($existing && $existing->estado === 'aprobado') {
            throw new \InvalidArgumentException('El elemento ya está aprobado.');
        }

        $usuarioId  = Auth::id();
        $allIds     = $this->collectDescendantIds($elementoId);

        $result = DB::transaction(function () use ($allIds, $elementoId, $procesoId, $usuarioId, $comentario) {
            $root     = null;
            $cascada  = [];
            foreach ($allIds as $id) {
                $targetUserIds = $id === $elementoId
                    ? [(int) $usuarioId]
                    : $this->getTargetAssigneeIds($id, $procesoId, null);

                if (empty($targetUserIds)) {
                    $targetUserIds = [(int) $usuarioId];
                }

                foreach ($targetUserIds as $targetUserId) {
                    $approval = ElementApproval::updateOrCreate(
                        [
                            'elemento_id' => $id,
                            'proceso_id'  => $procesoId,
                            'usuario_id'  => $targetUserId,
                        ],
                        ['estado' => 'aprobado', 'comentario' => $comentario]
                    )->load(['elemento', 'process', 'user']);

                    if ($id === $elementoId) {
                        $root = $approval;
                    } else {
                        $cascada[] = $approval;
                    }
                }
            }
            return ['raiz' => $root, 'cascada' => $cascada];
        });

        event(new ElementApproved($result['raiz']));
        $total = count($allIds);
        $desc  = $total > 1 ? " (+ {$total} nodos en cascada)" : '';
        AuditLogService::log(
            'aprobar',
            "Elemento aprobado: {$elemento->nomenclatura} (ID: {$elementoId}){$desc}",
            'Aprobación Elements'
        );

        // NUEVO (HU-notificaciones): por cada elemento aprobado (pauta + sus fuentes),
        // busca quién tiene asignado ese elemento y le manda notificación interna (solo app).
        // Va fuera de la transacción: si falla el envío no revierte la aprobación.
        foreach ($allIds as $elementId) {
            try {
                $assignedUserIds = ElementAssignment::where('elemento_id', $elementId)
                    ->where('proceso_id', $procesoId)
                    ->pluck('usuario_id')
                    ->unique()
                    ->values()
                    ->toArray();

                if (!empty($assignedUserIds)) {
                    $el = StructureElement::find($elementId);
                    NotificationService::createMany(
                        $assignedUserIds,
                        [
                            'tipo_evento' => Notification::TIPO_APROBACION_ELEMENTO,
                            'titulo'      => "Elemento {$el->nomenclatura} aprobado",
                            'mensaje'     => "El elemento {$el->nomenclatura} — {$el->descripcion} ha sido aprobado para el proceso #{$procesoId}.",
                            'enlace'      => "/elementos/{$elementoId}/aprobaciones",
                        ]
                    );
                }
            } catch (\Throwable $excepcion) {
                logger()->error('ElementApproved notification failed', [
                    'elemento_id' => $elementId,
                    'error'       => $excepcion->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Rechaza un elemento y en cascada todos sus descendientes activos.
     *
     * - Si pasas una FUENTE (hoja): rechaza solo esa fuente.
     * - Si pasas una PAUTA:        rechaza la pauta + todas sus fuentes.
     * - Si pasas una DIMENSIÓN:    rechaza la dimensión + pautas + fuentes.
     *
     * Devuelve el rechazo del elemento raíz solicitado.
     * TODO HU-009: también actualizará ELEMENTO_ASIGNACION a estado Rechazado.
     */
    public function rejectElemento(
        int $elementoId,
        int $procesoId,
        ?string $comentario = null,
        ?string $fechaLimite = null
    ): array {
        $elemento = StructureElement::find($elementoId);
        if (!$elemento) {
            throw new \InvalidArgumentException('El elemento especificado no existe.');
        }

        $existing = $this->getApprovalByElementAndProcess($elementoId, $procesoId);
        if ($existing && $existing->estado === 'rechazado') {
            throw new \InvalidArgumentException('El elemento ya está rechazado.');
        }

        $usuarioId  = Auth::id();
        $allIds     = $this->collectDescendantIds($elementoId);

        $result = DB::transaction(function () use ($allIds, $elementoId, $procesoId, $usuarioId, $comentario, $fechaLimite) {
            $root    = null;
            $cascada = [];
            foreach ($allIds as $id) {
                $targetUserIds = $id === $elementoId
                    ? [(int) $usuarioId]
                    : $this->getTargetAssigneeIds($id, $procesoId, null);

                if (empty($targetUserIds)) {
                    $targetUserIds = [(int) $usuarioId];
                }

                foreach ($targetUserIds as $targetUserId) {
                    $approval = ElementApproval::updateOrCreate(
                        [
                            'elemento_id' => $id,
                            'proceso_id'  => $procesoId,
                            'usuario_id'  => $targetUserId,
                        ],
                        [
                            'estado'             => 'rechazado',
                            'comentario'         => $comentario,
                            'nueva_fecha_limite' => $fechaLimite,
                        ]
                    )->load(['elemento', 'process', 'user']);

                    if ($id === $elementoId) {
                        $root = $approval;
                    } else {
                        $cascada[] = $approval;
                    }
                }
            }

            // Resetear asignaciones a Pendiente para que los responsables reenvíen
            $assignmentUpdate = ['estado' => ElementAssignment::ESTADO_PENDIENTE];
            if ($comentario !== null) {
                $assignmentUpdate['comentario'] = $comentario;
            }
            if ($fechaLimite !== null) {
                $assignmentUpdate['fecha_limite'] = $fechaLimite;
            }
            ElementAssignment::whereIn('elemento_id', $allIds)
                ->where('proceso_id', $procesoId)
                ->update($assignmentUpdate);

            return ['raiz' => $root, 'cascada' => $cascada];
        });

        event(new ElementRejected($result['raiz']));
        $total = count($allIds);
        $desc  = $total > 1 ? " (+ {$total} nodos en cascada)" : '';
        AuditLogService::log(
            'rechazar',
            "Elemento rechazado: {$elemento->nomenclatura} (ID: {$elementoId}){$desc}",
            'Aprobación Elements'
        );

        // NUEVO (HU-notificaciones): por cada elemento rechazado (pauta + sus fuentes),
        // busca quién tiene asignado ese elemento y le manda email + notificación interna.
        // Incluye el comentario del evaluador y la nueva fecha límite si se proporcionaron.
        $deadlineMessage = $fechaLimite ? " Nueva fecha límite: {$fechaLimite}." : '';
        $reviewerComment = $comentario ? " Observación del evaluador: {$comentario}." : '';

        foreach ($allIds as $elementId) {
            try {
                $assignedUserIds = ElementAssignment::where('elemento_id', $elementId)
                    ->where('proceso_id', $procesoId)
                    ->pluck('usuario_id')
                    ->unique()
                    ->values()
                    ->toArray();

                if (!empty($assignedUserIds)) {
                    $el = StructureElement::find($elementId);
                    NotificationService::createMany(
                        $assignedUserIds,
                        [
                            'tipo_evento' => Notification::TIPO_RECHAZO_ELEMENTO,
                            'titulo'      => "Elemento {$el->nomenclatura} rechazado — se requieren correcciones",
                            'mensaje'     => "El elemento {$el->nomenclatura} — {$el->descripcion} ha sido rechazado para el proceso #{$procesoId}.{$reviewerComment}{$deadlineMessage} Por favor revisa y reenvía.",
                            'enlace'      => "/elementos/{$elementoId}/aprobaciones",
                        ]
                    );
                }
            } catch (\Throwable $excepcion) {
                logger()->error('ElementRejected notification failed', [
                    'elemento_id' => $elementId,
                    'error'       => $excepcion->getMessage(),
                ]);
            }
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Aprobación / rechazo individual de hijo (fuente dentro de pauta)
    // -----------------------------------------------------------------------

    /**
     * Aprueba un elemento hijo (fuente) individualmente dentro del bloque padre.
     *
     * Regla de bloqueo: si el bloque padre está 'incompleto', un hijo ya
     * 'aprobado' no puede volver a tocarse.
     *
     * Tras guardar, recalcula el estado del bloque padre automáticamente.
     *
     * @throws \InvalidArgumentException Si el hijo no pertenece al padre o está inactivo.
     * @throws \LogicException           Si el hijo ya está aprobado y el bloque es 'incompleto'.
     */
    public function approveIndividualChild(
        int $padreId,
        int $hijoId,
        int $procesoId,
        ?int $responsableUsuarioId = null
    ): array {
        // Validaciones fuera de la transacción para que el Handler las capture directamente
        $hijo = StructureElement::where('elemento_id', $hijoId)
            ->where('padre_id', $padreId)
            ->where('activo', true)
            ->firstOrFail();

        if (!$hijo) {
            throw new \InvalidArgumentException(
                'El elemento hijo no existe, no pertenece al padre indicado o está inactivo.'
            );
        }

        $padreApproval = ElementApproval::where('elemento_id', $padreId)
            ->where('proceso_id', $procesoId)
            ->first();

        $targetUserIds = $this->getTargetAssigneeIds($hijoId, $procesoId, $responsableUsuarioId);
        if (empty($targetUserIds)) {
            $targetUserIds = [(int) Auth::id()];
        }

        $existing = ElementApproval::where('elemento_id', $hijoId)
            ->where('proceso_id', $procesoId)
            ->whereIn('usuario_id', $targetUserIds)
            ->get();

        $existingSummary = $this->resolveDecisionSummary($targetUserIds, $existing, true);
        if ($existingSummary === 'aprobado' && $padreApproval && $padreApproval->estado === 'incompleto') {
            throw new \LogicException(
                'Este elemento ya fue aprobado y está bloqueado mientras el bloque tenga estado incompleto.'
            );
        }

        $result = DB::transaction(function () use ($padreId, $hijoId, $procesoId, $hijo, $targetUserIds) {
            $usuarioId = Auth::id();

            $parentApproval = ElementApproval::firstOrCreate(
                ['elemento_id' => $padreId, 'proceso_id' => $procesoId],
                ['usuario_id' => $usuarioId, 'estado' => 'pendiente', 'comentario' => null]
            );

            $childApprovals = [];
            foreach ($targetUserIds as $targetUserId) {
                $childApprovals[] = ElementApproval::updateOrCreate(
                    [
                        'elemento_id' => $hijoId,
                        'proceso_id'  => $procesoId,
                        'usuario_id'  => $targetUserId,
                    ],
                    [
                        'estado'     => 'aprobado',
                        'comentario' => null,
                    ]
                )->load(['elemento']);
            }

            $this->recalculateParentState($padreId, $procesoId);

            $padre = StructureElement::find($padreId);
            AuditLogService::log(
                'aprobar',
                "Hijo '{$hijo->nomenclatura}' aprobado individualmente en bloque '{$padre->nomenclatura}'",
                'Aprobación Elements'
            );

            return [
                'padre_approval' => $parentApproval->fresh()->load(['elemento', 'process', 'user']),
                'hijo_approval'  => $childApprovals[0] ?? null,
                'hijo_approvals' => $childApprovals,
            ];
        });

        // NUEVO (HU-notificaciones): notifica al usuario asignado al hijo aprobado.
        // Solo canal interno (app), no email — aprobación no requiere acción urgente.
        try {
            $assignedUserIds = array_values(array_unique($targetUserIds));

            if (!empty($assignedUserIds)) {
                $hijoEl = $result['hijo_approval']?->elemento;
                if (!$hijoEl) {
                    return $result;
                }
                NotificationService::createMany(
                    $assignedUserIds,
                    [
                        'tipo_evento' => Notification::TIPO_APROBACION_ELEMENTO,
                        'titulo'      => "Elemento {$hijoEl->nomenclatura} aprobado",
                        'mensaje'     => "El elemento {$hijoEl->nomenclatura} — {$hijoEl->descripcion} ha sido aprobado para el proceso #{$procesoId}.",
                        'enlace'      => "/elementos/{$padreId}/aprobaciones",
                    ]
                );
            }
        } catch (\Throwable $excepcion) {
            logger()->error('ChildElementApproved notification failed', [
                'elemento_id' => $hijoId,
                'error'       => $excepcion->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Rechaza un elemento hijo (fuente) individualmente dentro del bloque padre.
     *
     * Regla de bloqueo: si el bloque padre está 'incompleto', un hijo ya
     * 'aprobado' no puede cambiarse a rechazado.
     *
     * Al rechazar se guarda el comentario en APROBACION_ELEMENTO del hijo
     * y se actualiza ELEMENTO_ASIGNACION con la nueva fecha límite (si se
     * proporciona), reseteando el estado de la asignación a 'Pendiente'.
     *
     * Tras guardar, recalcula el estado del bloque padre automáticamente.
     *
     * @throws \InvalidArgumentException Si el hijo no pertenece al padre o está inactivo.
     * @throws \LogicException           Si el hijo ya está aprobado y el bloque es 'incompleto'.
     */
    public function rejectIndividualChild(
        int $padreId,
        int $hijoId,
        int $procesoId,
        ?string $comentario = null,
        ?string $nuevaFechaLimite = null,
        ?int $responsableUsuarioId = null
    ): array {
        // Validaciones fuera de la transacción para que el Handler las capture directamente
        $hijo = StructureElement::where('elemento_id', $hijoId)
            ->where('padre_id', $padreId)
            ->where('activo', true)
            ->first();

        if (!$hijo) {
            throw new \InvalidArgumentException(
                'El elemento hijo no existe, no pertenece al padre indicado o está inactivo.'
            );
        }

        $padreApproval = ElementApproval::where('elemento_id', $padreId)
            ->where('proceso_id', $procesoId)
            ->first();

        $targetUserIds = $this->getTargetAssigneeIds($hijoId, $procesoId, $responsableUsuarioId);
        if (empty($targetUserIds)) {
            $targetUserIds = [(int) Auth::id()];
        }

        $existing = ElementApproval::where('elemento_id', $hijoId)
            ->where('proceso_id', $procesoId)
            ->whereIn('usuario_id', $targetUserIds)
            ->get();

        $existingSummary = $this->resolveDecisionSummary($targetUserIds, $existing, true);
        if ($existingSummary === 'aprobado' && $padreApproval && $padreApproval->estado === 'incompleto') {
            throw new \LogicException(
                'Este elemento ya fue aprobado y está bloqueado mientras el bloque tenga estado incompleto.'
            );
        }

        $result = DB::transaction(function () use ($padreId, $hijoId, $procesoId, $comentario, $nuevaFechaLimite, $hijo, $targetUserIds) {
            $usuarioId = Auth::id();

            $parentApproval = ElementApproval::firstOrCreate(
                ['elemento_id' => $padreId, 'proceso_id' => $procesoId],
                ['usuario_id' => $usuarioId, 'estado' => 'pendiente', 'comentario' => null]
            );

            $childApprovals = [];
            foreach ($targetUserIds as $targetUserId) {
                $childApprovals[] = ElementApproval::updateOrCreate(
                    [
                        'elemento_id' => $hijoId,
                        'proceso_id'  => $procesoId,
                        'usuario_id'  => $targetUserId,
                    ],
                    [
                        'estado'             => 'rechazado',
                        'comentario'         => $comentario,
                        'nueva_fecha_limite' => $nuevaFechaLimite,
                    ]
                )->load(['elemento']);
            }

            // Devolver la asignación al responsable: resetear estado + guardar observación + nueva fecha
            $assignmentUpdate = ['estado' => ElementAssignment::ESTADO_PENDIENTE];
            if ($comentario !== null) {
                $assignmentUpdate['comentario'] = $comentario;
            }
            if ($nuevaFechaLimite !== null) {
                $assignmentUpdate['fecha_limite'] = $nuevaFechaLimite;
            }

            $assignmentQuery = ElementAssignment::where('elemento_id', $hijoId)
                ->where('proceso_id', $procesoId);

            if (!empty($targetUserIds)) {
                $assignmentQuery->whereIn('usuario_id', $targetUserIds);
            }

            $assignmentQuery->update($assignmentUpdate);

            $this->recalculateParentState($padreId, $procesoId);

            $padre = StructureElement::find($padreId);
            AuditLogService::log(
                'rechazar',
                "Hijo '{$hijo->nomenclatura}' rechazado individualmente en bloque '{$padre->nomenclatura}'",
                'Aprobación Elements'
            );

            return [
                'padre_approval' => $parentApproval->fresh()->load(['elemento', 'process', 'user']),
                'hijo_approval'  => $childApprovals[0] ?? null,
                'hijo_approvals' => $childApprovals,
            ];
        });

        // NUEVO (HU-notificaciones): notifica al usuario asignado al hijo rechazado.
        // Canal email + app — rechazo individual requiere que el profesor corrija y reenvíe.
        try {
            $assignedUserIds = array_values(array_unique($targetUserIds));

            if (!empty($assignedUserIds)) {
                $hijoEl = $result['hijo_approval']?->elemento;
                if (!$hijoEl) {
                    return $result;
                }
                $deadlineMessage = $nuevaFechaLimite ? " Nueva fecha límite: {$nuevaFechaLimite}." : '';
                $reviewerComment = $comentario ? " Observación del evaluador: {$comentario}." : '';
                NotificationService::createMany(
                    $assignedUserIds,
                    [
                        'tipo_evento' => Notification::TIPO_RECHAZO_ELEMENTO,
                        'titulo'      => "Elemento {$hijoEl->nomenclatura} rechazado — se requieren correcciones",
                        'mensaje'     => "El elemento {$hijoEl->nomenclatura} — {$hijoEl->descripcion} ha sido rechazado para el proceso #{$procesoId}.{$reviewerComment}{$deadlineMessage} Por favor revisa y reenvía.",
                        'enlace'      => "/elementos/{$padreId}/aprobaciones",
                    ]
                );
            }
        } catch (\Throwable $excepcion) {
            logger()->error('ChildElementRejected notification failed', [
                'elemento_id' => $hijoId,
                'error'       => $excepcion->getMessage(),
            ]);
        }

        return $result;
    }
}
