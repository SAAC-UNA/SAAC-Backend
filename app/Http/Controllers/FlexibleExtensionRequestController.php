<?php

namespace App\Http\Controllers;

use App\Models\ElementAssignment;
use App\Models\ExtensionRequest;
use App\Http\Requests\StoreFlexibleExtensionRequestRequest;
use App\Http\Requests\ReviewExtensionRequestRequest;
use App\Http\Resources\ExtensionRequestResource;
use App\Services\FlexibleExtensionRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Events\ExtensionRequestCreated;
use App\Events\ExtensionRequestApproved;
use App\Events\ExtensionRequestRejected;

/**
 * Controller para gestionar solicitudes de ampliación del modelo FLEXIBLE (elemento_asignacion_id).
 *
 * SOLID: Responsabilidad única para el modelo flexible.
 * Equivale a ExtensionRequestController pero inyecta FlexibleExtensionRequestService
 * y usa StoreFlexibleExtensionRequestRequest (valida elemento_asignacion_id).
 *
 * Endpoints:
 * - GET  /api/elemento-solicitudes-ampliacion              - Listar todas (encargados)
 * - GET  /api/elemento-solicitudes-ampliacion/pendientes   - Pendientes (encargados)
 * - GET  /api/elemento-solicitudes-ampliacion/mis-solicitudes - Mis solicitudes
 * - GET  /api/elemento-solicitudes-ampliacion/{id}         - Ver una
 * - POST /api/elemento-solicitudes-ampliacion              - Crear
 * - POST /api/elemento-solicitudes-ampliacion/{id}/aprobar - Aprobar
 * - POST /api/elemento-solicitudes-ampliacion/{id}/rechazar - Rechazar
 */
class FlexibleExtensionRequestController extends Controller
{
    use AuthorizesRequests;

    protected FlexibleExtensionRequestService $service;

    public function __construct(FlexibleExtensionRequestService $service)
    {
        $this->service = $service;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper paginación
    // ─────────────────────────────────────────────────────────────────────────

    private function paginatedResponse($paginator): JsonResponse
    {
        $paginator->withPath(url('api/elemento-solicitudes-ampliacion'));

        return response()->json([
            'data' => ExtensionRequestResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from'         => $paginator->firstItem(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Listados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/elemento-solicitudes-ampliacion
     * Solo encargados — devuelve únicamente solicitudes del modelo flexible.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ExtensionRequest::class);

        $filters = $request->only([
            'estado', 'usuario_id', 'elemento_asignacion_id',
            'fecha_desde', 'fecha_hasta', 'per_page',
        ]);

        return $this->paginatedResponse($this->service->getAll($filters));
    }

    /**
     * GET /api/elemento-solicitudes-ampliacion/pendientes
     * Solo encargados — solicitudes flexible en estado pendiente.
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ExtensionRequest::class);

        $filters = $request->only([
            'usuario_id', 'elemento_asignacion_id',
            'fecha_desde', 'fecha_hasta', 'per_page',
        ]);

        return $this->paginatedResponse($this->service->getPending($filters));
    }

    /**
     * GET /api/elemento-solicitudes-ampliacion/mis-solicitudes
     * Cualquier usuario autenticado — sus propias solicitudes flexible.
     */
    public function mySolicitudes(Request $request): JsonResponse
    {
        $userId = Auth::id() ?? 1;

        $filters = $request->only([
            'estado', 'elemento_asignacion_id',
            'fecha_desde', 'fecha_hasta', 'per_page',
        ]);

        return $this->paginatedResponse($this->service->getByUser($userId, $filters));
    }

    /**
     * GET /api/elemento-solicitudes-ampliacion/{id}
     */
    public function show(string $id): JsonResponse
    {
        $extensionRequest = $this->service->findById((int) $id);

        if (!$extensionRequest) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $this->authorize('view', $extensionRequest);

        return response()->json(['data' => new ExtensionRequestResource($extensionRequest)], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Crear
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/elemento-solicitudes-ampliacion
     *
     * El request valida `elemento_asignacion_id`.
     * El service verifica que el usuario sea dueño de la asignación,
     * que no haya una solicitud pendiente abierta, y que la fecha sea válida.
     */
    public function store(StoreFlexibleExtensionRequestRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 1;

            $assignment = ElementAssignment::findOrFail(
                $request->validated()['elemento_asignacion_id']
            );

            $extensionRequest = $this->service->createRequest(
                $assignment,
                $request->validated(),
                $userId
            );

            event(new ExtensionRequestCreated($extensionRequest));

            return response()->json([
                'message' => 'Solicitud de ampliación creada correctamente.',
                'data'    => new ExtensionRequestResource($extensionRequest),
            ], 201);

        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Error al crear la solicitud.',
                'error'   => $exception->getMessage(),
            ], 400);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Aprobar / Rechazar
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/elemento-solicitudes-ampliacion/{id}/aprobar
     */
    public function approve(ReviewExtensionRequestRequest $request, string $id): JsonResponse
    {
        $extensionRequest = $this->service->findById((int) $id);

        if (!$extensionRequest) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $this->authorize('approve', $extensionRequest);

        try {
            $resolverUserId = Auth::id() ?? 1;
            $justification  = $request->input('justificacion');

            $approved = $this->service->approve((int) $id, $resolverUserId, $justification);

            event(new ExtensionRequestApproved($approved, $justification ?? ''));

            return response()->json([
                'message' => 'Solicitud aprobada correctamente.',
                'data'    => new ExtensionRequestResource($approved),
            ], 200);

        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Error al aprobar la solicitud.',
                'error'   => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/elemento-solicitudes-ampliacion/{id}/rechazar
     */
    public function reject(ReviewExtensionRequestRequest $request, string $id): JsonResponse
    {
        $extensionRequest = $this->service->findById((int) $id);

        if (!$extensionRequest) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $this->authorize('reject', $extensionRequest);

        try {
            $resolverUserId = Auth::id() ?? 1;
            $justification  = $request->input('justificacion');

            $rejected = $this->service->reject((int) $id, $resolverUserId, $justification);

            event(new ExtensionRequestRejected($rejected, $justification ?? 'Sin justificación proporcionada'));

            return response()->json([
                'message' => 'Solicitud rechazada correctamente.',
                'data'    => new ExtensionRequestResource($rejected),
            ], 200);

        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Error al rechazar la solicitud.',
                'error'   => $exception->getMessage(),
            ], 400);
        }
    }
}
