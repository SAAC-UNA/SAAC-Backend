<?php

namespace App\Http\Controllers;

use App\Models\ExtensionRequest;
use App\Http\Requests\StoreExtensionTimeRequestRequest;
use App\Http\Requests\ExtensionTimeRequestListRequest;
use App\Http\Resources\ExtensionTimeRequestResource;
use App\Services\ExtensionTimeRequestService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controlador que gestiona las operaciones de solicitudes de ampliación del profesor (RF-15).
 * Proporciona los endpoints para listar, crear, mostrar, actualizar y cancelar solicitudes.
 */
class ExtensionTimeRequestController extends Controller
{
    use AuthorizesRequests;

    protected $service;

    public function __construct(ExtensionTimeRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista solicitudes de ampliación con paginación obligatoria y filtros dinámicos.
     *
     * AUTORIZACIÓN:
     * - Profesores: Solo ven SUS propias solicitudes (se fuerza filtro usuario_id)
     * - Encargados/Admins: Ven TODAS las solicitudes del sistema
     *
     * FILTROS OPCIONALES:
     * - estado: pendiente|aprobada|rechazada
     * - evidencia_asignacion_id: ID de asignación de evidencia
     * - fecha_desde/fecha_hasta: Rango de fechas
     * - per_page: Registros por página (default 15, max 100)
     *
     * @param ExtensionTimeRequestListRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     */
    public function index(ExtensionTimeRequestListRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $perPage = min($request->input('per_page', 15), 100);
            $filters = $request->validated();

            // PL-10: FILTRO POR ROL - Profesores SOLO ven sus propias solicitudes
            // SuperUsuario, Admin y Encargado de Acreditación ven TODAS las solicitudes
            if ($user->hasRole('Profesor') && !$user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación'])) {
                $filters['usuario_id'] = $user->usuario_id;
            }

            $requests = $this->service->listRequests($perPage, $filters);

            return ExtensionTimeRequestResource::collection($requests);

        } catch (\Exception $exception) {
            Log::error('Error listando solicitudes de ampliación', [
                'usuario_id' => Auth::id(),
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);

            return response()->json(['message' => 'Ocurrió un error al obtener las solicitudes'], 500);
        }
    }

    /**
     * Muestra la información de una solicitud de ampliación específica según su ID.
     *
     * AUTORIZACIÓN:
     * - El creador de la solicitud puede verla
     * - Encargados y admins pueden ver cualquier solicitud
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $extensionRequest = ExtensionRequest::find((int)$id);

            if (!$extensionRequest) {
                return response()->json([
                    'message' => 'Solicitud no encontrada.'
                ], 404);
            }

            // PL-10: AUTORIZACIÓN con Policy
            $this->authorize('view', $extensionRequest);

            return response()->json([
                'data' => new ExtensionTimeRequestResource($extensionRequest)
            ], 200);

        } catch (\Exception $exception) {
            Log::error('Error al obtener solicitud', [
                'solicitud_id' => $id,
                'error' => $exception->getMessage()
            ]);
            return response()->json(['message' => 'Error al obtener la solicitud'], 500);
        }
    }

    /**
     * Crea una nueva solicitud de ampliación para el profesor autenticado.
     *
     * AUTORIZACIÓN:
     * - Solo profesores y encargados (que también pueden ser profesores)
     * - El service valida que la evidencia esté asignada al usuario
     *
     * @param StoreExtensionTimeRequestRequest $request
     * @return JsonResponse
     */
    public function store(StoreExtensionTimeRequestRequest $request): JsonResponse
    {
        try {
            // PL-10: AUTORIZACIÓN con Policy
            $this->authorize('create', ExtensionRequest::class);

            $userId = Auth::id();

            if (!$userId) {
                Log::warning('Intento de crear solicitud sin autenticación');
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }
            
            // CRÍTICO: Usar servicio para validaciones de seguridad
            // El servicio valida que el usuario sea el propietario de la asignación
            $extensionRequest = $this->service->createRequest($request->validated(), $userId);

            // Registro en bitácora
            $tipoAsignacion = "evidencia asignación {$extensionRequest->evidencia_asignacion_id}";

            AuditLogService::log(
                'crear',
                "Solicitud de ampliación creada (ID: {$extensionRequest->solicitud_ampliacion_id}) para {$tipoAsignacion}",
                'Solicitudes Ampliación'
            );

            return response()->json([
                'message' => 'Solicitud de ampliación creada exitosamente.',
                'data' => new ExtensionTimeRequestResource($extensionRequest)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $exception) {
            Log::warning('Error de validación al crear solicitud', [
                'usuario_id' => Auth::id(),
                'errors' => $exception->errors()
            ]);
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $exception->errors()
            ], 422);
        } catch (\Exception $exception) {
            Log::error('Error al crear solicitud de ampliación', [
                'usuario_id' => Auth::id(),
                'error' => $exception->getMessage()
            ]);
            return response()->json(['message' => 'Ocurrió un error al crear la solicitud'], 500);
        }
    }

    /**
     * Cancela una solicitud de ampliación pendiente (cambia estado a 'cancelada').
     *
     * AUTORIZACIÓN:
     * - Solo el creador puede cancelar su solicitud (o admin)
     * - Solo solicitudes en estado PENDIENTE
     *
     * @param string $id
     * @return JsonResponse
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $extensionRequest = ExtensionRequest::find((int)$id);

            if (!$extensionRequest) {
                return response()->json(['message' => 'Solicitud no encontrada.'], 404);
            }

            $this->authorize('cancel', $extensionRequest);

            $updated = $this->service->cancelRequest((int)$id, Auth::id());

            AuditLogService::log(
                'cancelar',
                "Solicitud de ampliación cancelada (ID: {$id})",
                'Solicitudes Ampliación'
            );

            return response()->json([
                'message' => 'Solicitud cancelada exitosamente.'
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            Log::warning('Intento de cancelación no autorizada', [
                'solicitud_id' => $id,
                'usuario_id'   => Auth::id()
            ]);
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Illuminate\Validation\ValidationException $validationException) {
            return response()->json([
                'message' => 'No se puede cancelar la solicitud.',
                'errors'  => $validationException->errors()
            ], 422);
        } catch (\Exception $exception) {
            Log::error('Error al cancelar solicitud', [
                'solicitud_id' => $id,
                'usuario_id'   => Auth::id(),
                'error'        => $exception->getMessage()
            ]);
            return response()->json(['message' => 'Error al cancelar la solicitud'], 500);
        }
    }

    /**
     * Obtiene evidencias asignadas al profesor que están próximas a vencer.
     *
     * AUTORIZACIÓN:
     * - Usuario autenticado consulta sus propias evidencias
     *
     * USO: Pantalla de solicitud de ampliación para sugerir evidencias urgentes
     *
     * @return JsonResponse
     */
    public function upcomingEvidences(): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            $evidences = $this->service->getUpcomingEvidences($userId);

            return response()->json([
                'data' => $evidences,
                'total' => $evidences->count(),
                'message' => $evidences->isEmpty()
                    ? 'No tiene evidencias próximas a vencer en los próximos 7 días.'
                    : 'Evidencias próximas a vencer o ya vencidas.'
            ], 200);

        } catch (\Exception $exception) {
            Log::error('Error al obtener evidencias próximas a vencer', [
                'usuario_id' => Auth::id(),
                'error' => $exception->getMessage()
            ]);
            return response()->json(['message' => 'Error al obtener evidencias'], 500);
        }
    }

}
