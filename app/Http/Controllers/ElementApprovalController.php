<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementApprovalRequest;
use App\Services\ElementApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

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
            $status    = request()->query('estado');
            $approvals = $this->approvalService->listApprovals($status);

            return response()->json([
                'success' => true,
                'data'    => $approvals,
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las aprobaciones.',
                'error'   => $exception->getMessage(),
            ], 500);
        }
    }

    public function showApproval(int $approvalId): JsonResponse
    {
        try {
            $approval = $this->approvalService->getApproval($approvalId);

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

        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta accion.',
            ], 403);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la aprobacion.',
                'error'   => $exception->getMessage(),
            ], 500);
        }
    }

    public function approveElement(ElementApprovalRequest $request, int $elementId): JsonResponse
    {
        try {
            $this->authorize('approve', \App\Models\ElementApproval::class);

            $result = $this->approvalService->approveElement(
                $elementId,
                $request->proceso_id,
                $request->comentario
            );

            return response()->json([
                'success' => true,
                'message' => 'Elemento aprobado exitosamente.',
                'data'    => [
                    'root'    => $result['root'],
                    'cascade' => $result['cascade'],
                ],
            ], 201);

        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta accion.',
            ], 403);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    public function rejectElement(ElementApprovalRequest $request, int $elementId): JsonResponse
    {
        try {
            $this->authorize('reject', \App\Models\ElementApproval::class);

            $result = $this->approvalService->rejectElement(
                $elementId,
                $request->proceso_id,
                $request->comentario,
                $request->fecha_limite
            );

            return response()->json([
                'success' => true,
                'message' => 'Elemento rechazado exitosamente.',
                'data'    => [
                    'root'    => $result['root'],
                    'cascade' => $result['cascade'],
                ],
            ], 201);

        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta accion.',
            ], 403);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    public function approveIndividualChild(ElementApprovalRequest $request, int $parentId, int $childId): JsonResponse
    {
        $this->authorize('approve', \App\Models\ElementApproval::class);

        $result = $this->approvalService->approveIndividualChild(
            $parentId,
            $childId,
            $request->proceso_id,
            $request->responsable_usuario_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Elemento hijo aprobado individualmente.',
            'data'    => $result,
        ], 201);
    }

    public function rejectIndividualChild(ElementApprovalRequest $request, int $parentId, int $childId): JsonResponse
    {
        $this->authorize('reject', \App\Models\ElementApproval::class);

        $result = $this->approvalService->rejectIndividualChild(
            $parentId,
            $childId,
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
