<?php

namespace App\Http\Controllers;

use App\Models\EvidenceAssignment;
use App\Models\ElementAssignment;
use App\Models\User;
use App\Http\Requests\EvidenceAssignmentRequest;
use App\Http\Requests\ValidateDuplicateAssignmentsRequest;
use App\Http\Resources\EvidenceAssignmentResource;
use App\Services\EvidenceAssignmentService;
use App\Events\EvidenceAssignmentDeleted;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

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
            $result = $this->service->assignEvidence($request->validated());
            
            return response()->json([
                'message' => 'Asignaciones procesadas correctamente.',
                'data' => [
                    'total_asignaciones' => $result['total_asignaciones'],
                    'total_errores' => $result['total_errores'],
                    'asignaciones' => EvidenceAssignmentResource::collection($result['asignaciones']),
                    'errores' => $result['errores']
                ]
            ], 201);

        } catch (\InvalidArgumentException $e) {
            // MODELO FLEXIBLE (HU-007): incompatibilidad de modelo entre proceso y evidencia
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error processing evidence assignments', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al procesar las asignaciones.'], 500);
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

        $user = $request->user();
        $isOwner = (int) $assignment->usuario_id === (int) ($user?->usuario_id ?? 0);
        $canEditAssignments = (bool) ($user?->can('asignaciones.edit') ?? false);

        if (!$isOwner && !$canEditAssignments) {
            return response()->json([
                'message' => 'No autorizado para actualizar esta asignación.',
            ], 403);
        }

        // El responsable puede cambiar únicamente su propio estado (en progreso/completado).
        if ($isOwner && !$canEditAssignments) {
            if ($request->hasAny(['fecha_limite', 'comentario'])) {
                return response()->json([
                    'message' => 'No autorizado para modificar fecha límite o comentario.',
                ], 403);
            }

            $request->validate([
                'estado' => 'required|string|in:en_progreso,completado',
            ]);
            $payload = $request->only(['estado']);
        } else {
            $request->validate([
                'estado' => 'sometimes|string|in:pendiente,en_progreso,completado,vencido',
                'fecha_limite' => 'sometimes|nullable|date|after:now',
                'comentario' => 'sometimes|nullable|string|max:1000',
            ]);
            $payload = $request->only(['estado', 'fecha_limite', 'comentario']);
        }

        try {
            $updatedAssignment = $this->service->updateAssignment(
                $assignment, 
                $payload
            );

            return EvidenceAssignmentResource::make($updatedAssignment)->response();

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
            
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
            // Guardar datos de la asignación antes de eliminarla para la notificación
            $assignmentData = [
                'asignacion_evidencia_id' => $assignment->evidencia_asignacion_id,
                'usuario_id' => $assignment->usuario_id,
                'evidencia_id' => $assignment->evidencia_id,
                'evidencia_nombre' => $assignment->evidence->nombre ?? 'Evidencia',
                'proceso_id' => $assignment->proceso_id,
                'fecha_asignacion' => $assignment->fecha_asignacion->format('Y-m-d'),
            ];
            
            $this->service->deleteAssignment($assignment);
            
            // Disparar evento para notificación
            event(new EvidenceAssignmentDeleted($assignmentData));
            
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

    /**
     * POST /api/evidencias-asignaciones/validar-duplicados
     * Validar si existen asignaciones duplicadas antes de crearlas.
     * Esta ruta es accesible para cualquier usuario autenticado.
     */
    public function validateDuplicates(ValidateDuplicateAssignmentsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // Buscar asignaciones existentes para la combinación proceso + evidencia + usuarios
        $duplicados = EvidenceAssignment::where('proceso_id', $validated['proceso_id'])
            ->where('evidencia_id', $validated['evidencia_id'])
            ->whereIn('usuario_id', $validated['usuarios'])
            ->with('user:usuario_id,nombre')
            ->get()
            ->map(function ($asignacion) {
                return [
                    'usuario_id' => $asignacion->usuario_id,
                    'usuario_nombre' => $asignacion->user->nombre,
                    'estado' => $asignacion->estado,
                    'fecha_asignacion' => $asignacion->fecha_asignacion->format('Y-m-d'),
                    'asignacion_id' => $asignacion->evidencia_asignacion_id,
                ];
            });

        return response()->json([
            'tiene_duplicados' => $duplicados->isNotEmpty(),
            'duplicados' => $duplicados->values(),
            'total_duplicados' => $duplicados->count(),
        ], 200);
    }

    /**
     * GET /api/evidencias-asignaciones/catalogo/usuarios
     * Catálogo mínimo de usuarios activos para formularios de asignación.
     */
    public function catalogUsers(): JsonResponse
    {
        $users = User::query()
            ->active()
            ->with('roles:id,name')
            ->orderBy('nombre')
            ->get(['usuario_id', 'nombre', 'email', 'status'])
            ->map(function (User $user) {
                return [
                    'id' => $user->usuario_id,
                    'name' => $user->nombre,
                    'email' => $user->email,
                    'status' => $user->status,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json(['data' => $users], 200);
    }

    /**
     * GET /api/evidencias-asignaciones/catalogo/roles
     * Catálogo mínimo de roles para formularios de asignación.
     */
    public function catalogRoles(): JsonResponse
    {
        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Role $role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => null,
                    'permissions' => [],
                ];
            })
            ->values();

        return response()->json(['data' => $roles], 200);
    }

    /**
     * GET /api/usuarios/{usuarioId}/mis-ciclos
     * Obtener los ciclos de acreditación donde el usuario tiene asignaciones,
     * con el tipo de modelo de cada uno (tradicional / elemento_flexible).
     */
    public function getUserCycles(string $usuarioId): JsonResponse
    {
        $userId = (int) $usuarioId;

        $traditionalCycles = EvidenceAssignment::where('usuario_id', $userId)
            ->with('process.accreditationCycle.structureModel')
            ->get()
            ->pluck('process.accreditationCycle')
            ->filter()
            ->unique('ciclo_acreditacion_id');

        $flexibleCycles = ElementAssignment::where('usuario_id', $userId)
            ->with('process.accreditationCycle.structureModel')
            ->get()
            ->pluck('process.accreditationCycle')
            ->filter()
            ->unique('ciclo_acreditacion_id');

        $cycles = $traditionalCycles->merge($flexibleCycles)
            ->unique('ciclo_acreditacion_id')
            ->map(fn ($cycle) => [
                'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
                'nombre'               => $cycle->nombre,
                'tipo_modelo'          => $cycle->structureModel?->tipo ?? 'tradicional',
            ])
            ->values();

        return response()->json(['data' => $cycles], 200);
    }
}
