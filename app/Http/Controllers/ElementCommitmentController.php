<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementCommitmentRequest;
use App\Http\Requests\ElementCommitmentListRequest;
use App\Http\Requests\UpdateElementCommitmentRequest;
use App\Services\ElementCommitmentService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

/**
 * Controlador de Compromisos de Mejora — modelo flexible (ELEMENTO).
 * Equivalente a ImprovementCommitmentController pero usando ElementCommitmentService.
 *
 * Patrón: pure HTTP — authorize → call service → return JSON.
 * Todo el negocio vive en ElementCommitmentService.
 */
class ElementCommitmentController extends Controller
{
    public function __construct(
        private readonly ElementCommitmentService $service
    ) {}

    
    /**
     * GET /api/compromisos-Elements
     * Lista compromisos con paginación y filtros opcionales.
     */
    public function listCommitments(ElementCommitmentListRequest $request): JsonResponse
    {
        $perPage     = $request->input('per_page', 10);
        $commitments = $this->service->listCommitments($perPage, $request->validated());

        AuditLogService::log(
            'consultar',
            'Listado de compromisos de mejora (Elements)',
            'Compromisos Elements'
        );

        return response()->json([
            'success' => true,
            'data'    => $commitments,
        ], 200);
    }

    
    
    public function showCommitment(int $id): JsonResponse
    {
        $commitment = $this->service->getCommitment($id);

        if (!$commitment) {
            return response()->json([
                'success' => false,
                'message' => 'Compromiso de mejora no encontrado.',
            ], 404);
        }

        AuditLogService::log(
            'consultar',
            "Consulta de compromiso de mejora (elemento) ID {$id}",
            'Compromisos Elements'
        );

        return response()->json([
            'success' => true,
            'data'    => $commitment,
        ], 200);
    }


    /**
     * GET /api/compromisos-Elements/usuario/{usuarioId}
     * Compromisos donde el usuario tiene asignaciones vinculadas.
     */
    public function getByUser(int $usuarioId): JsonResponse
    {
        $commitments = $this->service->getCommitmentsByUser($usuarioId);

        AuditLogService::log(
            'consultar',
            "Compromisos de mejora (Elements) por usuario {$usuarioId}",
            'Compromisos Elements'
        );

        return response()->json([
            'success' => true,
            'data'    => $commitments,
        ], 200);
    }

   
    /**
     * GET /api/compromisos-Elements/elemento/{elementoId}
     * Compromisos vinculados al elemento o cualquiera de sus descendientes.
     */
    public function getByElemento(int $elementoId): JsonResponse
    {
        $commitments = $this->service->getCommitmentsByElemento($elementoId);

        AuditLogService::log(
            'consultar',
            "Compromisos de mejora (Elements) por elemento {$elementoId}",
            'Compromisos Elements'
        );

        return response()->json([
            'success' => true,
            'data'    => $commitments,
        ], 200);
    }


    /**
     * POST /api/compromisos-Elements
     * Crea un compromiso y vincula automáticamente todas las asignaciones del árbol.
     */
    public function createCommitment(ElementCommitmentRequest $request): JsonResponse
    {
        try {
            $commitment = $this->service->createCommitment($request->validated());

            AuditLogService::log(
                'crear',
                'Compromiso de mejora (elemento) creado ID ' . $commitment->compromiso_elemento_id,
                'Compromisos Elements'
            );

            return response()->json([
                'success' => true,
                'message' => 'Compromiso de mejora creado exitosamente.',
                'data'    => $commitment,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (QueryException $e) {
            $errorCode = (int) ($e->errorInfo[1] ?? 0);

            if ($errorCode === 1062) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un compromiso con los mismos datos.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el compromiso de mejora.',
            ], 500);
        }
    }

  

    /**
     * PUT /api/compromisos-Elements/{id}
     */
    public function updateCommitment(UpdateElementCommitmentRequest $request, int $id): JsonResponse
    {
        $commitment = $this->service->getCommitment($id);

        if (!$commitment) {
            return response()->json([
                'success' => false,
                'message' => 'Compromiso de mejora no encontrado.',
            ], 404);
        }

        try {
            $updated = $this->service->updateCommitment($commitment, $request->validated());

            if ($updated === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se detectaron cambios en los datos enviados.',
                ], 422);
            }

            $changedKeys = implode(', ', array_keys($request->validated()));
            AuditLogService::log(
                'editar',
                "Compromiso de mejora (elemento) ID {$id} actualizado. Campos: {$changedKeys}",
                'Compromisos Elements'
            );

            return response()->json([
                'success' => true,
                'message' => 'Compromiso de mejora actualizado exitosamente.',
                'data'    => $updated,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el compromiso de mejora.',
            ], 500);
        }
    }

    

    /**
     * PATCH /api/compromisos-Elements/{id}/active
     * Body: { "activo": true } o { "activo": false }
     */
    public function setActive(Request $request, int $id): JsonResponse
    {
        $commitment = $this->service->getCommitment($id);

        if (!$commitment) {
            return response()->json([
                'success' => false,
                'message' => 'Compromiso de mejora no encontrado.',
            ], 404);
        }

        $activo = filter_var($request->input('activo', true), FILTER_VALIDATE_BOOLEAN);
        $updated = $this->service->setActive($commitment, $activo);

        $estado = $activo ? 'activado' : 'desactivado';
        AuditLogService::log(
            'editar',
            "Compromiso de mejora (elemento) ID {$id} {$estado}",
            'Compromisos Elements'
        );

        return response()->json([
            'success' => true,
            'message' => "Compromiso de mejora {$estado} exitosamente.",
            'data'    => $updated,
        ], 200);
    }
}
