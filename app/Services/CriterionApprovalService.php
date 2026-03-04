<?php

namespace App\Services;

use App\Models\CriterionApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * Servicio de aprobacion de criterios. Usa stored procedures para todas las
 * operaciones de base de datos.
 */
class CriterionApprovalService
{
    public function __construct() {}

    /**
     * Listar todas las aprobaciones de criterios.
     * Si el usuario es Profesor, filtra solo criterios con evidencias asignadas a el.
     */
    public function listApprovals()
    {
        $rows = DB::select('CALL SP_OBTENER_APROBACIONES_CRITERIO()');
        $approvals = collect($rows);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && $user->hasRole('Profesor')) {
            // Obtener asignaciones del usuario para extraer criterio_ids
            $asignaciones = DB::select('CALL SP_OBTENER_ASIGNACIONES_EVIDENCIA(?, ?, ?)', [
                null, $user->usuario_id, null,
            ]);

            $evidenciaIds = array_unique(array_column($asignaciones, 'evidencia_id'));

            if (empty($evidenciaIds)) {
                return collect();
            }

            // Obtener criterio_ids de esas evidencias
            $criterioIds = [];
            foreach ($evidenciaIds as $evId) {
                $ev = DB::select('CALL SP_BUSCAR_EVIDENCIA(?)', [$evId]);
                if (!empty($ev)) {
                    $criterioIds[] = $ev[0]->criterio_id;
                }
            }
            $criterioIds = array_unique($criterioIds);

            $approvals = $approvals->filter(fn($a) => in_array($a->criterio_id, $criterioIds));
        }

        return CriterionApproval::hydrate(
            $approvals->map(fn($r) => (array) $r)->values()->toArray()
        );
    }

    /**
     * Obtener una aprobacion especifica por ID.
     */
    public function getApproval(int $approvalId): ?CriterionApproval
    {
        $rows = DB::select('CALL SP_BUSCAR_APROBACION_CRITERIO(?)', [$approvalId]);
        return $rows ? CriterionApproval::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    /**
     * Obtener una aprobacion especifica por criterio y proceso.
     */
    public function getApprovalByCriterionAndProcess(int $criterioId, int $procesoId): ?CriterionApproval
    {
        $rows = DB::select('CALL SP_BUSCAR_APROBACION_CRITERIO_PROCESO(?, ?)', [
            $criterioId, $procesoId,
        ]);
        return $rows ? CriterionApproval::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    /**
     * Aprobar un criterio (crea o actualiza la aprobacion) y aprueba cada evidencia.
     */
    public function approveCriterion(
        int $criterioId,
        int $procesoId,
        int $usuarioId,
        ?string $comentario = null
    ): CriterionApproval {
        return DB::transaction(function () use ($criterioId, $procesoId, $usuarioId, $comentario) {
            // Upsert aprobacion de criterio
            $result = DB::select('CALL SP_UPSERT_APROBACION_CRITERIO(?, ?, ?, ?, ?)', [
                $criterioId,
                $procesoId,
                $usuarioId,
                'aprobado',
                $comentario,
            ]);

            $aprobacionCriterioId = $result[0]->aprobacion_criterio_id;

            // Aprobar cada evidencia del criterio
            $evidencias = DB::select('CALL SP_OBTENER_EVIDENCIAS_POR_CRITERIO_APROBACION(?)', [$criterioId]);
            foreach ($evidencias as $evidencia) {
                DB::statement('CALL SP_UPSERT_APROBACION_EVIDENCIA(?, ?, ?, ?, ?)', [
                    $evidencia->evidencia_id,
                    $procesoId,
                    $aprobacionCriterioId,
                    $usuarioId,
                    'aprobado',
                ]);
            }

            $rows = DB::select('CALL SP_BUSCAR_APROBACION_CRITERIO(?)', [$aprobacionCriterioId]);
            return CriterionApproval::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
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
    ): CriterionApproval {
        return DB::transaction(function () use ($criterioId, $procesoId, $usuarioId, $comentario) {
            $result = DB::select('CALL SP_UPSERT_APROBACION_CRITERIO(?, ?, ?, ?, ?)', [
                $criterioId,
                $procesoId,
                $usuarioId,
                'rechazado',
                $comentario,
            ]);

            $aprobacionCriterioId = $result[0]->aprobacion_criterio_id;

            $evidencias = DB::select('CALL SP_OBTENER_EVIDENCIAS_POR_CRITERIO_APROBACION(?)', [$criterioId]);
            foreach ($evidencias as $evidencia) {
                DB::statement('CALL SP_UPSERT_APROBACION_EVIDENCIA(?, ?, ?, ?, ?)', [
                    $evidencia->evidencia_id,
                    $procesoId,
                    $aprobacionCriterioId,
                    $usuarioId,
                    'rechazado',
                ]);
            }

            $rows = DB::select('CALL SP_BUSCAR_APROBACION_CRITERIO(?)', [$aprobacionCriterioId]);
            return CriterionApproval::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
        });
    }
}