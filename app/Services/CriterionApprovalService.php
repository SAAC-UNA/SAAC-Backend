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
    public function getApprovalByCriterionAndProcess(int $criterioId, int $procesoId): ?CriterionApproval
    {
        return CriterionApproval::where('criterio_id', $criterioId)
            ->where('proceso_id', $procesoId)
            ->first();
    }

    /**
     * Aprobar un criterio (crea o actualiza la aprobacion) y aprueba cada evidencia.
     */
    public function approveCriterion(
        int $criterioId,
        int $procesoId,
        int $usuarioId,
        ?string $comentario = null
    ): array {
        $result = DB::transaction(function () use ($criterioId, $procesoId, $usuarioId, $comentario) {
            $approval = CriterionApproval::updateOrCreate(
                ['criterio_id' => $criterioId, 'proceso_id' => $procesoId],
                ['usuario_id'  => $usuarioId, 'estado' => 'aprobado', 'comentario' => $comentario]
            );

            $evidencias = [];
            Evidence::where('criterio_id', $criterioId)->active()->each(
                function ($evidencia) use ($procesoId, $approval, $usuarioId, &$evidencias) {
                    $evidencias[] = EvidenceApproval::updateOrCreate(
                        ['evidencia_id' => $evidencia->evidencia_id, 'proceso_id' => $procesoId],
                        [
                            'criterio_aprobacion_id' => $approval->aprobacion_criterio_id,
                            'usuario_id'             => $usuarioId,
                            'estado'                 => 'aprobado',
                        ]
                    )->load(['evidence']);
                }
            );

            return [
                'raiz'      => $approval->load(['process', 'user']),
                'evidencias' => $evidencias,
            ];
        });

        // Notificar por cada evidencia aprobada individualmente — fuera de la transacción para que
        // un error de correo nunca revierta la aprobación. Cada profesor recibe la notificación
        // de la evidencia que tiene asignada, no un mensaje genérico del criterio.
        foreach ($result['evidencias'] as $evidenceApproval) {
            try {
                $assignedUserIds = EvidenceAssignment::where('evidencia_id', $evidenceApproval->evidencia_id)
                    ->where('proceso_id', $procesoId)
                    ->pluck('usuario_id')
                    ->unique()
                    ->values()
                    ->toArray();

                if (!empty($assignedUserIds)) {
                    $evidencia = $evidenceApproval->evidence;
                    NotificationService::createMany(
                        $assignedUserIds,
                        [
                            'tipo_evento' => Notification::TIPO_APROBACION_EVIDENCIA,
                            'titulo'      => "Evidencia {$evidencia->nomenclatura} aprobada",
                            'mensaje'     => "La evidencia {$evidencia->nomenclatura} — {$evidencia->descripcion} ha sido aprobada para el proceso #{$procesoId}.",
                            'enlace'      => "/criterios/{$criterioId}/evidencias/aprobaciones",
                        ]
                    );
                }
            } catch (\Throwable $excepcion) {
                logger()->error('BlockApproval evidence notification failed', [
                    'evidencia_id' => $evidenceApproval->evidencia_id,
                    'criterio_id'  => $criterioId,
                    'error'        => $excepcion->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Rechazar un criterio y rechazar cada evidencia del criterio.
     */
    public function rejectCriterion(
        int $criterioId,
        int $procesoId,
        int $usuarioId,
        ?string $comentario = null,
        ?string $nuevaFechaLimite = null
    ): array {
        $result = DB::transaction(function () use ($criterioId, $procesoId, $usuarioId, $comentario, $nuevaFechaLimite) {
            $approval = CriterionApproval::updateOrCreate(
                ['criterio_id' => $criterioId, 'proceso_id' => $procesoId],
                ['usuario_id'  => $usuarioId, 'estado' => 'rechazado', 'comentario' => $comentario, 'nueva_fecha_limite' => $nuevaFechaLimite]
            );

            $evidencias   = [];
            $evidenciaIds = [];
            Evidence::where('criterio_id', $criterioId)->active()->each(
                function ($evidencia) use ($procesoId, $approval, $usuarioId, $comentario, $nuevaFechaLimite, &$evidencias, &$evidenciaIds) {
                    $evidenciaIds[] = $evidencia->evidencia_id;
                    $evidenceUpdate = [
                        'criterio_aprobacion_id' => $approval->aprobacion_criterio_id,
                        'usuario_id'             => $usuarioId,
                        'estado'                 => 'rechazado',
                        'nueva_fecha_limite'     => $nuevaFechaLimite,
                    ];
                    if ($comentario !== null) {
                        $evidenceUpdate['comentario'] = $comentario;
                    }
                    $evidencias[] = EvidenceApproval::updateOrCreate(
                        ['evidencia_id' => $evidencia->evidencia_id, 'proceso_id' => $procesoId],
                        $evidenceUpdate
                    )->load(['evidence']);
                }
            );

            // Resetear asignaciones a Pendiente para que los profesores reenvíen
            $assignmentUpdate = ['estado' => EvidenceAssignment::ESTADO_PENDIENTE];
            if ($comentario !== null) {
                $assignmentUpdate['comentario'] = $comentario;
            }
            if ($nuevaFechaLimite !== null) {
                $assignmentUpdate['fecha_limite'] = $nuevaFechaLimite;
            }
            EvidenceAssignment::where('proceso_id', $procesoId)
                ->whereIn('evidencia_id', $evidenciaIds)
                ->update($assignmentUpdate);

            return [
                'raiz'      => $approval->load(['process', 'user']),
                'evidencias' => $evidencias,
            ];
        });

        // Notificar por cada evidencia rechazada individualmente — fuera de la transacción para que
        // un error de correo nunca revierta el rechazo. Cada profesor recibe la notificación
        // de la evidencia que tiene asignada, no un mensaje genérico del criterio.
        $deadlineMessage = $nuevaFechaLimite ? " Nueva fecha límite: {$nuevaFechaLimite}." : '';
        $reviewerComment = $comentario ? " Observación del evaluador: {$comentario}." : '';

        foreach ($result['evidencias'] as $evidenceApproval) {
            try {
                $assignedUserIds = EvidenceAssignment::where('evidencia_id', $evidenceApproval->evidencia_id)
                    ->where('proceso_id', $procesoId)
                    ->pluck('usuario_id')
                    ->unique()
                    ->values()
                    ->toArray();

                if (!empty($assignedUserIds)) {
                    $evidencia = $evidenceApproval->evidence;
                    NotificationService::createMany(
                        $assignedUserIds,
                        [
                            'tipo_evento' => Notification::TIPO_RECHAZO_EVIDENCIA,
                            'titulo'      => "Evidencia {$evidencia->nomenclatura} rechazada — se requieren correcciones",
                            'mensaje'     => "La evidencia {$evidencia->nomenclatura} — {$evidencia->descripcion} ha sido rechazada para el proceso #{$procesoId}.{$reviewerComment}{$deadlineMessage} Por favor revisa y reenvía.",
                            'enlace'      => "/criterios/{$criterioId}/evidencias/aprobaciones",
                        ]
                    );
                }
            } catch (\Throwable $excepcion) {
                logger()->error('BlockRejection evidence notification failed', [
                    'evidencia_id' => $evidenceApproval->evidencia_id,
                    'criterio_id'  => $criterioId,
                    'error'        => $excepcion->getMessage(),
                ]);
            }
        }

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
    private function recalculateBlockState(CriterionApproval $block): void
    {
        $total    = Evidence::where('criterio_id', $block->criterio_id)->active()->count();
        $approved = EvidenceApproval::where('criterio_aprobacion_id', $block->aprobacion_criterio_id)
            ->where('estado', 'aprobado')->count();
        $rejected = EvidenceApproval::where('criterio_aprobacion_id', $block->aprobacion_criterio_id)
            ->where('estado', 'rechazado')->count();

        if ($approved === $total) {
            $block->estado = 'aprobado';
        } elseif ($rejected === $total) {
            $block->estado = 'rechazado';
        } elseif ($rejected >= 1) {
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
        int $userId
    ): array {
        $result = DB::transaction(function () use ($criterionId, $evidenceId, $processId, $userId) {
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

            // Bloqueo: evidencias ya aprobadas no se tocan cuando el bloque es 'incompleto'
            $existing = EvidenceApproval::where('evidencia_id', $evidenceId)
                ->where('proceso_id', $processId)
                ->first();

            if ($existing && $existing->estado === 'aprobado' && $criterionApproval->estado === 'incompleto') {
                throw new \LogicException(
                    'Esta evidencia ya fue aprobada y está bloqueada mientras el bloque tenga estado incompleto.'
                );
            }

            $evidenceApproval = EvidenceApproval::updateOrCreate(
                ['evidencia_id' => $evidenceId, 'proceso_id' => $processId],
                [
                    'criterio_aprobacion_id' => $criterionApproval->aprobacion_criterio_id,
                    'usuario_id'             => $userId,
                    'estado'                 => 'aprobado',
                    'comentario'             => null,
                ]
            );

            $this->recalculateBlockState($criterionApproval);

            return [
                'criterion_approval' => $criterionApproval->fresh()->load(['process', 'user']),
                'evidence_approval'  => $evidenceApproval->load(['evidence']),
            ];
        });

        // Notificar al profesor asignado — fuera de la transacción, solo canal interno
        // (aprobación = buena noticia, no requiere acción urgente).
        try {
            $assignedUserIds = EvidenceAssignment::where('evidencia_id', $evidenceId)
                ->where('proceso_id', $processId)
                ->pluck('usuario_id')
                ->unique()
                ->values()
                ->toArray();

            if (!empty($assignedUserIds)) {
                $evidence = $result['evidence_approval']->evidence;
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
        } catch (\Throwable $excepcion) {
            logger()->error('EvidenceApproved notification failed', [
                'evidencia_id' => $evidenceId,
                'error'        => $excepcion->getMessage(),
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
        ?string $comentario = null,
        ?string $nuevaFechaLimite = null
    ): array {
        $result = DB::transaction(function () use (
            $criterionId, $evidenceId, $processId, $userId, $comentario, $nuevaFechaLimite
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

            // Bloqueo: evidencias ya aprobadas no se pueden rechazar cuando el bloque es 'incompleto'
            $existing = EvidenceApproval::where('evidencia_id', $evidenceId)
                ->where('proceso_id', $processId)
                ->first();

            if ($existing && $existing->estado === 'aprobado' && $criterionApproval->estado === 'incompleto') {
                throw new \LogicException(
                    'Esta evidencia ya fue aprobada y está bloqueada mientras el bloque tenga estado incompleto.'
                );
            }

            // Guardar rechazo con observación en APROBACION_EVIDENCIA
            $evidenceApproval = EvidenceApproval::updateOrCreate(
                ['evidencia_id' => $evidenceId, 'proceso_id' => $processId],
                [
                    'criterio_aprobacion_id' => $criterionApproval->aprobacion_criterio_id,
                    'usuario_id'             => $userId,
                    'estado'                 => 'rechazado',
                    'comentario'             => $comentario,
                    'nueva_fecha_limite'     => $nuevaFechaLimite,
                ]
            );

            // Devolver la asignación al responsable: resetear estado + guardar observación + nueva fecha
            $assignmentUpdate = ['estado' => EvidenceAssignment::ESTADO_PENDIENTE];
            if ($comentario !== null) {
                $assignmentUpdate['comentario'] = $comentario;
            }
            if ($nuevaFechaLimite !== null) {
                $assignmentUpdate['fecha_limite'] = $nuevaFechaLimite;
            }

            EvidenceAssignment::where('evidencia_id', $evidenceId)
                ->where('proceso_id', $processId)
                ->update($assignmentUpdate);

            $this->recalculateBlockState($criterionApproval);

            return [
                'criterion_approval' => $criterionApproval->fresh()->load(['process', 'user']),
                'evidence_approval'  => $evidenceApproval->load(['evidence']),
            ];
        });

        // Notificar al profesor asignado — fuera de la transacción, canal email+interno
        // (rechazo = requiere acción correctiva urgente).
        try {
            $assignedUserIds = EvidenceAssignment::where('evidencia_id', $evidenceId)
                ->where('proceso_id', $processId)
                ->pluck('usuario_id')
                ->unique()
                ->values()
                ->toArray();

            if (!empty($assignedUserIds)) {
                $evidence = $result['evidence_approval']->evidence;
                $deadlineMessage  = $nuevaFechaLimite ? " Nueva fecha límite: {$nuevaFechaLimite}." : '';
                $reviewerComment = $comentario ? " Observación del evaluador: {$comentario}." : '';
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
        } catch (\Throwable $excepcion) {
            logger()->error('EvidenceRejected notification failed', [
                'evidencia_id' => $evidenceId,
                'error'        => $excepcion->getMessage(),
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
            ->with(['process', 'user', 'evidenceApprovals'])
            ->first();

        $existingByEvidenceId = $criterionApproval
            ? $criterionApproval->evidenceApprovals->keyBy('evidencia_id')
            : collect();

        // Asignaciones del proceso para las evidencias activas del criterio
        $evidenceIds = Evidence::where('criterio_id', $criterionId)->active()->pluck('evidencia_id');
        $assignmentsByEvidenceId = EvidenceAssignment::where('proceso_id', $processId)
            ->whereIn('evidencia_id', $evidenceIds)
            ->get()
            ->keyBy('evidencia_id');

        $evidences = Evidence::where('criterio_id', $criterionId)
            ->active()
            ->get()
            ->map(function ($evidenceItem) use ($existingByEvidenceId, $assignmentsByEvidenceId) {
                $approval    = $existingByEvidenceId->get($evidenceItem->evidencia_id);
                $assignment  = $assignmentsByEvidenceId->get($evidenceItem->evidencia_id);
                return [
                    'evidencia_id'            => $evidenceItem->evidencia_id,
                    'nomenclatura'            => $evidenceItem->nomenclatura,
                    'descripcion'             => $evidenceItem->descripcion,
                    'approval_status'         => $approval ? $approval->estado : 'pendiente',
                    'comentario_rechazo'      => $approval?->comentario,
                    'aprobacion_evidencia_id' => $approval?->aprobacion_evidencia_id,
                    'updated_at'              => $approval?->updated_at,
                    'asignacion'              => $assignment ? [
                        'estado'       => $assignment->estado,
                        'fecha_limite' => $assignment->fecha_limite,
                        'usuario_id'   => $assignment->usuario_id,
                    ] : null,
                ];
            })
            ->values();

        return [
            'criterion_approval' => $criterionApproval,
            'evidences'          => $evidences,
        ];
    }
}