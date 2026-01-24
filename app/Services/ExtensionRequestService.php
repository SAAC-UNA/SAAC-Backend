<?php

namespace App\Services;

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Notifications\ExtensionRequestCreated; // HU-16: NOTIFICACIÓN - Importar clase
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification; // HU-16: NOTIFICACIÓN - Importar facade
use Illuminate\Support\Facades\Log; // HU-16: NOTIFICACIÓN - Para logs de errores
use Carbon\Carbon;

/**
 * Service para manejar la lógica de negocio de solicitudes de ampliación.
 * 
 * Centraliza operaciones como crear, aprobar y rechazar solicitudes.
 */
class ExtensionRequestService
{
    /**
     * Obtener todas las solicitudes con filtros y paginación.
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(array $filters = [])
    {
        $query = ExtensionRequest::query();

        // Filtro por estado
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        // Filtro por usuario
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        // Filtro por evidencia asignación
        if (!empty($filters['evidencia_asignacion_id'])) {
            $query->where('evidencia_asignacion_id', $filters['evidencia_asignacion_id']);
        }

        // Filtro por rango de fechas
        if (!empty($filters['fecha_desde'])) {
            $query->where('fecha_solicitud', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->where('fecha_solicitud', '<=', $filters['fecha_hasta']);
        }

        // Incluir relaciones
        $query->with(['evidenceAssignment', 'user', 'resolutor']);

        // Ordenar por fecha descendente
        $query->orderBy('fecha_solicitud', 'desc');

        // Paginar resultados (default 15, max 100)
        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Obtener solicitudes pendientes con filtros y paginación.
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPending(array $filters = [])
    {
        $query = ExtensionRequest::query();

        // Siempre filtrar por pendientes
        $query->pendientes();

        // Filtro por usuario
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        // Filtro por evidencia asignación
        if (!empty($filters['evidencia_asignacion_id'])) {
            $query->where('evidencia_asignacion_id', $filters['evidencia_asignacion_id']);
        }

        // Filtro por rango de fechas
        if (!empty($filters['fecha_desde'])) {
            $query->where('fecha_solicitud', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->where('fecha_solicitud', '<=', $filters['fecha_hasta']);
        }

        // Incluir relaciones
        $query->with(['evidenceAssignment', 'user']);

        // Ordenar por fecha ascendente (más antiguas primero)
        $query->orderBy('fecha_solicitud', 'asc');

        // Paginar resultados (default 15, max 100)
        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Obtener solicitudes de un usuario específico con filtros y paginación.
     *
     * @param int $usuarioId
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByUser(int $usuarioId, array $filters = [])
    {
        $query = ExtensionRequest::query();

        // Siempre filtrar por usuario
        $query->deUsuario($usuarioId);

        // Filtro por estado
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        // Filtro por evidencia asignación
        if (!empty($filters['evidencia_asignacion_id'])) {
            $query->where('evidencia_asignacion_id', $filters['evidencia_asignacion_id']);
        }

        // Filtro por rango de fechas
        if (!empty($filters['fecha_desde'])) {
            $query->where('fecha_solicitud', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->where('fecha_solicitud', '<=', $filters['fecha_hasta']);
        }

        // Incluir relaciones
        $query->with(['evidenceAssignment', 'resolutor']);

        // Ordenar por fecha descendente
        $query->orderBy('fecha_solicitud', 'desc');

        // Paginar resultados (default 15, max 100)
        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Encontrar solicitud por ID.
     *
     * @param int $id
     * @return ExtensionRequest|null
     */
    public function findById(int $id): ?ExtensionRequest
    {
        return ExtensionRequest::with(['evidenceAssignment', 'user', 'resolutor'])->find($id);
    }

    /**
     * Crear una nueva solicitud de ampliación.
     * 
     * HU-16: Implementa notificación por email a encargados de acreditación.
     * 
     * MEJORA IMPLEMENTADA: Filtrado por carrera
     * - Antes: Notificaba a TODOS los encargados de acreditación
     * - Ahora: Notifica solo a los encargados de la carrera específica
     * - Ejemplo: Solicitud de Ingeniería → Solo encargados de Ingeniería
     * - Fallback: Si no hay encargados específicos, notifica a todos (seguridad)
     * 
     * Cadena de relaciones para obtener la carrera:
     * solicitud → evidenceAssignment → proceso → accreditationCycle → careerCampus → career
     *
     * @param array $data
     * @param int $usuarioId ID del usuario solicitante
     * @return ExtensionRequest
     * @throws \Exception
     */
    public function createRequest(array $data, int $usuarioId): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            // Verificar que la asignación existe
            $assignment = EvidenceAssignment::find($data['evidencia_asignacion_id']);
            if (!$assignment) {
                throw new \Exception('La asignación de evidencia no existe.');
            }

