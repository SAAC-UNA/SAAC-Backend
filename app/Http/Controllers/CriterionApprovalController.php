<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriterionApprovalRequest;
use App\Services\CriterionApprovalService;
use App\Services\AuditLogService;
use App\Models\Criterion;
use App\Events\CriterionApproved;
use App\Events\CriterionRejected;
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

            // Verificar si ya existe una aprobación para este criterio
            $existingApproval = $this->approvalService->getApprovalByCriterionAndProcess(
                $criterioId,
                $request->proceso_id
            );

            if ($existingApproval && $existingApproval->estado === 'aprobado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio ya está aprobado.'
                ], 400);
            }

            $usuarioId = Auth::id();

            $result = $this->approvalService->approveCriterion(
                $criterioId,
                $request->proceso_id,
                $usuarioId,
                $request->comentario
            );

            // Disparar evento para notificaciones
            event(new CriterionApproved($result['raiz']));
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
         // Verificar si ya existe una aprobación para este criterio
            $existingApproval = $this->approvalService->getApprovalByCriterionAndProcess(
                $criterioId,
                $request->proceso_id
            );

            if ($existingApproval && $existingApproval->estado === 'rechazado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio ya está rechazado.'
                ], 400);
            }

            $usuarioId = Auth::id();

            $result = $this->approvalService->rejectCriterion(
                $criterioId,
                $request->proceso_id,
                $usuarioId,
                $request->comentario
            );

            // Disparar evento para notificaciones
            event(new CriterionRejected($result['raiz']));
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

}
