<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImprovementCommitmentRequest;
use App\Http\Requests\ImprovementCommitmentListRequest;
use App\Http\Requests\UpdateImprovementCommitmentRequest;
use App\Http\Resources\ImprovementCommitmentResource;
use App\Services\AuditLogService;
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
     * Lista compromisos de mejora con paginación obligatoria y filtros dinámicos.
     * Default: 10 registros por página, máximo 50 (estándar del equipo).
     *
     * @param ImprovementCommitmentListRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listCommitments(ImprovementCommitmentListRequest $request)
    {
        $perPage = $request->input('per_page', 10);
        $commitments = $this->commitmentService->listCommitments($perPage, $request->validated());

        AuditLogService::log(
            'consultar',
            'Listado de compromisos de mejora',
            'Compromisos de mejora'
        );
        
        // SIEMPRE retornar con formato paginado (incluye meta y links automáticamente)
        return ImprovementCommitmentResource::collection($commitments);
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

        AuditLogService::log(
            'consultar',
            "Consulta de compromiso de mejora ID {$id}",
            'Compromisos de mejora'
        );

        return response()->json([
            'data' => new ImprovementCommitmentResource($commitment)
        ], 200);
    }

    /**
     * Obtener compromisos de mejora filtrados por usuario.
     *
     * @param int $usuarioId ID del usuario.
     * @return JsonResponse Respuesta JSON con los compromisos donde el usuario tiene asignaciones.
     */
    public function getByUser(int $usuarioId): JsonResponse
    {
        $commitments = $this->commitmentService->getCommitmentsByUser($usuarioId);

        AuditLogService::log(
            'consultar',
            "Consulta de compromisos de mejora por usuario {$usuarioId}",
            'Compromisos de mejora'
        );

        return response()->json([
            'data' => ImprovementCommitmentResource::collection($commitments)
        ], 200);
    }

    /**
     * Obtener compromisos de mejora filtrados por evidencia.
     *
     * @param int $evidenciaId ID de la evidencia.
     * @return JsonResponse Respuesta JSON con los compromisos donde la evidencia está asignada.
     */
    public function getByEvidence(int $evidenciaId): JsonResponse
    {
        $commitments = $this->commitmentService->getCommitmentsByEvidence($evidenciaId);

        AuditLogService::log(
            'consultar',
            "Consulta de compromisos de mejora por evidencia {$evidenciaId}",
            'Compromisos de mejora'
        );

        return response()->json([
            'data' => ImprovementCommitmentResource::collection($commitments)
        ], 200);
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

            // Si retorna null, ya existe un compromiso para esta carrera en este ciclo
            if ($commitment === null) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => [
                        'ciclo_acreditacion_id' => ['Ya existe un compromiso de mejora para esta carrera en este ciclo de acreditación. Solo se permite un compromiso por carrera en cada ciclo.']
                    ],
                ], 422);
            }

            AuditLogService::log(
                'crear',
                'Creación de compromiso de mejora ID '.$commitment->compromiso_mejora_id,
                'Compromisos de mejora'
            );

            return response()->json([
                'message' => 'Compromiso de mejora creado con éxito.',
                'data' => new ImprovementCommitmentResource($commitment),
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
     * @param UpdateImprovementCommitmentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateCommitment(UpdateImprovementCommitmentRequest $request, int $id): JsonResponse
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

            // Si retorna null, no hubo cambios
            if ($updated === null) {
                return response()->json([
                    'message' => 'Solicitud válida, pero no se aplicaron cambios.',
                    'errors' => [
                        'general' => ['No se detectaron diferencias entre los datos enviados y el registro actual.']
                    ],
                ], 422);
            }

            $changedKeys = implode(', ', array_keys($request->validated()));
            AuditLogService::log(
                'editar',
                "Actualización de compromiso de mejora ID {$id}. Campos: {$changedKeys}",
                'Compromisos de mejora'
            );

            return response()->json([
                'message' => 'Compromiso de mejora actualizado con éxito.',
                'data' => new ImprovementCommitmentResource($updated),
            ], 200);

        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $exception->errors(),
            ], 422);

        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
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
    * Activar o desactivar un compromiso de mejora.
     * Body JSON esperado: {"activo": true} o {"activo": false}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function setActive(int $id): JsonResponse
    {
        $commitment = $this->commitmentService->getCommitment($id);

        if (!$commitment) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Compromiso de mejora no encontrado.',
            ], 404);
        }

        // Validar que se envió el campo 'activo'
        $activo = request()->input('activo');
        
        if ($activo === null) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'El campo "activo" es requerido.',
                'errors' => [
                    'activo' => ['El campo activo es requerido y debe ser true o false.']
                ]
            ], 422);
        }

        try {
            $updated = $this->commitmentService->setActive($commitment, (bool) $activo);
            $updated = $updated->refresh()->load([
                'process.accreditationCycle.careerCampus.career',
                'process.accreditationCycle.careerCampus.campus',
                'evidences.criterion.component.dimension',
                'evidences.criterion.standards',
                'assignedEvidences.evidence',
                'assignedEvidences.user'
            ]);
            
            $message = $activo 
                ? 'Compromiso de mejora activado con éxito.'
                : 'Compromiso de mejora desactivado con éxito.';

            AuditLogService::log(
                'editar',
                "Cambio de estado activo={$updated->activo} en compromiso de mejora ID {$id}",
                'Compromisos de mejora'
            );

            return response()->json([
                'message' => $message,
                'data' => new ImprovementCommitmentResource($updated),
            ], 200);

        } catch (QueryException $exception) {
            return response()->json([
                'error' => 'Database Error',
                'message' => 'Error al actualizar el estado del compromiso.',
                'details' => $exception->getMessage(),
            ], 500);
        }
    }
}
