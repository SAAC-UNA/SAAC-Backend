<?php

namespace App\Http\Controllers;

use App\Models\EvidenceAssignment;
use App\Http\Requests\EvidenceAssignmentRequest;
use App\Http\Resources\EvidenceAssignmentResource;
use App\Services\EvidenceAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EvidenceAssignmentController extends Controller
{
    protected $service;

    public function __construct(EvidenceAssignmentService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/evidencias-asignaciones
     * Mostrar todas las asignaciones de evidencias.
     */
    public function index(): JsonResponse
    {
        $assignments = $this->service->getAll();
        return EvidenceAssignmentResource::collection($assignments)->response();
    }

    /**
     * POST /api/evidencias-asignaciones
     * Crear nuevas asignaciones de evidencias a usuarios y/o roles.
     */
    public function store(EvidenceAssignmentRequest $request): JsonResponse
    {
        try {
            $resultado = $this->service->assignEvidence($request->validated());
            
            return response()->json([
                'message' => 'Asignaciones procesadas correctamente.',
                'data' => [
                    'total_asignaciones' => $resultado['total_asignaciones'],
                    'total_errores' => $resultado['total_errores'],
                    'asignaciones' => EvidenceAssignmentResource::collection($resultado['asignaciones']),
                    'errores' => $resultado['errores']
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar las asignaciones.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/evidencias-asignaciones/{id}
     * Mostrar una asignación específica.
     */
    public function show(string $id): JsonResponse
    {
        $assignment = $this->service->findById((int)$id);
        
        if (!$assignment) {
            return response()->json(['message' => 'Asignación no encontrada.'], 404);
        }

        return EvidenceAssignmentResource::make($assignment)->response();
    }

    /**
     * PUT/PATCH /api/evidencias-asignaciones/{id}
     * Actualizar una asignación específica (principalmente el estado).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $assignment = $this->service->findById((int)$id);
        
        if (!$assignment) {
            return response()->json(['message' => 'Asignación no encontrada.'], 404);
        }

        // Validación simple para actualización de estado
        $request->validate([
            'estado' => 'sometimes|string|in:pendiente,en_progreso,completado,vencido',
            'fecha_limite' => 'sometimes|nullable|date|after:now'
        ]);

        try {
            $updatedAssignment = $this->service->updateAssignment(
                $assignment, 
                $request->only(['estado', 'fecha_limite'])
            );

            return EvidenceAssignmentResource::make($updatedAssignment)->response();
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la asignación.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/evidencias-asignaciones/{id}
     * Eliminar una asignación específica.
     */
    public function destroy(string $id): JsonResponse
    {
        $assignment = $this->service->findById((int)$id);
        
        if (!$assignment) {
            return response()->json(['message' => 'Asignación no encontrada.'], 404);
        }

        try {
            $this->service->deleteAssignment($assignment);
            
            return response()->json([
                'message' => 'Asignación eliminada correctamente.'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la asignación.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/usuarios/{usuarioId}/evidencias-asignadas
     * Obtener todas las evidencias asignadas a un usuario específico.
     */
    public function getByUser(string $usuarioId): JsonResponse
    {
        $assignments = $this->service->getAssignmentsByUser((int)$usuarioId);
        return EvidenceAssignmentResource::collection($assignments)->response();
    }

    /**
     * GET /api/evidencias/{evidenciaId}/asignaciones
     * Obtener todas las asignaciones de una evidencia específica.
     */
    public function getByEvidence(string $evidenciaId): JsonResponse
    {
        $assignments = $this->service->getAssignmentsByEvidence((int)$evidenciaId);
        return EvidenceAssignmentResource::collection($assignments)->response();
    }

    /**
     * GET /api/procesos/{procesoId}/asignaciones
     * Obtener todas las asignaciones de un proceso específico.
     */
    public function getByProcess(string $procesoId): JsonResponse
    {
        $assignments = $this->service->getAssignmentsByProcess((int)$procesoId);
        return EvidenceAssignmentResource::collection($assignments)->response();
    }
}
