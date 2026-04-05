<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriterionApprovalRequest;
use App\Services\CriterionApprovalService;
use App\Services\AuditLogService;
use App\Models\Criterion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

/**
 * Controlador que gestiona las operaciones relacionadas con Aprobaciones de Criterios.
 * Proporciona los endpoints para aprobar/rechazar criterios por bloques y consultar historial.
 */
class CriterionApprovalController extends Controller
{
    use AuthorizesRequests;

    private CriterionApprovalService $approvalService;

    public function __construct(CriterionApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Lista todas las aprobaciones de criterios junto con sus relaciones.
     *
     * @return JsonResponse
     */
    public function listApprovals(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\CriterionApproval::class);

        try {
            $estado    = request()->query('estado');
            $approvals = $this->approvalService->listApprovals($estado);

            return response()->json([
                'success' => true,
                'data' => $approvals
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las aprobaciones.',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra la información de una aprobación específica según su ID.
     *
     * @param int $approvalId Identificador de la aprobación.
     * @return JsonResponse
     */
    public function showApproval(int $approvalId): JsonResponse
    {
        try {
            $approval = $this->approvalService->getApproval($approvalId);

            if (!$approval) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aprobación no encontrada.'
                ], 404);
            }

            $this->authorize('view', $approval);

            return response()->json([
                'success' => true,
                'data' => $approval
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la aprobación.',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar un criterio (bloque de evidencias).
     * Permite la aprobación independientemente del estado de completitud de las evidencias.
     *
     * @param CriterionApprovalRequest $request Datos validados de la aprobación.
     * @param int $criterioId Identificador del criterio a aprobar.
     * @return JsonResponse
     */
    public function approveCriterion(CriterionApprovalRequest $request, int $criterioId): JsonResponse
    {
        try {
            $this->authorize('approve', \App\Models\CriterionApproval::class);
            // Validar que el criterio existe
            $criterion = Criterion::find($criterioId);
            if (!$criterion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio especificado no existe.'
                ], 404);
            }

            $usuarioId = Auth::id();

            $result = $this->approvalService->approveCriterion(
                $criterioId,
                $request->proceso_id,
                $usuarioId,
                $request->comentario
            );

            // Registrar en bitácora
            AuditLogService::log(
                'aprobar',
                "Criterio aprobado: {$criterion->nomenclatura} (ID: {$criterioId})",
                'Aprobación Criterios'
            );

            return response()->json([
                'success' => true,
                'message' => 'Criterio aprobado exitosamente.',
                'data' => [
                    'raiz'      => $result['raiz'],
                    'evidencias' => $result['evidencias'],
                ],
            ], 201);

        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.'
            ], 403);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Rechazar un criterio (bloque de evidencias).
     *
     * @param CriterionApprovalRequest $request Datos validados del rechazo.
     * @param int $criterioId Identificador del criterio a rechazar.
     * @return JsonResponse
     */
    public function rejectCriterion(CriterionApprovalRequest $request, int $criterioId): JsonResponse
    {
        try {
            $this->authorize('reject', \App\Models\CriterionApproval::class);
            // Validar que el criterio existe
            $criterion = Criterion::find($criterioId);
            if (!$criterion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio especificado no existe.'
                ], 404);
            }
            $usuarioId = Auth::id();

            $result = $this->approvalService->rejectCriterion(
                $criterioId,
                $request->proceso_id,
                $usuarioId,
                $request->comentario,
                $request->nueva_fecha_limite
            );

            // Registrar en bitácora
            AuditLogService::log(
                'rechazar',
                "Criterio rechazado: {$criterion->nomenclatura} (ID: {$criterioId})",
                'Aprobación Criterios'
            );

            return response()->json([
                'success' => true,
                'message' => 'Criterio rechazado exitosamente.',
                'data' => [
                    'raiz'      => $result['raiz'],
                    'evidencias' => $result['evidencias'],
                ],
            ], 201);

        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.'
            ], 403);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    // -------------------------------------------------------------------------
    // Aprobaciones individuales de evidencias (HU-010 individual)
    // -------------------------------------------------------------------------

    /**
     * Listar el estado de aprobación individual de cada evidencia activa de un criterio.
     * Query param: proceso_id (requerido).
     *
     * @param int $criterioId
     * @return JsonResponse
     */
    public function listEvidenceApprovals(int $criterionId): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\CriterionApproval::class);

        $processId = request()->query('proceso_id');

        if (!$processId || !is_numeric($processId)) {
            return response()->json([
                'success' => false,
                'message' => 'El parámetro proceso_id es requerido y debe ser un número entero.',
            ], 422);
        }

        try {
            $data = $this->approvalService->listEvidenceApprovals((int) $criterionId, (int) $processId);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las aprobaciones de evidencias.',
                'error'   => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprobar una evidencia individual dentro de un bloque de criterio.
     * Si no existe aprobación de bloque, se crea en estado 'pendiente'.
     *
     * @param CriterionApprovalRequest $request
     * @param int $criterioId
     * @param int $evidenciaId
     * @return JsonResponse
     */
    public function approveIndividualEvidence(
        CriterionApprovalRequest $request,
        int $criterionId,
        int $evidenceId
    ): JsonResponse {
        try {
            $this->authorize('approve', \App\Models\CriterionApproval::class);

            $criterion = Criterion::find($criterionId);
            if (!$criterion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio especificado no existe.',
                ], 404);
            }

            $userId = Auth::id();

            $result = $this->approvalService->approveIndividualEvidence(
                $criterionId,
                $evidenceId,
                $request->proceso_id,
                $userId
            );

            AuditLogService::log(
                'aprobar',
                "Evidencia {$evidenceId} aprobada individualmente en criterio {$criterion->nomenclatura} (ID: {$criterionId})",
                'Aprobación Criterios'
            );

            return response()->json([
                'success' => true,
                'message' => 'Evidencia aprobada individualmente.',
                'data'    => $result,
            ], 201);

        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Rechazar una evidencia individual dentro de un bloque de criterio.
     * Si no existe aprobación de bloque, se crea en estado 'pendiente'.
     *
     * @param CriterionApprovalRequest $request
     * @param int $criterioId
     * @param int $evidenciaId
     * @return JsonResponse
     */
    public function rejectIndividualEvidence(
        CriterionApprovalRequest $request,
        int $criterionId,
        int $evidenceId
    ): JsonResponse {
        try {
            $this->authorize('reject', \App\Models\CriterionApproval::class);

            $criterion = Criterion::find($criterionId);
            if (!$criterion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio especificado no existe.',
                ], 404);
            }

            $userId = Auth::id();

            $result = $this->approvalService->rejectIndividualEvidence(
                $criterionId,
                $evidenceId,
                $request->proceso_id,
                $userId,
                $request->comentario,
                $request->nueva_fecha_limite
            );

            AuditLogService::log(
                'rechazar',
                "Evidencia {$evidenceId} rechazada individualmente en criterio {$criterion->nomenclatura} (ID: {$criterionId})",
                'Aprobación Criterios'
            );

            return response()->json([
                'success' => true,
                'message' => 'Evidencia rechazada individualmente.',
                'data'    => $result,
            ], 201);

        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

}
