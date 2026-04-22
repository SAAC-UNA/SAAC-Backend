<?php

namespace App\Http\Controllers;

use App\Models\ExtensionRequest;
use App\Http\Requests\StoreExtensionRequestRequest;
use App\Http\Requests\ReviewExtensionRequestRequest;
use App\Http\Resources\ExtensionRequestResource;
use App\Services\TradicionalExtensionRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Events\ExtensionRequestCreated;
use App\Events\ExtensionRequestApproved;
use App\Events\ExtensionRequestRejected;

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

    public function __construct(TradicionalExtensionRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * Helper para formatear respuestas paginadas con estructura consistente.
     * 
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @return \Illuminate\Http\JsonResponse
     */
    private function paginatedResponse($paginator): JsonResponse
    {
        return response()->json([
            'data' => ExtensionRequestResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ]
        ], 200);
    }

    /**
     * GET /api/solicitudes-ampliacion
     * 
     * PROPÓSITO: Listar todas las solicitudes de ampliación (sin filtrar por estado).
     * 
     * AUTORIZACIÓN: Solo encargados de acreditación (policy viewAny).
     * 
     * FLUJO:
     * 1. Verifica que el usuario tenga rol de encargado
     * 2. Extrae filtros opcionales del query string
     * 3. Delega al service la consulta con filtros
     * 4. Retorna respuesta paginada formateada
     * 
     * FILTROS OPCIONALES:
     * - estado: pendiente|aprobada|rechazada (filtra por estado específico)
     * - usuario_id: ID del usuario solicitante
     * - evidencia_asignacion_id: ID de la asignación de evidencia
     * - fecha_desde: YYYY-MM-DD (solicitudes desde esta fecha)
     * - fecha_hasta: YYYY-MM-DD (solicitudes hasta esta fecha)
     * - per_page: registros por página (default 15, max 100)
     * 
     * USO: Dashboard de encargados para ver todas las solicitudes del sistema.
     */
    public function index(Request $request): JsonResponse
    {
        // Autorización: Solo encargados pueden ver todas las solicitudes
        $this->authorize('viewAny', ExtensionRequest::class);

        // Extraer filtros del request (solo los permitidos)
        $filters = $request->only(['estado', 'usuario_id', 'evidencia_asignacion_id', 'fecha_desde', 'fecha_hasta', 'per_page']);
        
        // Delegar consulta al service (quien maneja la lógica de filtrado)
        $extensionRequests = $this->service->getAll($filters);
        
        // Retornar respuesta paginada con formato consistente
        return $this->paginatedResponse($extensionRequests);
    }

    /**
     * GET /api/solicitudes-ampliacion/pendientes
     * 
     * PROPÓSITO: Listar solo solicitudes PENDIENTES de revisión.
     * 
     * AUTORIZACIÓN: Solo encargados de acreditación (policy viewAny).
     * 
     * FLUJO:
     * 1. Verifica que el usuario tenga rol de encargado
     * 2. Extrae filtros opcionales del query string
     * 3. Delega al service la consulta (siempre con estado='pendiente')
     * 4. Retorna respuesta paginada formateada
     * 
     * FILTROS OPCIONALES:
     * - usuario_id: ID del usuario solicitante
     * - evidencia_asignacion_id: ID de la asignación de evidencia
     * - fecha_desde: YYYY-MM-DD (solicitudes desde esta fecha)
     * - fecha_hasta: YYYY-MM-DD (solicitudes hasta esta fecha)
     * - per_page: registros por página (default 15, max 100)
     * 
     * NOTA: No incluye filtro 'estado' porque siempre es 'pendiente'.
     * 
     * USO: Dashboard de encargados para ver su bandeja de trabajo (lo que deben revisar).
     */
    public function pending(Request $request): JsonResponse
    {
        // Autorización: Solo encargados pueden ver solicitudes pendientes
        $this->authorize('viewAny', ExtensionRequest::class);

        // Extraer filtros del request (sin 'estado' porque siempre es pendiente)
        $filters = $request->only(['usuario_id', 'evidencia_asignacion_id', 'fecha_desde', 'fecha_hasta', 'per_page']);
        
        // Delegar consulta al service (quien aplica filtro estado='pendiente' automáticamente)
        $extensionRequests = $this->service->getPending($filters);
        
        // Retornar respuesta paginada con formato consistente
        return $this->paginatedResponse($extensionRequests);
    }

    /**
     * GET /api/solicitudes-ampliacion/mis-solicitudes
     * 
     * PROPÓSITO: Listar las solicitudes creadas por el usuario autenticado.
     * 
     * AUTORIZACIÓN: Ninguna (cualquier usuario autenticado puede ver sus propias solicitudes).
     * 
     * FLUJO:
     * 1. Obtiene el ID del usuario autenticado (con fallback temporal a ID 1)
     * 2. Extrae filtros opcionales del query string
     * 3. Delega al service la consulta filtrada por usuario_id
     * 4. Retorna respuesta paginada formateada
     * 
     * FILTROS OPCIONALES:
     * - estado: pendiente|aprobada|rechazada (filtra por estado específico)
     * - evidencia_asignacion_id: ID de la asignación de evidencia
     * - fecha_desde: YYYY-MM-DD (solicitudes desde esta fecha)
     * - fecha_hasta: YYYY-MM-DD (solicitudes hasta esta fecha)
     * - per_page: registros por página (default 15, max 100)
     * 
     * NOTA TEMPORAL: Usa fallback a usuario ID 1 mientras no haya LDAP implementado.
     * En producción con LDAP, Auth::id() siempre retornará el usuario real.
     * 
     * USO: Vista de docente para seguimiento de sus solicitudes enviadas.
     */
    public function mySolicitudes(Request $request): JsonResponse
    {
        // Obtener ID del usuario autenticado (fallback temporal para testing sin LDAP)
        $userId = Auth::id() ?? 1;
        
        // Extraer filtros del request (solo los permitidos)
        $filters = $request->only(['estado', 'evidencia_asignacion_id', 'fecha_desde', 'fecha_hasta', 'per_page']);
        
        // Delegar consulta al service (filtra automáticamente por usuario_id)
        $extensionRequests = $this->service->getByUser($userId, $filters);
        
        // Retornar respuesta paginada con formato consistente
        return $this->paginatedResponse($extensionRequests);
    }

    /**
     * GET /api/solicitudes-ampliacion/{id}
     * 
     * PROPÓSITO: Obtener los detalles completos de una solicitud específica.
     * 
     * AUTORIZACIÓN: Solo el creador de la solicitud o un encargado (policy view).
     * 
     * FLUJO:
     * 1. Busca la solicitud por ID en la base de datos
     * 2. Si no existe, retorna 404 (not found)
     * 3. Verifica autorización (solo dueño o encargado puede ver)
     * 4. Si no autorizado, Laravel retorna 403 automáticamente
     * 5. Retorna los datos completos de la solicitud
     * 
     * PARÁMETROS:
     * - id: ID de la solicitud a consultar (route parameter)
     * 
     * RESPUESTAS:
     * - 200: Solicitud encontrada y autorizada
     * - 404: Solicitud no existe
     * - 403: Usuario no autorizado para ver esta solicitud
     * 
     * USO: Ver detalles de una solicitud (tanto para docente como encargado).
     */
    public function show(string $id): JsonResponse
    {
        // Buscar solicitud por ID (delega al service)
        $extensionRequest = $this->service->findById((int)$id);
        
        // Si no existe, retornar 404
        if (!$extensionRequest) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        // Autorización: Solo dueño o encargado pueden ver (403 si falla)
        $this->authorize('view', $extensionRequest);

        // Retornar solicitud completa con relaciones
        return response()->json([
            'data' => new ExtensionRequestResource($extensionRequest)
        ], 200);
    }

    /**
     * POST /api/solicitudes-ampliacion
     * 
     * PROPÓSITO: Crear una nueva solicitud de ampliación de fecha límite.
     * 
     * AUTORIZACIÓN: Temporalmente deshabilitada para testing. En producción solo usuarios autenticados.
     * 
     * FLUJO:
     * 1. Valida datos de entrada con StoreExtensionRequestRequest
     * 2. Obtiene ID del usuario autenticado (fallback temporal a ID 1)
     * 3. Delega al service la creación de la solicitud
     * 4. Service carga la carrera desde evidencia → proceso → ciclo → sede → carrera
     * 5. Service filtra encargados por carrera específica
     * 6. Service envía notificación por email solo a encargados de esa carrera
     * 7. Si no hay encargados específicos, notifica a todos (fallback de seguridad)
     * 8. Retorna la solicitud creada con estado 201 (created)
     * 
     * DATOS REQUERIDOS (validados por Request):
     * - evidencia_asignacion_id: ID de la asignación de evidencia
     * - justificacion: Razón de la solicitud (min 10 caracteres)
     * - fecha_sugerida: Fecha propuesta (debe ser después de hoy)
     * 
     * RESPUESTAS:
     * - 201: Solicitud creada exitosamente (emails enviados)
     * - 400: Error de lógica de negocio (ej: evidencia no existe)
     * - 422: Validación fallida (Laravel automático)
     * 
     * NOTA: La notificación por email se envía de forma síncrona.
     * Para producción considerar usar Queue system para mejorar performance.
     * 
     * USO: Docente solicita más tiempo para subir evidencia.
     */
    public function store(StoreExtensionRequestRequest $request): JsonResponse
    {
        // TEMPORAL: Autorización comentada para testing sin LDAP
        // En producción descomentar esta línea:
        // $this->authorize('create', ExtensionRequest::class);

        try {
            // Obtener ID del usuario autenticado (fallback temporal para testing)
            $userId = Auth::id() ?? 1;
            
            // Delegar al service la creación completa (incluye notificaciones)
            $extensionRequest = $this->service->createRequest(
                $request->validated(), 
                $userId
            );

            // Disparar evento para notificaciones
            event(new ExtensionRequestCreated($extensionRequest));

            // Retornar 201 (created) con la solicitud creada
            return response()->json([
                'message' => 'Solicitud de ampliación creada correctamente.',
                'data' => new ExtensionRequestResource($extensionRequest)
            ], 201);
            
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Exception $exception) {
            Log::error('Error creating extension request', ['usuario_id' => Auth::id(), 'error' => $exception->getMessage()]);
            return response()->json(['message' => 'Error al procesar la solicitud.'], 500);
        }
    }

    /**
     * POST /api/solicitudes-ampliacion/{id}/aprobar
     * 
     * PROPÓSITO: Aprobar una solicitud de ampliación pendiente.
     * 
     * AUTORIZACIÓN: Solo encargados de acreditación (policy approve).
     * 
     * FLUJO:
     * 1. Busca la solicitud por ID
     * 2. Si no existe, retorna 404
     * 3. Verifica que el usuario sea encargado (403 si falla)
     * 4. Delega al service la aprobación
     * 5. Service valida que esté en estado 'pendiente'
     * 6. Service cambia estado a 'aprobada'
     * 7. Service actualiza fecha_limite en la evidencia asignada (CRÍTICO)
     * 8. Service registra fecha_resolucion y resolutor_id
     * 9. Service envía notificación por email al solicitante
     * 10. Retorna la solicitud actualizada
     * 
     * DATOS OPCIONALES:
     * - justificacion: Comentario del encargado explicando por qué aprueba
     * 
     * PARÁMETROS:
     * - id: ID de la solicitud a aprobar (route parameter)
     * 
     * RESPUESTAS:
     * - 200: Solicitud aprobada exitosamente
     * - 400: Error de lógica (ej: solicitud ya fue resuelta)
     * - 403: Usuario no es encargado
     * - 404: Solicitud no existe
     * 
     * IMPACTO: Al aprobar, la fecha_limite de la evidencia se actualiza
     * permitiendo al docente subir el archivo hasta la nueva fecha.
     * 
     * USO: Encargado acepta la solicitud del docente.
     */
    public function approve(ReviewExtensionRequestRequest $request, string $id): JsonResponse
    {
        // Buscar solicitud por ID
        $extensionRequest = $this->service->findById((int)$id);
        
        // Si no existe, retornar 404
        if (!$extensionRequest) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        // Autorización: Solo encargados pueden aprobar (403 si falla)
        $this->authorize('approve', $extensionRequest);

        try {
            // Obtener ID del encargado que aprueba (fallback temporal)
            $reviewerId = Auth::id() ?? 1;
            
            // Obtener justificación opcional del encargado
            $justification = $request->input('justificacion');
            
            // Delegar al service la aprobación completa (incluye actualizar evidencia y notificar)
            $approvedRequest = $this->service->approve(
                (int)$id, 
                $reviewerId, 
                $justification
            );

            // Disparar evento para notificaciones
            event(new ExtensionRequestApproved($approvedRequest, $justification ?? ''));

            // Retornar 200 con solicitud aprobada
            return response()->json([
                'message' => 'Solicitud aprobada correctamente.',
                'data' => new ExtensionRequestResource($approvedRequest)
            ], 200);
            
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Exception $exception) {
            Log::error('Error approving extension request', ['solicitud_id' => $id, 'error' => $exception->getMessage()]);
            return response()->json(['message' => 'Error al procesar la solicitud.'], 500);
        }
    }

    /**
     * POST /api/solicitudes-ampliacion/{id}/rechazar
     * 
     * PROPÓSITO: Rechazar una solicitud de ampliación pendiente.
     * 
     * AUTORIZACIÓN: Solo encargados de acreditación (policy reject).
     * 
     * FLUJO:
     * 1. Busca la solicitud por ID
     * 2. Si no existe, retorna 404
     * 3. Verifica que el usuario sea encargado (403 si falla)
     * 4. Delega al service el rechazo
     * 5. Service valida que esté en estado 'pendiente'
     * 6. Service cambia estado a 'rechazada'
     * 7. Service NO actualiza fecha_limite de la evidencia (CRÍTICO)
     * 8. Service registra fecha_resolucion y resolutor_id
     * 9. Service envía notificación por email al solicitante
     * 10. Retorna la solicitud actualizada
     * 
     * DATOS OPCIONALES:
     * - justificacion: Comentario del encargado explicando por qué rechaza
     * 
     * PARÁMETROS:
     * - id: ID de la solicitud a rechazar (route parameter)
     * 
     * RESPUESTAS:
     * - 200: Solicitud rechazada exitosamente
     * - 400: Error de lógica (ej: solicitud ya fue resuelta)
     * - 403: Usuario no es encargado
     * - 404: Solicitud no existe
     * 
     * IMPACTO: Al rechazar, la fecha_limite de la evidencia NO cambia,
     * por lo que el docente debe cumplir con la fecha original.
     * 
     * USO: Encargado niega la solicitud del docente (ej: justificación insuficiente).
     */
    public function reject(ReviewExtensionRequestRequest $request, string $id): JsonResponse
    {
        // Buscar solicitud por ID
        $extensionRequest = $this->service->findById((int)$id);
        
        // Si no existe, retornar 404
        if (!$extensionRequest) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        // Autorización: Solo encargados pueden rechazar (403 si falla)
        $this->authorize('reject', $extensionRequest);

        try {
            // Obtener ID del encargado que rechaza (fallback temporal)
            $reviewerId = Auth::id() ?? 1;
            
            // Obtener justificación opcional del encargado
            $justification = $request->input('justificacion');
            
            // Delegar al service el rechazo completo (incluye notificar, NO actualiza fecha)
            $rejectedRequest = $this->service->reject(
                (int)$id, 
                $reviewerId, 
                $justification
            );

            // Disparar evento para notificaciones
            event(new ExtensionRequestRejected($rejectedRequest, $justification ?? 'Sin justificación proporcionada'));

            // Retornar 200 con solicitud rechazada
            return response()->json([
                'message' => 'Solicitud rechazada correctamente.',
                'data' => new ExtensionRequestResource($rejectedRequest)
            ], 200);
            
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Exception $exception) {
            Log::error('Error rejecting extension request', ['solicitud_id' => $id, 'error' => $exception->getMessage()]);
            return response()->json(['message' => 'Error al procesar la solicitud.'], 500);
        }
    }
}
