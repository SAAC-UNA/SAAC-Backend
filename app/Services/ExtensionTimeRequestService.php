<?php

namespace App\Services;

use App\Models\ExtensionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Servicio del profesor para solicitudes de ampliacion de plazo (RF-15).
 * Usa stored procedures para todas las operaciones de base de datos.
 */
class ExtensionTimeRequestService
{
    /**
     * Listar solicitudes con paginacion y filtros.
     */
    public function listRequests(int $perPage = 15, array $filters = [])
    {
        $page        = $filters['page'] ?? 1;
        $offset      = ($page - 1) * $perPage;
        $estado      = $filters['estado'] ?? null;
        $usuarioId   = $filters['usuario_id'] ?? null;
        $asignacionId= $filters['evidencia_asignacion_id'] ?? null;
        $fechaDesde  = $filters['fecha_desde'] ?? null;
        $fechaHasta  = $filters['fecha_hasta'] ?? null;

        $total = DB::select('CALL SP_CONTAR_SOLICITUDES_AMPLIACION(?, ?, ?, ?, ?)', [
            $estado, $usuarioId, $asignacionId, $fechaDesde, $fechaHasta,
        ])[0]->total ?? 0;

        $rows = DB::select('CALL SP_OBTENER_SOLICITUDES_AMPLIACION(?, ?, ?, ?, ?, ?, ?)', [
            $estado, $usuarioId, $asignacionId, $fechaDesde, $fechaHasta,
            $offset, $perPage,
        ]);

        $items = ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $rows));

        return new LengthAwarePaginator($items, (int) $total, $perPage, $page, [
            'path' => request()->url(),
        ]);
    }

    /**
     * Obtener una solicitud especifica por ID y usuario.
     */
    public function getRequest(int $requestId, int $userId): ?ExtensionRequest
    {
        $rows = DB::select('CALL SP_BUSCAR_SOLICITUD_AMPLIACION(?)', [$requestId]);
        if (empty($rows)) return null;

        $solicitud = $rows[0];
        // Validar que pertenece al usuario
        if ($solicitud->usuario_id !== $userId) return null;

        return ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    /**
     * Obtener evidencias proximas a vencer para un usuario.
     */
    public function getUpcomingEvidences(int $userId)
    {
        $limitDate = Carbon::now()->addDays(7)->format('Y-m-d H:i:s');
        $rows = DB::select('CALL SP_OBTENER_EVIDENCIAS_PROXIMAS(?, ?)', [$userId, $limitDate]);
        return \App\Models\EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows));
    }

    /**
     * Crear una nueva solicitud de ampliacion con validaciones de negocio.
     */
    public function createRequest(array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Verificar que la asignacion existe
            $assignmentRows = DB::select('CALL SP_BUSCAR_ASIGNACION_EVIDENCIA(?)', [
                $data['evidencia_asignacion_id'],
            ]);

            if (empty($assignmentRows)) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'La asignacion de evidencia no existe o no esta activa.',
                ]);
            }

            $assignment = $assignmentRows[0];

            // 2. Verificar que el usuario es el asignado
            if ($assignment->usuario_id !== $userId) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'Solo puede solicitar ampliacion para evidencias asignadas a usted.',
                ]);
            }

            // 3. Verificar que la evidencia no este aprobada
            if ($assignment->estado === 'Aprobada') {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'No se puede solicitar ampliacion para evidencias ya aprobadas.',
                ]);
            }

            // 4. Validar que el plazo no haya vencido
            $fechaLimite = Carbon::parse($assignment->fecha_limite);
            if ($fechaLimite->lt(Carbon::now())) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'No se puede solicitar ampliacion para evidencias con plazo ya vencido.',
                ]);
            }

            // 5. Verificar que no tenga solicitud pendiente
            $pendiente = DB::select('CALL SP_VERIFICAR_SOLICITUD_PENDIENTE(?)', [
                $data['evidencia_asignacion_id'],
            ]);
            if (!empty($pendiente) && $pendiente[0]->existe) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'Ya tiene una solicitud pendiente para esta evidencia.',
                ]);
            }

            // 6. Validar que la fecha sugerida sea posterior a la fecha limite
            $suggestedDate    = Carbon::parse($data['fecha_sugerida']);
            $originalDeadline = $fechaLimite;

            if ($suggestedDate->lte($originalDeadline)) {
                throw ValidationException::withMessages([
                    'fecha_sugerida' => 'La fecha sugerida debe ser posterior a la fecha limite original (' . $originalDeadline->format('d/m/Y H:i') . ').',
                ]);
            }

            // 7. Validar que la ampliacion sea de maximo 30 dias
            $extensionDays = $originalDeadline->diffInDays($suggestedDate);
            if ($extensionDays > 30) {
                throw ValidationException::withMessages([
                    'fecha_sugerida' => 'La ampliacion solicitada no puede exceder 30 dias desde la fecha limite original.',
                ]);
            }

            // 8. Crear la solicitud via SP
            $rows = DB::select('CALL SP_CREAR_SOLICITUD_AMPLIACION(?, ?, ?, ?, ?)', [
                $data['evidencia_asignacion_id'],
                $userId,
                $data['motivo'],
                $data['fecha_sugerida'],
                ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            return ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
        });
    }

    /**
     * Actualizar una solicitud de ampliacion pendiente del profesor.
     */
    public function updateRequest(int $requestId, array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($requestId, $data, $userId) {
            $rows = DB::select('CALL SP_BUSCAR_SOLICITUD_AMPLIACION(?)', [$requestId]);

            if (empty($rows)) {
                throw ValidationException::withMessages(['solicitud' => 'Solicitud no encontrada.']);
            }

            $solicitud = $rows[0];

            if ($solicitud->usuario_id !== $userId) {
                throw ValidationException::withMessages(['solicitud' => 'No tiene permisos para editar esta solicitud.']);
            }

            if ($solicitud->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages(['solicitud' => 'Solo se pueden editar solicitudes pendientes.']);
            }

            if (isset($data['fecha_sugerida'])) {
                $asignacion = DB::select('CALL SP_BUSCAR_ASIGNACION_EVIDENCIA(?)', [$solicitud->evidencia_asignacion_id]);
                $originalDeadline = Carbon::parse($asignacion[0]->fecha_limite ?? null);
                $suggestedDate    = Carbon::parse($data['fecha_sugerida']);

                if ($suggestedDate->lte($originalDeadline)) {
                    throw ValidationException::withMessages([
                        'fecha_sugerida' => 'La fecha sugerida debe ser posterior a la fecha limite original.',
                    ]);
                }

                if ($originalDeadline->diffInDays($suggestedDate) > 30) {
                    throw ValidationException::withMessages([
                        'fecha_sugerida' => 'La ampliacion no puede exceder 30 dias.',
                    ]);
                }
            }

            // Actualizar via SP_APROBAR/SP_RECHAZAR no aplica aqui.
            // Usamos SP_ACTUALIZAR_ASIGNACION_EVIDENCIA para actualizar datos de la solicitud.
            // Sin embargo, no hay SP especifico para editar SOLICITUD_AMPLIACION.
            // Usamos Eloquent solo para el UPDATE de la solicitud (no hay SP de update de solicitud).
            $solicitudModel = ExtensionRequest::findOrFail($requestId);
            $solicitudModel->update([
                'motivo'         => $data['motivo'] ?? $solicitudModel->motivo,
                'fecha_sugerida' => $data['fecha_sugerida'] ?? $solicitudModel->fecha_sugerida,
            ]);

            $updated = DB::select('CALL SP_BUSCAR_SOLICITUD_AMPLIACION(?)', [$requestId]);
            return ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $updated))->first();
        });
    }

    /**
     * Eliminar una solicitud pendiente del profesor.
     */
    public function deleteRequest(int $requestId, int $userId): bool
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $rows = DB::select('CALL SP_BUSCAR_SOLICITUD_AMPLIACION(?)', [$requestId]);

            if (empty($rows)) {
                throw ValidationException::withMessages(['solicitud' => 'La solicitud no existe.']);
            }

            $solicitud = $rows[0];

            if ($solicitud->usuario_id !== $userId) {
                throw ValidationException::withMessages(['solicitud' => 'Solo puede eliminar sus propias solicitudes.']);
            }

            if ($solicitud->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'solicitud' => 'Solo se pueden eliminar solicitudes pendientes.',
                ]);
            }

            DB::statement('CALL SP_ELIMINAR_SOLICITUD_AMPLIACION(?)', [$requestId]);
            return true;
        });
    }
}