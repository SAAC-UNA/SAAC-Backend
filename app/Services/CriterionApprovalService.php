<?php

namespace App\Services;

use App\Models\CriterionApproval;
use App\Models\EvidenceApproval;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

/**
 * Servicio de aprobacion de criterios con Eloquent ORM.
 */
class CriterionApprovalService
{
    public function __construct() {}

    /**
     * Listar aprobaciones de criterios.
     * Si el usuario es Profesor, filtra solo criterios con evidencias asignadas a el.
     */
    public function listApprovals(?string $estado = null)
    {
        $query = CriterionApproval::with(['process', 'user']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && $user->hasRole('Profesor')) {
            $query->whereHas(
                'criterion.evidences.assignments',
                fn($q) => $q->where('usuario_id', $user->usuario_id)
            );
        }

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        return $query->get();
    }

    /**
     * Obtener una aprobacion especifica por ID.
     */
    public function getApproval(int $approvalId): ?CriterionApproval
    {
        return CriterionApproval::with(['process', 'user', 'evidenceApprovals.evidence'])->find($approvalId);
    }

    /**
     * Obtener una aprobacion especifica por criterio y proceso.
     */
    public function getApprovalByCriterionAndProcess(int $criterionId, int $processId): ?CriterionApproval
    {
        return CriterionApproval::where('criterio_id', $criterionId)
            ->where('proceso_id', $processId)
            ->first();
    }

