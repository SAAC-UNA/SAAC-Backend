<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriterionApprovalRequest;
use App\Services\CriterionApprovalService;
use App\Models\Criterion;
use App\Events\CriterionApproved;
use App\Events\CriterionRejected;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * Controlador que gestiona las operaciones relacionadas con Aprobaciones de Criterios.
 * Proporciona los endpoints para aprobar/rechazar criterios por bloques y consultar historial.
 */
class CriterionApprovalController extends Controller
{
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
        try {
            $approvals = $this->approvalService->listApprovals();

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
     * Valida que todas las evidencias estén completas antes de aprobar.
     *
     * @param CriterionApprovalRequest $request Datos validados de la aprobación.
     * @param int $criterioId Identificador del criterio a aprobar.
     * @return JsonResponse
     */
    public function approveCriterion(CriterionApprovalRequest $request, int $criterioId): JsonResponse
    {
        try {
            // Validar que el criterio existe
            $criterion = Criterion::find($criterioId);
            if (!$criterion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio especificado no existe.'
                ], 404);
            }

            // Verificar que todas las evidencias del criterio estén completas
            $completenessCheck = $this->approvalService->verifyAllEvidencesAreComplete(
                $criterioId,
                $request->proceso_id
            );
            
            if (!$completenessCheck['is_complete']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede aprobar el criterio. Faltan ' . 
                        count($completenessCheck['missing_evidences']) . 
                        ' evidencia(s) por completar.',
                    'data' => [
                        'total_evidencias' => $completenessCheck['total'],
                        'evidencias_completadas' => $completenessCheck['completed'],
                        'evidencias_faltantes' => $completenessCheck['missing_evidences']
                    ]
                ], 400);
            }

            // Verificar si ya existe una aprobación para este criterio
            $existingApproval = $this->approvalService->getApproval($criterioId, $request->proceso_id);
            if ($existingApproval && $existingApproval->estado === 'aprobado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio ya está aprobado.'
                ], 400);
            }

            // TODO: Obtener el usuario autenticado cuando implementes autenticación
            // $usuarioId = auth()->user()->usuario_id;
            $usuarioId = 1; // Temporal - hardcoded

            $approval = $this->approvalService->approveCriterion(
                $criterioId,
                $request->proceso_id,
                $usuarioId,
                $request->comentario
            );

            // Disparar evento para notificaciones
            event(new CriterionApproved($approval));

            return response()->json([
                'success' => true,
                'message' => 'Criterio aprobado exitosamente.',
                'data' => $approval
            ], 201);

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
            // Validar que el criterio existe
            $criterion = Criterion::find($criterioId);
            if (!$criterion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio especificado no existe.'
                ], 404);
            }
         // Verificar si ya existe una aprobación para este criterio
            $existingApproval = $this->approvalService->getApproval($criterioId, $request->proceso_id);
            if ($existingApproval && $existingApproval->estado === 'rechazado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El criterio ya está rechazado.'
                ], 400);
            }

            // TODO: Obtener el usuario autenticado cuando implementes autenticación
            // $usuarioId = auth()->user()->usuario_id;
            $usuarioId = 1; // Temporal - hardcoded

            $approval = $this->approvalService->rejectCriterion(
                $criterioId,
                $request->proceso_id,
                $usuarioId,
                $request->comentario
            );

            // Disparar evento para notificaciones
            event(new CriterionRejected($approval));

            return response()->json([
                'success' => true,
                'message' => 'Criterio rechazado exitosamente.',
                'data' => $approval
            ], 201);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 400);
        }
    }

}
