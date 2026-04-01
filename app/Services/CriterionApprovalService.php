<?php

namespace App\Services;

use App\Models\CriterionApproval;
use App\Models\EvidenceApproval;
use App\Models\Evidence;
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
        $query = CriterionApproval::with(['criterion', 'process', 'user']);

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
        return CriterionApproval::with(['criterion', 'process', 'user', 'evidenceApprovals.evidence'])->find($approvalId);
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
        return DB::transaction(function () use ($criterioId, $procesoId, $usuarioId, $comentario) {
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
                'raiz'      => $approval->load(['criterion', 'process', 'user']),
                'evidencias' => $evidencias,
            ];
        });
    }

    /**
     * Rechazar un criterio y rechazar cada evidencia del criterio.
     */
    public function rejectCriterion(
        int $criterioId,
        int $procesoId,
        int $usuarioId,
        ?string $comentario = null
    ): array {
        return DB::transaction(function () use ($criterioId, $procesoId, $usuarioId, $comentario) {
            $approval = CriterionApproval::updateOrCreate(
                ['criterio_id' => $criterioId, 'proceso_id' => $procesoId],
                ['usuario_id'  => $usuarioId, 'estado' => 'rechazado', 'comentario' => $comentario]
            );

            $evidencias = [];
            Evidence::where('criterio_id', $criterioId)->active()->each(
                function ($evidencia) use ($procesoId, $approval, $usuarioId, &$evidencias) {
                    $evidencias[] = EvidenceApproval::updateOrCreate(
                        ['evidencia_id' => $evidencia->evidencia_id, 'proceso_id' => $procesoId],
                        [
                            'criterio_aprobacion_id' => $approval->aprobacion_criterio_id,
                            'usuario_id'             => $usuarioId,
                            'estado'                 => 'rechazado',
                        ]
                    )->load(['evidence']);
                }
            );

            return [
                'raiz'      => $approval->load(['criterion', 'process', 'user']),
                'evidencias' => $evidencias,
            ];
        });
    }
}