    /**
     * Aprobar un criterio (crea o actualiza la aprobacion) y aprueba cada evidencia.
     */
    public function approveCriterion(
        int $criterionId,
        int $processId,
        int $userId,
        ?string $comment = null
    ): array {
        $result = DB::transaction(function () use ($criterionId, $processId, $userId, $comment) {
            $approval = CriterionApproval::updateOrCreate(
                ['criterio_id' => $criterionId, 'proceso_id' => $processId],
                ['usuario_id'  => $userId, 'estado' => 'aprobado', 'comentario' => $comment]
            );

            $evidences = [];
            Evidence::where('criterio_id', $criterionId)->active()->each(
                function ($evidence) use ($processId, $approval, $userId, &$evidences) {
                    $targetUserIds = EvidenceAssignment::where('evidencia_id', $evidence->evidencia_id)
                        ->where('proceso_id', $processId)
                        ->pluck('usuario_id')
                        ->map(fn($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->toArray();

                    if (empty($targetUserIds)) {
                        $targetUserIds = [(int) $userId];
                    }

                    foreach ($targetUserIds as $targetUserId) {
                        $evidences[] = EvidenceApproval::updateOrCreate(
                            [
                                'evidencia_id' => $evidence->evidencia_id,
                                'proceso_id'   => $processId,
                                'usuario_id'   => $targetUserId,
                            ],
                            [
                                'criterio_aprobacion_id' => $approval->aprobacion_criterio_id,
                                'estado'                 => 'aprobado',
                                'comentario'             => null,
                            ]
                        )->load(['evidence']);
                    }
                }
            );

            return [
                'root'      => $approval->load(['process', 'user']),
                'evidences' => $evidences,
            ];
        });

        // Notificar por cada evidencia aprobada individualmente — fuera de la transacción para que
        // un error de correo nunca revierta la aprobación. Cada profesor recibe la notificación
        // de la evidencia que tiene asignada, no un mensaje genérico del criterio.
        collect($result['evidences'])
            ->groupBy('evidencia_id')
            ->each(function ($group) use ($processId, $criterionId) {
                $evidenceApproval = $group->first();
            try {
                $assignedUserIds = EvidenceAssignment::where('evidencia_id', $evidenceApproval->evidencia_id)
                    ->where('proceso_id', $processId)
                    ->pluck('usuario_id')
                    ->unique()
                    ->values()
                    ->toArray();

                if (!empty($assignedUserIds)) {
                    $evidence = $evidenceApproval->evidence;
                    NotificationService::createMany(
                        $assignedUserIds,
                        [
                            'tipo_evento' => Notification::TIPO_APROBACION_EVIDENCIA,
                            'titulo'      => "Evidencia {$evidence->nomenclatura} aprobada",
                            'mensaje'     => "La evidencia {$evidence->nomenclatura} — {$evidence->descripcion} ha sido aprobada para el proceso #{$processId}.",
                            'enlace'      => "/criterios/{$criterionId}/evidencias/aprobaciones",
                        ]
                    );
                }
            } catch (\Throwable $exception) {
                logger()->error('BlockApproval evidence notification failed', [
                    'evidencia_id' => $evidenceApproval->evidencia_id,
                    'criterio_id'  => $criterionId,
                    'error'        => $exception->getMessage(),
                ]);
            }
            });

        return $result;
    }

    /**
     * Rechazar un criterio y rechazar cada evidencia del criterio.
     */
    public function rejectCriterion(
        int $criterionId,
        int $processId,
        int $userId,
        ?string $comment = null,
        ?string $newDeadline = null
    ): array {
        $result = DB::transaction(function () use ($criterionId, $processId, $userId, $comment, $newDeadline) {
            $approval = CriterionApproval::updateOrCreate(
                ['criterio_id' => $criterionId, 'proceso_id' => $processId],
                ['usuario_id'  => $userId, 'estado' => 'rechazado', 'comentario' => $comment, 'nueva_fecha_limite' => $newDeadline]
            );

            $evidences   = [];
            $evidenceIds = [];
            Evidence::where('criterio_id', $criterionId)->active()->each(
                function ($evidence) use ($processId, $approval, $userId, $comment, $newDeadline, &$evidences, &$evidenceIds) {
                    $evidenceIds[] = $evidence->evidencia_id;
                    $targetUserIds = EvidenceAssignment::where('evidencia_id', $evidence->evidencia_id)
                        ->where('proceso_id', $processId)
                        ->pluck('usuario_id')
                        ->map(fn($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->toArray();

                    if (empty($targetUserIds)) {
                        $targetUserIds = [(int) $userId];
                    }

                    foreach ($targetUserIds as $targetUserId) {
                        $evidenceUpdate = [
                            'criterio_aprobacion_id' => $approval->aprobacion_criterio_id,
                            'estado'                 => 'rechazado',
                            'nueva_fecha_limite'     => $newDeadline,
                        ];
                        if ($comment !== null) {
                            $evidenceUpdate['comentario'] = $comment;
                        }

                        $evidences[] = EvidenceApproval::updateOrCreate(
                            [
                                'evidencia_id' => $evidence->evidencia_id,
                                'proceso_id'   => $processId,
                                'usuario_id'   => $targetUserId,
                            ],
                            $evidenceUpdate
                        )->load(['evidence']);
                    }
                }
            );

            // Resetear asignaciones a Pendiente para que los profesores reenvíen
            $assignmentUpdate = ['estado' => EvidenceAssignment::ESTADO_PENDIENTE];
            if ($comment !== null) {
                $assignmentUpdate['comentario'] = $comment;
            }
            if ($newDeadline !== null) {
                $assignmentUpdate['fecha_limite'] = $newDeadline;
            }
            EvidenceAssignment::where('proceso_id', $processId)
                ->whereIn('evidencia_id', $evidenceIds)
                ->update($assignmentUpdate);

            return [
                'root'      => $approval->load(['process', 'user']),
                'evidences' => $evidences,
            ];
        });

        // Notificar por cada evidencia rechazada individualmente — fuera de la transacción para que
        // un error de correo nunca revierta el rechazo. Cada profesor recibe la notificación
        // de la evidencia que tiene asignada, no un mensaje genérico del criterio.
        $deadlineMessage = $newDeadline ? " Nueva fecha límite: {$newDeadline}." : '';
        $reviewerComment = $comment ? " Observación del evaluador: {$comment}." : '';

        collect($result['evidences'])
            ->groupBy('evidencia_id')
            ->each(function ($group) use ($processId, $criterionId, $reviewerComment, $deadlineMessage) {
                $evidenceApproval = $group->first();
            try {
                $assignedUserIds = EvidenceAssignment::where('evidencia_id', $evidenceApproval->evidencia_id)
                    ->where('proceso_id', $processId)
                    ->pluck('usuario_id')
                    ->unique()
                    ->values()
                    ->toArray();

                if (!empty($assignedUserIds)) {
                    $evidence = $evidenceApproval->evidence;
                    NotificationService::createMany(
                        $assignedUserIds,
                        [
                            'tipo_evento' => Notification::TIPO_RECHAZO_EVIDENCIA,
                            'titulo'      => "Evidencia {$evidence->nomenclatura} rechazada — se requieren correcciones",
                            'mensaje'     => "La evidencia {$evidence->nomenclatura} — {$evidence->descripcion} ha sido rechazada para el proceso #{$processId}.{$reviewerComment}{$deadlineMessage} Por favor revisa y reenvía.",
                            'enlace'      => "/criterios/{$criterionId}/evidencias/aprobaciones",
                        ]
                    );
                }
            } catch (\Throwable $exception) {
                logger()->error('BlockRejection evidence notification failed', [
                    'evidencia_id' => $evidenceApproval->evidencia_id,
                    'criterio_id'  => $criterionId,
                    'error'        => $exception->getMessage(),
                ]);
            }
            });

        return $result;
    }

    // -------------------------------------------------------------------------
    // Aprobaciones individuales de evidencias (HU-010 individual)
    // -------------------------------------------------------------------------

    /**
     * Recalcula y persiste el estado del bloque según las decisiones individuales actuales.
     *
     * Reglas:
     *   - Todas aprobadas                         → 'aprobado'
     *   - Todas rechazadas                        → 'rechazado'
     *   - Al menos 1 rechazada (no todas iguales) → 'incompleto'
     *   - Ninguna decisión aún                    → 'pendiente'
     */
    private function getTargetAssigneeIds(int $evidenceId, int $processId, ?int $targetUserId): array
    {
        $query = EvidenceAssignment::where('evidencia_id', $evidenceId)
            ->where('proceso_id', $processId);

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
                'El responsable seleccionado no tiene asignada esta evidencia en el proceso indicado.'
            );
        }

        return $userIds;
    }

    /**
     * @return array<int, EvidenceApproval>
     */
    private function getLatestApprovalsByUser(Collection $approvals): array
    {
        $latest = [];

        $approvals->groupBy('usuario_id')->each(function (Collection $group, $userId) use (&$latest) {
            $ordered = $group->sortByDesc(
                fn(EvidenceApproval $approval) => $approval->updated_at?->getTimestamp() ?? $approval->aprobacion_evidencia_id
            );
            $latest[(int) $userId] = $ordered->first();
        });

        return $latest;
    }

    private function resolveDecisionSummary(
        array $targetUserIds,
        Collection $approvals,
        bool $allowIncomplete
    ): array {
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
            return ['status' => 'pendiente', 'comentario' => null];
        }

        $approvedCount = count(array_filter($states, fn($state) => $state === 'aprobado'));
        $rejectedCount = count(array_filter($states, fn($state) => $state === 'rechazado'));

        $comment = null;
        foreach ($latestByUser as $approval) {
            if ($approval->estado === 'rechazado' && !empty($approval->comentario)) {
                $comment = $approval->comentario;
                break;
            }
        }

        if ($approvedCount === $total) {
            return ['status' => 'aprobado', 'comentario' => null];
        }

        if ($rejectedCount === $total) {
            return ['status' => 'rechazado', 'comentario' => $comment];
        }

        if ($allowIncomplete && $rejectedCount >= 1) {
            return ['status' => 'incompleto', 'comentario' => $comment];
        }

        if ($rejectedCount >= 1) {
            return ['status' => 'rechazado', 'comentario' => $comment];
        }

        return ['status' => 'pendiente', 'comentario' => null];
    }

    private function recalculateBlockState(CriterionApproval $block): void
    {
        $evidenceIds = Evidence::where('criterio_id', $block->criterio_id)
            ->active()
            ->pluck('evidencia_id');

        $total = $evidenceIds->count();

        if ($total === 0) {
            $block->estado = 'pendiente';
            $block->save();
            return;
        }

        $assignmentsByEvidenceId = EvidenceAssignment::where('proceso_id', $block->proceso_id)
            ->whereIn('evidencia_id', $evidenceIds)
            ->get()
            ->groupBy('evidencia_id');

        $approvalsByEvidenceId = EvidenceApproval::where('criterio_aprobacion_id', $block->aprobacion_criterio_id)
            ->whereIn('evidencia_id', $evidenceIds)
            ->get()
            ->groupBy('evidencia_id');

        $approved = 0;
        $rejected = 0;
        $incomplete = 0;

        foreach ($evidenceIds as $evidenceId) {
            $assigneeIds = $assignmentsByEvidenceId
                ->get($evidenceId, collect())
                ->pluck('usuario_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            $summary = $this->resolveDecisionSummary(
                $assigneeIds,
                $approvalsByEvidenceId->get($evidenceId, collect()),
                true
            );

            if ($summary['status'] === 'aprobado') {
                $approved++;
                continue;
            }

            if ($summary['status'] === 'rechazado') {
                $rejected++;
                continue;
            }

            if ($summary['status'] === 'incompleto') {
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
     * Aprobar una evidencia individual dentro de un bloque de criterio.
     *
     * Regla de bloqueo: si el bloque está 'incompleto', una evidencia ya
     * 'aprobado' no puede volver a tocarse (está bloqueada).
     *
     * Tras guardar, recalcula el estado del bloque automáticamente.
     *
     * @throws \InvalidArgumentException Si la evidencia no pertenece al criterio.
     * @throws \LogicException           Si la evidencia ya está aprobada y el bloque es 'incompleto'.
     */
    public function approveIndividualEvidence(
        int $criterionId,
        int $evidenceId,
        int $processId,
        int $userId,
        ?int $responsibleUserId = null
    ): array {
        $targetUserIds = [];

        $result = DB::transaction(function () use ($criterionId, $evidenceId, $processId, $userId, $responsibleUserId, &$targetUserIds) {
            $evidence = Evidence::where('evidencia_id', $evidenceId)
                ->where('criterio_id', $criterionId)
                ->active()
                ->first();

            if (!$evidence) {
                throw new \InvalidArgumentException(
                    'La evidencia no existe, no pertenece al criterio indicado o está inactiva.'
                );
            }

            // Obtener o crear el bloque
            $criterionApproval = CriterionApproval::firstOrCreate(
                ['criterio_id' => $criterionId, 'proceso_id' => $processId],
                ['usuario_id' => $userId, 'estado' => 'pendiente', 'comentario' => null]
            );

            $targetUserIds = $this->getTargetAssigneeIds($evidenceId, $processId, $responsibleUserId);
            if (empty($targetUserIds)) {
                $targetUserIds = [(int) $userId];
            }

            // Bloqueo: evidencias ya aprobadas no se tocan cuando el bloque es 'incompleto'
            $existing = EvidenceApproval::where('evidencia_id', $evidenceId)
                ->where('proceso_id', $processId)
                ->whereIn('usuario_id', $targetUserIds)
                ->get();

            $existingSummary = $this->resolveDecisionSummary($targetUserIds, $existing, true);
            if ($existingSummary['status'] === 'aprobado' && $criterionApproval->estado === 'incompleto') {
                throw new \LogicException(
                    'Esta evidencia ya fue aprobada y está bloqueada mientras el bloque tenga estado incompleto.'
                );
            }

            $evidenceApprovals = [];
            foreach ($targetUserIds as $targetUserId) {
                $evidenceApprovals[] = EvidenceApproval::updateOrCreate(
                    [
                        'evidencia_id' => $evidenceId,
                        'proceso_id'   => $processId,
                        'usuario_id'   => $targetUserId,
                    ],
                    [
                        'criterio_aprobacion_id' => $criterionApproval->aprobacion_criterio_id,
                        'estado'                 => 'aprobado',
                        'comentario'             => null,
                    ]
                )->load(['evidence']);
            }

            $this->recalculateBlockState($criterionApproval);

            return [
                'criterion_approval' => $criterionApproval->fresh()->load(['process', 'user', 'evidenceApprovals']),
                'evidence_approval'  => $evidenceApprovals[0] ?? null,
                'evidence_approvals' => $evidenceApprovals,
            ];
        });

        // Notificar al profesor asignado — fuera de la transacción, solo canal interno
        // (aprobación = buena noticia, no requiere acción urgente).
        try {
            $assignedUserIds = array_values(array_unique($targetUserIds));

            if (!empty($assignedUserIds)) {
                $evidence = $result['evidence_approval']?->evidence;
                if (!$evidence) {
                    return $result;
                }
                NotificationService::createMany(
                    $assignedUserIds,
                    [
                        'tipo_evento' => Notification::TIPO_APROBACION_EVIDENCIA,
                        'titulo'      => "Evidencia {$evidence->nomenclatura} aprobada",
                        'mensaje'     => "La evidencia {$evidence->nomenclatura} — {$evidence->descripcion} ha sido aprobada para el proceso #{$processId}.",
                        'enlace'      => "/criterios/{$criterionId}/evidencias/aprobaciones",
                    ]
                );
            }
        } catch (\Throwable $exception) {
            logger()->error('EvidenceApproved notification failed', [
                'evidencia_id' => $evidenceId,
                'error'        => $exception->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Rechazar una evidencia individual dentro de un bloque de criterio.
     *
     * Regla de bloqueo: si el bloque está 'incompleto', una evidencia ya
     * 'aprobado' no puede cambiarse a rechazado.
     *
     * Al rechazar se guarda el comentario (observación) en APROBACION_EVIDENCIA
     * y se actualiza EVIDENCIA_ASIGNACION con la observación y la nueva fecha
     * límite (si se proporciona), reseteando el estado de la asignación a 'Pendiente'.
     *
     * Tras guardar, recalcula el estado del bloque automáticamente.
     *
     * @throws \InvalidArgumentException Si la evidencia no pertenece al criterio.
     * @throws \LogicException           Si la evidencia ya está aprobada y el bloque es 'incompleto'.
     */
    public function rejectIndividualEvidence(
        int $criterionId,
        int $evidenceId,
        int $processId,
        int $userId,
        ?string $comment = null,
        ?string $newDeadline = null,
        ?int $responsibleUserId = null
    ): array {
        $targetUserIds = [];

        $result = DB::transaction(function () use (
            $criterionId, $evidenceId, $processId, $userId, $comment, $newDeadline, $responsibleUserId, &$targetUserIds
        ) {
            $evidence = Evidence::where('evidencia_id', $evidenceId)
                ->where('criterio_id', $criterionId)
                ->active()
                ->first();

            if (!$evidence) {
                throw new \InvalidArgumentException(
                    'La evidencia no existe, no pertenece al criterio indicado o está inactiva.'
                );
            }

            // Obtener o crear el bloque
            $criterionApproval = CriterionApproval::firstOrCreate(
                ['criterio_id' => $criterionId, 'proceso_id' => $processId],
                ['usuario_id' => $userId, 'estado' => 'pendiente', 'comentario' => null]
            );

            $targetUserIds = $this->getTargetAssigneeIds($evidenceId, $processId, $responsibleUserId);
            if (empty($targetUserIds)) {
                $targetUserIds = [(int) $userId];
            }

            // Bloqueo: evidencias ya aprobadas no se pueden rechazar cuando el bloque es 'incompleto'
            $existing = EvidenceApproval::where('evidencia_id', $evidenceId)
                ->where('proceso_id', $processId)
                ->whereIn('usuario_id', $targetUserIds)
                ->get();

            $existingSummary = $this->resolveDecisionSummary($targetUserIds, $existing, true);
            if ($existingSummary['status'] === 'aprobado' && $criterionApproval->estado === 'incompleto') {
                throw new \LogicException(
                    'Esta evidencia ya fue aprobada y está bloqueada mientras el bloque tenga estado incompleto.'
                );
            }

            // Guardar rechazo con observación en APROBACION_EVIDENCIA
            $evidenceApprovals = [];
            foreach ($targetUserIds as $targetUserId) {
                $evidenceApprovals[] = EvidenceApproval::updateOrCreate(
                    [
                        'evidencia_id' => $evidenceId,
                        'proceso_id'   => $processId,
                        'usuario_id'   => $targetUserId,
                    ],
                    [
                        'criterio_aprobacion_id' => $criterionApproval->aprobacion_criterio_id,
                        'estado'                 => 'rechazado',
                        'comentario'             => $comment,
                        'nueva_fecha_limite'     => $newDeadline,
                    ]
                )->load(['evidence']);
            }

            // Devolver la asignación al responsable: resetear estado + guardar observación + nueva fecha
            $assignmentUpdate = ['estado' => EvidenceAssignment::ESTADO_PENDIENTE];
            if ($comment !== null) {
                $assignmentUpdate['comentario'] = $comment;
            }
            if ($newDeadline !== null) {
                $assignmentUpdate['fecha_limite'] = $newDeadline;
            }

            $assignmentQuery = EvidenceAssignment::where('evidencia_id', $evidenceId)
                ->where('proceso_id', $processId);

            if (!empty($targetUserIds)) {
                $assignmentQuery->whereIn('usuario_id', $targetUserIds);
            }

            $assignmentQuery->update($assignmentUpdate);

            $this->recalculateBlockState($criterionApproval);

            return [
                'criterion_approval' => $criterionApproval->fresh()->load(['process', 'user', 'evidenceApprovals']),
                'evidence_approval'  => $evidenceApprovals[0] ?? null,
                'evidence_approvals' => $evidenceApprovals,
            ];
        });

        // Notificar al profesor asignado — fuera de la transacción, canal email+interno
        // (rechazo = requiere acción correctiva urgente).
        try {
            $assignedUserIds = array_values(array_unique($targetUserIds));

            if (!empty($assignedUserIds)) {
                $evidence = $result['evidence_approval']?->evidence;
                if (!$evidence) {
                    return $result;
                }
                $deadlineMessage  = $newDeadline ? " Nueva fecha límite: {$newDeadline}." : '';
                $reviewerComment = $comment ? " Observación del evaluador: {$comment}." : '';
                NotificationService::createMany(
                    $assignedUserIds,
                    [
                        'tipo_evento' => Notification::TIPO_RECHAZO_EVIDENCIA,
                        'titulo'      => "Evidencia {$evidence->nomenclatura} rechazada — se requieren correcciones",
                        'mensaje'     => "La evidencia {$evidence->nomenclatura} — {$evidence->descripcion} ha sido rechazada para el proceso #{$processId}.{$reviewerComment}{$deadlineMessage} Por favor revisa y reenvía.",
                        'enlace'      => "/criterios/{$criterionId}/evidencias/aprobaciones",
                    ]
                );
            }
        } catch (\Throwable $exception) {
            logger()->error('EvidenceRejected notification failed', [
                'evidencia_id' => $evidenceId,
                'error'        => $exception->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Listar el estado de aprobación individual de cada evidencia activa de un criterio.
     *
     * Las evidencias sin decisión aparecen con estado_aprobacion = 'sin_decision'.
     */
    public function listEvidenceApprovals(int $criterionId, int $processId): array
    {
        $criterionApproval = CriterionApproval::where('criterio_id', $criterionId)
            ->where('proceso_id', $processId)
            ->with(['process', 'user'])
            ->first();

        // Asignaciones del proceso para las evidencias activas del criterio
        $evidenceIds = Evidence::where('criterio_id', $criterionId)->active()->pluck('evidencia_id');
        $assignmentsByEvidenceId = EvidenceAssignment::where('proceso_id', $processId)
            ->whereIn('evidencia_id', $evidenceIds)
            ->with('user')
            ->get()
            ->groupBy('evidencia_id');

        $approvalsByEvidenceId = EvidenceApproval::where('proceso_id', $processId)
            ->whereIn('evidencia_id', $evidenceIds)
            ->get()
            ->groupBy('evidencia_id');

        $evidences = Evidence::where('criterio_id', $criterionId)
            ->active()
            ->get()
            ->map(function ($evidenceItem) use ($assignmentsByEvidenceId, $approvalsByEvidenceId) {
                $evidenceId = $evidenceItem->evidencia_id;
                $assignments = $assignmentsByEvidenceId->get($evidenceId, collect());
                $approvals = $approvalsByEvidenceId->get($evidenceId, collect());

                $assigneeIds = $assignments->pluck('usuario_id')
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->toArray();

                $summary = $this->resolveDecisionSummary($assigneeIds, $approvals, false);

                $latestByUser = $this->getLatestApprovalsByUser($approvals);
                $approvalsByUser = [];
                foreach ($latestByUser as $approvalUserId => $approval) {
                    $approvalsByUser[(string) $approvalUserId] = [
                        'approval_status' => $approval->estado,
                        'comentario_rechazo' => $approval->comentario,
                        'aprobacion_evidencia_id' => $approval->aprobacion_evidencia_id,
                        'updated_at' => $approval->updated_at,
                    ];
                }

                $latestApproval = $approvals->sortByDesc(
                    fn(EvidenceApproval $approval) => $approval->updated_at?->getTimestamp() ?? $approval->aprobacion_evidencia_id
                )->first();

                $assignment = $assignments->first();
                return [
                    'evidencia_id'            => $evidenceId,
                    'nomenclatura'            => $evidenceItem->nomenclatura,
                    'descripcion'             => $evidenceItem->descripcion,
                    'approval_status'         => $summary['status'],
                    'comentario_rechazo'      => $summary['comentario'],
                    'aprobacion_evidencia_id' => $latestApproval?->aprobacion_evidencia_id,
                    'updated_at'              => $latestApproval?->updated_at,
                    'asignacion'              => $assignment ? [
                        'estado'       => $assignment->estado,
                        'fecha_limite' => $assignment->fecha_limite,
                        'usuario_id'   => $assignment->usuario_id,
                        'usuario_nombre' => $assignment->user?->nombre,
                    ] : null,
                    'approvals_by_user'      => $approvalsByUser,
                ];
            })
            ->values();

        return [
            'criterion_approval' => $criterionApproval,
            'evidences'          => $evidences,
        ];
    }
}