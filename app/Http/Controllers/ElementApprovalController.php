<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementApprovalRequest;
use App\Services\ElementApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Controlador de aprobaciones de Elements � modelo flexible (HU-010).
 * Solo maneja HTTP: delega toda la l�gica a ElementApprovalService.
 */
class ElementApprovalController extends Controller
{
    use AuthorizesRequests;

    private ElementApprovalService $approvalService;

    public function __construct(ElementApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function listApprovals(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\ElementApproval::class);

        try {
            $estado    = request()->query('estado');
            $approvals = $this->approvalService->listApprovals($estado);

            return response()->json([
                'success' => true,
                'data'    => $approvals,
            ], 200);

        } catch (Exception $e) {
            Log::error('Error listing element approvals', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las aprobaciones.'], 500);
        }
    }

    public function showApproval(int $aprobacionId): JsonResponse
    {
        try {
            $approval = $this->approvalService->getApproval($aprobacionId);

            if (!$approval) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aprobacion no encontrada.',
                ], 404);
            }

            $this->authorize('view', $approval);

            return response()->json([
                'success' => true,
                'data'    => $approval,
            ], 200);

        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para realizar esta accion.'], 403);
        } catch (Exception $e) {
            Log::error('Error fetching element approval', ['aprobacion_id' => $aprobacionId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener la aprobacion.'], 500);
        }
    }

    public function approveElemento(ElementApprovalRequest $request, int $elementoId): JsonResponse
    {
        try {
            $this->authorize('approve', \App\Models\ElementApproval::class);

            $result = $this->approvalService->approveElemento(
                $elementoId,
                $request->proceso_id,
                $request->comentario
            );

            return response()->json([
                'success' => true,
                'message' => 'Elemento aprobado exitosamente.',
                'data'    => [
                    'raiz'    => $result['raiz'],
                    'cascada' => $result['cascada'],
                ],
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para realizar esta accion.'], 403);
        } catch (Exception $e) {
            Log::error('Error approving element', ['elemento_id' => $elementoId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al procesar la aprobación.'], 500);
        }
    }

    public function rejectElemento(ElementApprovalRequest $request, int $elementoId): JsonResponse
    {
        try {
            $this->authorize('reject', \App\Models\ElementApproval::class);

            $result = $this->approvalService->rejectElemento(
                $elementoId,
                $request->proceso_id,
                $request->comentario,
                $request->fecha_limite
            );

            return response()->json([
                'success' => true,
                'message' => 'Elemento rechazado exitosamente.',
                'data'    => [
                    'raiz'    => $result['raiz'],
                    'cascada' => $result['cascada'],
                ],
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para realizar esta accion.'], 403);
        } catch (Exception $e) {
            Log::error('Error rejecting element', ['elemento_id' => $elementoId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al procesar el rechazo.'], 500);
        }
    }

    public function approveIndividualChild(ElementApprovalRequest $request, int $padreId, int $hijoId): JsonResponse
    {
        $this->authorize('approve', \App\Models\ElementApproval::class);

        $result = $this->approvalService->approveIndividualChild(
            $padreId,
            $hijoId,
            $request->proceso_id,
            $request->responsable_usuario_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Elemento hijo aprobado individualmente.',
            'data'    => $result,
        ], 201);
    }

    public function rejectIndividualChild(ElementApprovalRequest $request, int $padreId, int $hijoId): JsonResponse
    {
        $this->authorize('reject', \App\Models\ElementApproval::class);

        $result = $this->approvalService->rejectIndividualChild(
            $padreId,
            $hijoId,
            $request->proceso_id,
            $request->comentario,
            $request->nueva_fecha_limite,
            $request->responsable_usuario_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Elemento hijo rechazado individualmente.',
            'data'    => $result,
        ], 201);
    }
}
