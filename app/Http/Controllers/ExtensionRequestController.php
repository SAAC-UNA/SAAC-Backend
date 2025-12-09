<?php

namespace App\Http\Controllers;

use App\Models\ExtensionRequest;
use App\Http\Requests\StoreExtensionRequestRequest;
use App\Http\Requests\ReviewExtensionRequestRequest;
use App\Http\Resources\ExtensionRequestResource;
use App\Services\ExtensionRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller para gestionar solicitudes de ampliación de fechas límite.
 * 
 * Endpoints:
 * - GET /api/solicitudes-ampliacion - Listar todas (solo encargados)
 * - GET /api/solicitudes-ampliacion/pendientes - Listar pendientes (solo encargados)
 * - GET /api/solicitudes-ampliacion/mis-solicitudes - Mis solicitudes
 * - GET /api/solicitudes-ampliacion/{id} - Ver una solicitud
 * - POST /api/solicitudes-ampliacion - Crear solicitud
 * - POST /api/solicitudes-ampliacion/{id}/aprobar - Aprobar solicitud
 * - POST /api/solicitudes-ampliacion/{id}/rechazar - Rechazar solicitud
 */
class ExtensionRequestController extends Controller
{
    use AuthorizesRequests;
    
    protected $service;

    public function __construct(ExtensionRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/solicitudes-ampliacion
     * Listar todas las solicitudes (solo encargados de acreditación).
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ExtensionRequest::class);

        $solicitudes = $this->service->getAll();
        return response()->json([
            'data' => ExtensionRequestResource::collection($solicitudes)
        ], 200);
    }

    /**
     * GET /api/solicitudes-ampliacion/pendientes
     * Listar solicitudes pendientes (solo encargados de acreditación).
     */
    public function pending(): JsonResponse
    {
        $this->authorize('viewAny', ExtensionRequest::class);

        $solicitudes = $this->service->getPending();
        return response()->json([
            'data' => ExtensionRequestResource::collection($solicitudes)
        ], 200);
    }

    /**
     * GET /api/solicitudes-ampliacion/mis-solicitudes
     * Listar mis propias solicitudes.
     */
    public function mySolicitudes(): JsonResponse
    {
        // TEMPORAL: Fallback a usuario ID 1 para pruebas
        $usuarioId = Auth::id() ?? 1;
        $solicitudes = $this->service->getByUser($usuarioId);
        
        return response()->json([
            'data' => ExtensionRequestResource::collection($solicitudes)
        ], 200);
    }

    /**
     * GET /api/solicitudes-ampliacion/{id}
     * Ver una solicitud específica.
     */
    public function show(string $id): JsonResponse
    {
        $solicitud = $this->service->findById((int)$id);
        
        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        $this->authorize('view', $solicitud);

        return response()->json([
            'data' => new ExtensionRequestResource($solicitud)
        ], 200);
    }

    /**
     * POST /api/solicitudes-ampliacion
     * Crear una nueva solicitud de ampliación.
     */
    public function store(StoreExtensionRequestRequest $request): JsonResponse
    {
        // TEMPORAL: Comentado para probar sin autorización
        // $this->authorize('create', ExtensionRequest::class);

        try {
            // TEMPORAL: Mientras no haya autenticación, usar usuario hardcodeado
            // Cambiar este valor por un usuario_id que exista en tu BD
            $usuarioId = Auth::id() ?? 1; // Si no hay auth, usar usuario ID 1
            
            $solicitud = $this->service->createRequest(
                $request->validated(), 
                $usuarioId
            );

            return response()->json([
                'message' => 'Solicitud de ampliación creada correctamente.',
                'data' => new ExtensionRequestResource($solicitud)
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la solicitud.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * POST /api/solicitudes-ampliacion/{id}/aprobar
     * Aprobar una solicitud de ampliación.
     */
    public function approve(ReviewExtensionRequestRequest $request, string $id): JsonResponse
    {
        $solicitud = $this->service->findById((int)$id);
        
        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        $this->authorize('approve', $solicitud);

        try {
            // TEMPORAL: Fallback a usuario ID 1 para pruebas
            $resolutorId = Auth::id() ?? 1;
            $justificacion = $request->input('justificacion');
            
            $solicitudAprobada = $this->service->approve(
                (int)$id, 
                $resolutorId, 
                $justificacion
            );

            return response()->json([
                'message' => 'Solicitud aprobada correctamente.',
                'data' => new ExtensionRequestResource($solicitudAprobada)
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al aprobar la solicitud.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * POST /api/solicitudes-ampliacion/{id}/rechazar
     * Rechazar una solicitud de ampliación.
     */
    public function reject(ReviewExtensionRequestRequest $request, string $id): JsonResponse
    {
        $solicitud = $this->service->findById((int)$id);
        
        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        $this->authorize('reject', $solicitud);

        try {
            // TEMPORAL: Fallback a usuario ID 1 para pruebas
            $resolutorId = Auth::id() ?? 1;
            $justificacion = $request->input('justificacion');
            
            $solicitudRechazada = $this->service->reject(
                (int)$id, 
                $resolutorId, 
                $justificacion
            );

            return response()->json([
                'message' => 'Solicitud rechazada correctamente.',
                'data' => new ExtensionRequestResource($solicitudRechazada)
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar la solicitud.',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
