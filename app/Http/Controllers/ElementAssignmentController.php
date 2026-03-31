<?php

namespace App\Http\Controllers;

use App\Models\ElementAssignment;
use App\Http\Requests\ElementAssignmentRequest;
use App\Services\ElementAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ElementAssignmentController extends Controller
{
    protected ElementAssignmentService $service;

    public function __construct(ElementAssignmentService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/elementos-asignaciones
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->getAll()], 200);
    }

    /**
     * POST /api/elementos-asignaciones
     * Assign an element to users and/or roles. HU-007 (flexible model).
     */
    public function store(ElementAssignmentRequest $request): JsonResponse
    {
        try {
            $result = $this->service->assignElement($request->validated());

            return response()->json([
                'message' => 'Element assignments processed successfully.',
                'data'    => [
                    'total_assignments' => $result['total_assignments'],
                    'total_errors'      => $result['total_errors'],
                    'assignments'       => $result['assignments'],
                    'errors'            => $result['errors'],
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing assignments.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/elementos-asignaciones/{id}
     */
    public function show(string $id): JsonResponse
    {
        $assignment = $this->service->findById((int) $id);

        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        return response()->json(['data' => $assignment], 200);
    }

    /**
     * PUT /api/elementos-asignaciones/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $assignment = $this->service->findById((int) $id);

        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        $request->validate([
            'estado'       => 'sometimes|string|in:Pendiente,En Progreso,Completado,Vencido',
            'fecha_limite' => 'sometimes|nullable|date',
            'comentario'   => 'sometimes|nullable|string|max:1000',
        ]);

        try {
            $updated = $this->service->updateAssignment(
                $assignment,
                $request->only(['estado', 'fecha_limite', 'comentario'])
            );

            return response()->json(['data' => $updated], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating assignment.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/elementos-asignaciones/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $assignment = $this->service->findById((int) $id);

        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        $this->service->deleteAssignment($assignment);

        return response()->json(['message' => 'Assignment deleted successfully.'], 200);
    }

    /**
     * GET /api/usuarios/{usuarioId}/elementos-asignados
     */
    public function byUser(string $userId): JsonResponse
    {
        return response()->json(['data' => $this->service->getByUser((int) $userId)], 200);
    }

    /**
     * GET /api/elementos/{elementoId}/asignaciones
     */
    public function byElement(string $elementId): JsonResponse
    {
        return response()->json(['data' => $this->service->getByElement((int) $elementId)], 200);
    }

    /**
     * GET /api/procesos/{procesoId}/elementos-asignaciones
     */
    public function byProcess(string $processId): JsonResponse
    {
        return response()->json(['data' => $this->service->getByProcess((int) $processId)], 200);
    }
}
