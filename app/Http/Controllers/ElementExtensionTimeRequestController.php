<?php

namespace App\Http\Controllers;

use App\Models\ElementExtensionRequest;
use App\Http\Requests\StoreElementExtensionTimeRequestRequest;
use App\Http\Requests\UpdateElementExtensionTimeRequestRequest;
use App\Http\Requests\ElementExtensionTimeRequestListRequest;
use App\Http\Resources\ElementExtensionTimeRequestResource;
use App\Services\ElementExtensionTimeRequestService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controlador exclusivo para solicitudes de ampliación de plazo — modelo flexible (elementos).
 * Opera únicamente sobre solicitudes con elemento_asignacion_id.
 * Las solicitudes de evidencia siguen en ExtensionTimeRequestController.
 */
class ElementExtensionTimeRequestController extends Controller
{
    use AuthorizesRequests;

    protected ElementExtensionTimeRequestService $service;

    public function __construct(ElementExtensionTimeRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista solicitudes de ampliación de elemento con paginación y filtros.
     * Profesores solo ven las suyas; encargados/admins ven todas.
     */
    public function index(ElementExtensionTimeRequestListRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user    = Auth::user();
            $perPage = min($request->input('per_page', 15), 100);
            $filters = $request->validated();

            if ($user->hasRole('Profesor') && !$user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación'])) {
                $filters['usuario_id'] = $user->usuario_id;
            }

            $requests = $this->service->listRequests($perPage, $filters);

            return ElementExtensionTimeRequestResource::collection($requests);

        } catch (\Exception $e) {
            Log::error('Error listando solicitudes de ampliación (elemento)', [
                'usuario_id' => Auth::id(),
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Ocurrió un error al obtener las solicitudes'], 500);
        }
    }

    /**
     * Retorna el detalle de una solicitud de elemento.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $extensionRequest = $this->service->getById((int)$id);

            if (!$extensionRequest) {
                return response()->json(['message' => 'Solicitud no encontrada.'], 404);
            }

            $this->authorize('view', $extensionRequest);

            return response()->json([
                'data' => new ElementExtensionTimeRequestResource($extensionRequest)
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Exception $e) {
            Log::error('Error al obtener solicitud de elemento', ['solicitud_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al obtener la solicitud'], 500);
        }
    }

    /**
     * Crea una nueva solicitud de ampliación para una asignación de elemento.
     */
    public function store(StoreElementExtensionTimeRequestRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', ElementExtensionRequest::class);

            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            $solicitud = $this->service->createRequest($request->validated(), $userId);

            AuditLogService::log(
                'crear',
                "Solicitud de ampliación (elemento) creada (ID: {$solicitud->solicitud_ampliacion_elemento_id}) para elemento asignación {$solicitud->elemento_asignacion_id}",
                'Solicitudes Ampliación Elemento'
            );

            return response()->json([
                'message' => 'Solicitud de ampliación creada exitosamente.',
                'data'    => new ElementExtensionTimeRequestResource($solicitud),
            ], 201);

        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear solicitud de ampliación (elemento)', ['usuario_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Ocurrió un error al crear la solicitud'], 500);
        }
    }

    /**
     * Actualiza una solicitud de elemento pendiente.
     */
    public function update(UpdateElementExtensionTimeRequestRequest $request, string $id): JsonResponse
    {
        try {
            $extensionRequest = $this->service->getById((int)$id);

            if (!$extensionRequest) {
                return response()->json(['message' => 'Solicitud no encontrada.'], 404);
            }

            $this->authorize('update', $extensionRequest);

            $solicitud = $this->service->updateRequest((int)$id, $request->validated(), Auth::id());

            AuditLogService::log('editar', "Solicitud de ampliación (elemento) actualizada (ID: {$id})", 'Solicitudes Ampliación Elemento');

            return response()->json([
                'message' => 'Solicitud actualizada exitosamente.',
                'data'    => new ElementExtensionTimeRequestResource($solicitud),
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'No se puede actualizar la solicitud.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar solicitud (elemento)', ['solicitud_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al actualizar la solicitud'], 500);
        }
    }

    /**
     * Elimina físicamente una solicitud de elemento pendiente.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $extensionRequest = $this->service->getById((int)$id);

            if (!$extensionRequest) {
                return response()->json(['message' => 'Solicitud no encontrada.'], 404);
            }

            $this->authorize('delete', $extensionRequest);

            $this->service->deleteRequest((int)$id, Auth::id());

            AuditLogService::log('eliminar', "Solicitud de ampliación (elemento) eliminada (ID: {$id})", 'Solicitudes Ampliación Elemento');

            return response()->json(['message' => 'Solicitud eliminada exitosamente.'], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'No se puede eliminar la solicitud.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error al eliminar solicitud (elemento)', ['solicitud_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al eliminar la solicitud'], 500);
        }
    }

    /**
     * Cancela una solicitud de elemento pendiente (cambia estado a 'cancelada').
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $extensionRequest = $this->service->getById((int)$id);

            if (!$extensionRequest) {
                return response()->json(['message' => 'Solicitud no encontrada.'], 404);
            }

            $this->authorize('cancel', $extensionRequest);

            $this->service->cancelRequest((int)$id, Auth::id());

            AuditLogService::log('cancelar', "Solicitud de ampliación (elemento) cancelada (ID: {$id})", 'Solicitudes Ampliación Elemento');

            return response()->json(['message' => 'Solicitud cancelada exitosamente.'], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'No se puede cancelar la solicitud.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error al cancelar solicitud (elemento)', ['solicitud_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al cancelar la solicitud'], 500);
        }
    }

    /**
     * Asignaciones de elemento próximas a vencer del usuario autenticado (ventana 7 días).
     */
    public function upcomingElements(): JsonResponse
    {
        try {
            $userId   = Auth::id();
            $elements = $this->service->getUpcomingElements($userId);

            if ($elements->isEmpty()) {
                return response()->json([
                    'message' => 'No tiene asignaciones de elementos próximas a vencer en los próximos 7 días.',
                    'data'    => [],
                ], 200);
            }

            return response()->json([
                'message' => 'Asignaciones de elementos próximas a vencer.',
                'data'    => $elements->map(fn($ea) => [
                    'elemento_asignacion_id' => $ea->elemento_asignacion_id,
                    'estado'                 => $ea->estado,
                    'fecha_limite'           => $ea->fecha_limite?->format('Y-m-d'),
                    'element'                => $ea->element ? [
                        'elemento_id'  => $ea->element->elemento_id,
                        'nomenclatura' => $ea->element->nomenclatura,
                        'descripcion'  => $ea->element->descripcion,
                    ] : null,
                    'process' => $ea->process ? [
                        'proceso_id' => $ea->process->proceso_id,
                        'nombre'     => $ea->process->nombre,
                    ] : null,
                ]),
                'total' => $elements->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener elementos próximos a vencer', ['usuario_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al obtener asignaciones próximas a vencer'], 500);
        }
    }
}
