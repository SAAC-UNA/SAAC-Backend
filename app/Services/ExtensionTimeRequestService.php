<?php

namespace App\Services;

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Servicio que gestiona la lógica de negocio para solicitudes de ampliación del profesor (RF-15).
 * Incluye operaciones para listar, obtener, crear, actualizar y cancelar solicitudes,
 * así como validaciones específicas del dominio.
 */
class ExtensionTimeRequestService
{
    /**
     * Listar solicitudes de ampliación con paginación obligatoria y filtros dinámicos.
     *
     * @param int $perPage Cantidad de registros por página (default: 15).
     * @param array $filters Filtros opcionales: estado, evidencia_asignacion_id, usuario_id, fecha_desde, fecha_hasta
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listRequests(int $perPage = 15, array $filters = [])
    {
        $query = ExtensionRequest::query();

        // Filtro OPCIONAL por usuario (si no se envía, lista TODAS)
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        // Filtro por estado (opcional)
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        // Filtro por evidencia asignación (opcional)
        if (!empty($filters['evidencia_asignacion_id'])) {
            $query->where('evidencia_asignacion_id', $filters['evidencia_asignacion_id']);
        }

        // Filtro por rango de fechas (opcional)
        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_hasta']);
        }

        // OPTIMIZACIÓN: Cargar SOLO relaciones mínimas para listado
        $query->with([
            'user:usuario_id,nombre,cedula,email'
        ]);

        // Ordenar por fecha descendente (más recientes primero)
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Obtener una solicitud de ampliación específica por ID con sus relaciones.
     * Valida que la solicitud pertenezca al profesor autenticado.
     *
     * @param int $requestId Identificador único de la solicitud.
     * @param int $userId Identificador del profesor.
     * @return ExtensionRequest|null Retorna la solicitud o null si no existe.
     */
    public function getRequest(int $requestId, int $userId): ?ExtensionRequest
    {
        return ExtensionRequest::where('solicitud_ampliacion_id', $requestId)
            ->where('usuario_id', $userId)
            ->with([
                // Cargar info completa de evidencia y criterio (sin jerarquía extra)
                'evidenceAssignment',
                'evidenceAssignment.evidence',
                'evidenceAssignment.evidence.criterion'
            ])
            ->first();
    }

