<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImprovementCommitmentRequest;
use App\Services\ImprovementCommitmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * Controlador que gestiona las operaciones relacionadas con los Compromisos de Mejora.
 * Proporciona los endpoints para listar, crear, mostrar, actualizar y eliminar compromisos.
 */
class ImprovementCommitmentController extends Controller
{
    private ImprovementCommitmentService $commitmentService;

    public function __construct(ImprovementCommitmentService $commitmentService)
    {
        $this->commitmentService = $commitmentService;
    }

    /**
     * Lista todos los compromisos de mejora con sus relaciones.
     *
     * @return JsonResponse
     */
    public function listCommitments(): JsonResponse
    {
        $commitments = $this->commitmentService->listCommitments();
        return response()->json(['data' => $commitments], 200);
    }

    /**
     * Lista compromisos de mejora con paginación.
     *
     * Query param opcional: per_page (default: 10)
     *
     * @return JsonResponse
     */
    public function listCommitmentsPaginated(): JsonResponse
    {
        $perPage = (int) request()->input('per_page', 10);
        $commitments = $this->commitmentService->listCommitmentsPaginated($perPage);
        return response()->json($commitments, 200);
    }

    /**
     * Muestra la información de un compromiso de mejora específico según su ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showCommitment(int $id): JsonResponse
    {
        $commitment = $this->commitmentService->getCommitment($id);

        if (!$commitment) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Compromiso de mejora no encontrado.',
            ], 404);
        }

        return response()->json(['data' => $commitment], 200);
    }

    /**
     * Crea un nuevo compromiso de mejora junto con sus evidencias asociadas.
     *
     * @param ImprovementCommitmentRequest $request
     * @return JsonResponse
     */
    public function createCommitment(ImprovementCommitmentRequest $request): JsonResponse
    {
        try {
            $commitment = $this->commitmentService->createCommitment($request->validated());

            return response()->json([
                'message' => 'Compromiso de mejora creado con éxito.',
                'data' => $commitment,
            ], 201);

        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $exception->errors(),
            ], 422);

        } catch (QueryException $exception) {
            // Manejar errores de constraint de base de datos
            $errorCode = (int)($exception->errorInfo[1] ?? 0);
            
            if ($errorCode === 1062) { // Duplicate entry
                return response()->json([
                    'error' => 'Duplicate Entry',
                    'message' => 'No se puede crear la asignación porque ya existe un registro con los mismos datos.',
                ], 409);
            }

            return response()->json([
                'error' => 'Database Error',
                'message' => 'Error al crear el compromiso de mejora.',
                'details' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza un compromiso de mejora existente.
     *
     * @param ImprovementCommitmentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateCommitment(ImprovementCommitmentRequest $request, int $id): JsonResponse
    {
        $commitment = $this->commitmentService->getCommitment($id);

        if (!$commitment) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Compromiso de mejora no encontrado.',
            ], 404);
        }

        try {
            $updated = $this->commitmentService->updateCommitment($commitment, $request->validated());

            return response()->json([
                'message' => 'Compromiso de mejora actualizado con éxito.',
                'data' => $updated,
            ], 200);

        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $exception->errors(),
            ], 422);

        } catch (QueryException $exception) {
            return response()->json([
                'error' => 'Database Error',
                'message' => 'Error al actualizar el compromiso de mejora.',
                'details' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un compromiso de mejora existente.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteCommitment(int $id): JsonResponse
    {
        $commitment = $this->commitmentService->getCommitment($id);

        if (!$commitment) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Compromiso de mejora no encontrado.',
            ], 404);
        }

        try {
            $this->commitmentService->deleteCommitment($commitment);

            return response()->json([
                'message' => 'Compromiso de mejora eliminado con éxito.',
            ], 200);

        } catch (QueryException $exception) {
            if ((int)($exception->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'error' => 'Constraint Violation',
                    'message' => 'No se puede eliminar: el compromiso tiene registros relacionados.',
                    'code' => 'FK_CONSTRAINT',
                ], 409);
            }

            return response()->json([
                'error' => 'Database Error',
                'message' => 'Error al eliminar el compromiso de mejora.',
                'details' => $exception->getMessage(),
            ], 500);
        }
    }
}