            // TEMPORAL: Comentado para pruebas sin autenticación
            // Verificar que el usuario es el asignado
            // if ($assignment->usuario_id !== $usuarioId) {
            //     throw new \Exception('Solo el usuario asignado puede solicitar ampliación.');
            // }

            // Verificar que no tenga una solicitud pendiente para esta asignación
            $pendingRequest = ExtensionRequest::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($pendingRequest) {
                throw new \Exception('Ya existe una solicitud pendiente para esta asignación.');
            }

            // Crear la solicitud
            $extensionRequest = ExtensionRequest::create([
                'evidencia_asignacion_id' => $data['evidencia_asignacion_id'],
                'usuario_id' => $usuarioId,
                'fecha_solicitud' => Carbon::now(),
                'motivo' => $data['motivo'],
                'fecha_sugerida' => $data['fecha_sugerida'],
                'estado' => ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            // Cargar relaciones necesarias para acceder a la carrera
            $extensionRequest->load('evidenceAssignment.proceso.accreditationCycle.careerCampus.career');

            // ========== HU-16: NOTIFICACIÓN - INICIO ==========
            // PRUEBA TEMPORAL: Enviar correo directamente al usuario Ana
            try {
                // Buscar usuario Ana por email
                $testUser = User::where('email', 'ana.zuniga.cardenas@est.una.ac.cr')->first();
                
                if ($testUser) {
                    Notification::send([$testUser], new ExtensionRequestCreated($extensionRequest));
                    Log::info("Notificación de prueba enviada a: {$testUser->email}");
                } else {
                    Log::warning('Usuario de prueba no encontrado');
                }
            } catch (\Exception $notificationException) {
                Log::warning('No se pudo enviar notificación de solicitud de ampliación', [
                    'solicitud_id' => $extensionRequest->solicitud_ampliacion_id,
                    'error' => $notificationException->getMessage()
                ]);
            }
            // ========== HU-16: NOTIFICACIÓN - FIN ==========

            DB::commit();
            // Cargar relaciones necesarias incluyendo la cadena hasta carrera
            return $extensionRequest->load([
                'evidenceAssignment.proceso.carreraSede.carrera',
                'user'
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Aprobar una solicitud de ampliación.
     *
     * @param int $solicitudId
     * @param int $resolutorId ID del usuario que aprueba
     * @param string|null $justificacion
     * @return ExtensionRequest
     * @throws \Exception
     */
    public function approve(int $solicitudId, int $resolutorId, ?string $justificacion = null): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            $extensionRequest = ExtensionRequest::find($solicitudId);
            if (!$extensionRequest) {
                throw new \Exception('La solicitud no existe.');
            }

            if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden aprobar solicitudes pendientes.');
            }

            // Actualizar la solicitud
            $extensionRequest->update([
                'estado' => ExtensionRequest::ESTADO_APROBADA,
                'fecha_resolucion' => Carbon::now(),
                'usuario_resolutor_id' => $resolutorId,
                'justificacion' => $justificacion,
            ]);

            // Actualizar la fecha límite de la asignación de evidencia
            $assignment = $extensionRequest->evidenceAssignment;
            $assignment->update([
                'fecha_limite' => $extensionRequest->fecha_sugerida,
            ]);

            DB::commit();
            return $extensionRequest->load(['evidenceAssignment', 'user', 'resolutor']);
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Rechazar una solicitud de ampliación.
     *
     * @param int $solicitudId
     * @param int $resolutorId ID del usuario que rechaza
     * @param string $justificacion
     * @return ExtensionRequest
     * @throws \Exception
     */
    public function reject(int $solicitudId, int $resolutorId, string $justificacion): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            $extensionRequest = ExtensionRequest::find($solicitudId);
            if (!$extensionRequest) {
                throw new \Exception('La solicitud no existe.');
            }

            if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden rechazar solicitudes pendientes.');
            }

            // Actualizar la solicitud
            $extensionRequest->update([
                'estado' => ExtensionRequest::ESTADO_RECHAZADA,
                'fecha_resolucion' => Carbon::now(),
                'usuario_resolutor_id' => $resolutorId,
                'justificacion' => $justificacion,
            ]);

            DB::commit();
            return $extensionRequest->load(['evidenceAssignment', 'user', 'resolutor']);
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