    /**
     * Obtener evidencias asignadas al profesor que están próximas a vencer.
     * Criterio: evidencias con fecha límite en los próximos 7 días o ya vencidas.
     *
     * @param int $userId Identificador del profesor.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUpcomingEvidences(int $userId)
    {
        $limitDate = Carbon::now()->addDays(7);

        return EvidenceAssignment::where('usuario_id', $userId)
            ->whereIn('estado', ['Pendiente', 'En Progreso']) // Solo evidencias no completadas
            ->where('fecha_limite', '<=', $limitDate)
            ->with([
                'evidence:evidencia_id,nomenclatura,descripcion,criterio_id',
                'evidence.criterion:criterio_id,nomenclatura,descripcion'
            ])
            ->select('evidencia_asignacion_id', 'evidencia_id', 'estado', 'fecha_limite')
            ->orderBy('fecha_limite', 'asc') // Más urgentes primero
            ->get();
    }

    /**
     * Crear una nueva solicitud de ampliación con validaciones de negocio.
     * 
     * Validaciones:
     * - El usuario debe estar asignado a la evidencia
     * - No puede tener solicitud pendiente para esa evidencia
     * - La evidencia no puede estar completada
     * - La fecha sugerida debe ser posterior a la fecha límite original
     * - La ampliación máxima es de 30 días
     *
     * @param array $data Datos validados del request.
     * @param int $userId Identificador del profesor solicitante.
     * @return ExtensionRequest
     * @throws ValidationException
     */
    public function createRequest(array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Verificar que la asignación existe
            $assignment = EvidenceAssignment::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
                ->first();

            if (!$assignment) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'La asignación de evidencia no existe o no está activa.'
                ]);
            }

            // 2. CRÍTICO: Verificar que el usuario es el asignado a la evidencia
            if ($assignment->usuario_id !== $userId) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'Solo puede solicitar ampliación para evidencias asignadas a usted.'
                ]);
            }

            // 3. Verificar que la evidencia no esté aprobada (Completada SÍ puede pedir ampliación)
            if ($assignment->estado === 'Aprobada') {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'No se puede solicitar ampliación para evidencias ya aprobadas por el encargado.'
                ]);
            }

            // 4. CRÍTICO: Validar que el plazo NO haya vencido (Criterio de Aceptación #3)
            if ($assignment->fecha_limite->lt(Carbon::now())) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'No se puede solicitar ampliación para evidencias con plazo ya vencido. El plazo venció el ' . $assignment->fecha_limite->format('d/m/Y H:i') . '. Contacte al encargado de acreditación.'
                ]);
            }

            // 5. Verificar que no tenga solicitud pendiente para esta asignación
            $pendingRequest = ExtensionRequest::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
                ->where('usuario_id', $userId)
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($pendingRequest) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'Ya tiene una solicitud pendiente para esta evidencia. Espere la resolución antes de crear otra.'
                ]);
            }

            // 6. Validar que la fecha sugerida sea posterior a la fecha límite original
            $suggestedDate = Carbon::parse($data['fecha_sugerida']);
            $originalDeadline = $assignment->fecha_limite;

            if ($suggestedDate->lte($originalDeadline)) {
                throw ValidationException::withMessages([
                    'fecha_sugerida' => 'La fecha sugerida debe ser posterior a la fecha límite original (' . $originalDeadline->format('d/m/Y H:i') . ').'
                ]);
            }

            // 7. Validar que la ampliación sea razonable (máximo 30 días adicionales)
            $extensionDays = $originalDeadline->diffInDays($suggestedDate);
            if ($extensionDays > 30) {
                throw ValidationException::withMessages([
                    'fecha_sugerida' => 'La ampliación solicitada no puede exceder 30 días desde la fecha límite original.'
                ]);
            }

            // 8. Crear la solicitud (created_at se genera automáticamente)
            $extensionRequest = ExtensionRequest::create([
                'evidencia_asignacion_id' => $data['evidencia_asignacion_id'],
                'usuario_id' => $userId,
                'motivo' => $data['motivo'],
                'fecha_sugerida' => $data['fecha_sugerida'],
                'estado' => ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            // 9. Cargar relaciones mínimas para confirmación
            return $extensionRequest->load([
                'evidenceAssignment:evidencia_asignacion_id,evidencia_id,fecha_limite',
                'evidenceAssignment.evidence:evidencia_id,nomenclatura,descripcion'
            ]);
        });
    }

    /**
     * Actualizar una solicitud de ampliación pendiente del profesor.
     *
     * @param int $requestId Identificador de la solicitud.
     * @param array $data Datos actualizados (motivo y/o fecha_sugerida).
     * @param int $userId Identificador del profesor.
     * @return ExtensionRequest
     * @throws ValidationException
     */
    public function updateRequest(int $requestId, array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($requestId, $data, $userId) {
            // 1. Obtener la solicitud
            $extensionRequest = ExtensionRequest::find($requestId);

            if (!$extensionRequest) {
                throw ValidationException::withMessages([
                    'solicitud' => 'Solicitud no encontrada.'
                ]);
            }

            // 2. CRÍTICO: Verificar que el usuario es el dueño de la solicitud
            if ($extensionRequest->usuario_id !== $userId) {
                throw ValidationException::withMessages([
                    'solicitud' => 'No tiene permisos para editar esta solicitud.'
                ]);
            }

            // 3. CRÍTICO: Solo se pueden editar solicitudes pendientes
            if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'solicitud' => 'Solo se pueden editar solicitudes pendientes.'
                ]);
            }

            // 4. Validar fecha_sugerida si se está actualizando
            if (isset($data['fecha_sugerida'])) {
                $suggestedDate = Carbon::parse($data['fecha_sugerida']);
                $originalDeadline = $extensionRequest->evidenceAssignment->fecha_limite;

                // Validar que sea posterior a la fecha límite original
                if ($suggestedDate->lte($originalDeadline)) {
                    throw ValidationException::withMessages([
                        'fecha_sugerida' => 'La fecha sugerida debe ser posterior a la fecha límite original (' . $originalDeadline->format('d/m/Y H:i') . ').'
                    ]);
                }

                // Validar ampliación máxima de 30 días
                $extensionDays = $originalDeadline->diffInDays($suggestedDate);
                if ($extensionDays > 30) {
                    throw ValidationException::withMessages([
                        'fecha_sugerida' => 'La ampliación solicitada no puede exceder 30 días desde la fecha límite original.'
                    ]);
                }
            }

            // 5. Actualizar solo los campos permitidos (motivo y fecha_sugerida)
            $extensionRequest->update([
                'motivo' => $data['motivo'] ?? $extensionRequest->motivo,
                'fecha_sugerida' => $data['fecha_sugerida'] ?? $extensionRequest->fecha_sugerida,
            ]);

            // 6. Cargar relaciones mínimas para confirmación
            return $extensionRequest->load([
                'evidenceAssignment:evidencia_asignacion_id,evidencia_id,fecha_limite',
                'evidenceAssignment.evidence:evidencia_id,nomenclatura,descripcion'
            ]);
        });
    }

    /**
     * Eliminar una solicitud de ampliación pendiente del profesor.
     * Solo se pueden eliminar solicitudes propias en estado pendiente.
     *
     * @param int $requestId Identificador de la solicitud.
     * @param int $userId Identificador del profesor.
     * @return bool
     * @throws ValidationException
     */
    public function deleteRequest(int $requestId, int $userId): bool
    {
        return DB::transaction(function () use ($requestId, $userId) {
            // 1. Buscar la solicitud
            $extensionRequest = ExtensionRequest::find($requestId);

            if (!$extensionRequest) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La solicitud no existe.'
                ]);
            }

            // 2. Verificar que la solicitud pertenece al usuario
            if ($extensionRequest->usuario_id !== $userId) {
                throw ValidationException::withMessages([
                    'solicitud' => 'Solo puede eliminar sus propias solicitudes.'
                ]);
            }

            // 3. Verificar que la solicitud esté pendiente
            if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'solicitud' => 'Solo se pueden eliminar solicitudes pendientes. Esta solicitud ya fue ' . strtolower($extensionRequest->estado) . '.'
                ]);
            }

            // 4. Eliminar físicamente el registro
            return $extensionRequest->delete();
        });
    }
}
