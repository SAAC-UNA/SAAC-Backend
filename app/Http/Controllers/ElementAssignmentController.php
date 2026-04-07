<?php

namespace App\Http\Controllers;

use App\Models\ElementAssignment;
use App\Http\Requests\ElementAssignmentRequest;
use App\Http\Requests\RetroalimentacionRequest;
use App\Http\Resources\ElementAssignmentResource;
use App\Services\ElementAssignmentService;
use App\Services\FlexibleExtensionRequestService;
use App\Events\ExtensionRequestCreated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ElementAssignmentController extends Controller
{
    protected ElementAssignmentService $service;
    protected FlexibleExtensionRequestService $extensionService;

    public function __construct(ElementAssignmentService $service, FlexibleExtensionRequestService $extensionService)
    {
        $this->service          = $service;
        $this->extensionService = $extensionService;
    }

    /**
     * GET /api/elementos-asignaciones
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->getAll()], 200);
    }

    /**
     * GET /api/elementos-asignaciones/filtrar
     * Explorador de pautas para el modelo flexible (equivalente a evidencias/filter).
     */
    public function filtrar(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'proceso_id'  => 'nullable|integer|exists:PROCESO,proceso_id',
            'elemento_id' => 'nullable|integer|exists:ELEMENTO,elemento_id',
            'estado'      => 'nullable|string|in:Pendiente,En Progreso,Completado,Vencido,Observada,Validada',
            'usuario_id'  => 'nullable|integer|exists:USUARIO,usuario_id',
            'per_page'    => 'nullable|integer|min:5|max:100',
            'page'        => 'nullable|integer|min:1',
        ]);

        $result = $this->service->filter($filters, $request->user());

        return response()->json($result, 200);
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

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
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

        return response()->json(['data' => new ElementAssignmentResource($assignment)], 200);
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

        $user = $request->user();
        $isOwner = (int) $assignment->usuario_id === (int) ($user?->usuario_id ?? 0);
        $canEditAssignments = (bool) ($user?->can('asignaciones.edit') ?? false);

        if (!$isOwner && !$canEditAssignments) {
            return response()->json([
                'message' => 'No autorizado para actualizar esta asignación.',
            ], 403);
        }

        // El responsable puede modificar únicamente su propio estado de trabajo.
        if ($isOwner && !$canEditAssignments) {
            if ($request->hasAny(['fecha_limite', 'comentario'])) {
                return response()->json([
                    'message' => 'No autorizado para modificar fecha límite o comentario.',
                ], 403);
            }

            $request->validate([
                'estado' => 'required|string|in:En Progreso,Completado',
            ]);
            $payload = $request->only(['estado']);
        } else {
            $request->validate([
                'estado'       => 'sometimes|string|in:Pendiente,En Progreso,Completado,Vencido',
                'fecha_limite' => 'sometimes|nullable|date',
                'comentario'   => 'sometimes|nullable|string|max:1000',
            ]);
            $payload = $request->only(['estado', 'fecha_limite', 'comentario']);
        }

        try {
            $updated = $this->service->updateAssignment(
                $assignment,
                $payload
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
        $assignments = $this->service->getByUser((int) $userId);
        return response()->json(['data' => ElementAssignmentResource::collection($assignments)], 200);
    }

    /**
     * GET /api/elementos/{elementoId}/asignaciones
     */
    public function byElement(Request $request, string $elementId): JsonResponse
    {
        $processId = $request->query('proceso_id');

        $parsedProcessId = null;
        if ($processId !== null && is_numeric($processId)) {
            $parsedProcessId = (int) $processId;
        }

        return response()->json([
            'data' => $this->service->getByElement((int) $elementId, $parsedProcessId),
        ], 200);
    }

    /**
     * GET /api/procesos/{procesoId}/elementos-asignaciones
     */
    public function byProcess(string $processId): JsonResponse
    {
        return response()->json(['data' => $this->service->getByProcess((int) $processId)], 200);
    }

    /**
     * POST /api/elementos-asignaciones/{id}/retroalimentacion
     * HU-013 equivalente para modelo flexible.
     *
     * Marca la asignación como 'Observada' (requiere corrección) o 'Validada' (aprobada).
     * Solo roles autorizados: Encargado de Acreditación, Administrador, Superusuario.
     */
    public function retroalimentar(RetroalimentacionRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole(['Encargado de Acreditación', 'Administrador', 'Superusuario'])) {
            return response()->json(['message' => 'No autorizado para retroalimentar asignaciones de elemento.'], 403);
        }

        $assignment = $this->service->findById((int) $id);
        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        try {
            $updated = $this->service->retroalimentar($assignment, $request->validated(), $user);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $updated], 200);
    }

    /**
     * POST /api/elementos-asignaciones/{id}/solicitud-ampliacion
     * HU-016 equivalente para modelo flexible.
     *
     * Permite al usuario asignado solicitar una ampliación de plazo.
     */
    public function storeExtension(Request $request, string $id): JsonResponse
    {
        $assignment = $this->service->findById((int) $id);
        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        $validated = $request->validate([
            'motivo'         => ['required', 'string', 'min:10', 'max:1000'],
            'fecha_sugerida' => ['required', 'date', 'after:today'],
        ]);

        try {
            $solicitud = $this->extensionService->createRequest(
                $assignment,
                $validated,
                $request->user()->usuario_id
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        event(new ExtensionRequestCreated($solicitud));

        return response()->json(['data' => $solicitud->load(['user'])], 201);
    }
}
