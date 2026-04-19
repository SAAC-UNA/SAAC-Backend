<?php

namespace App\Services;

use App\Contracts\ExtensionRequestContract;
use App\Models\ExtensionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Implementación base compartida para solicitudes de ampliación de plazo.
 *
 * Contiene toda la lógica de negocio que es idéntica entre el modelo
 * tradicional y el modelo flexible:
 *   - approve()    → patrón XOR: actualiza EvidenceAssignment O ElementAssignment
 *   - reject()     → solo cambia estado, sin tocar asignaciones
 *   - getAll()     → consulta SOLICITUD_AMPLIACION sin distinción de modelo
 *   - getPending() → filtro de estado sobre getAll()
 *   - getByUser()  → filtro de usuario sobre getAll()
 *   - findById()   → búsqueda por PK
 *
 * Las subclases solo implementan createRequest() con la firma apropiada
 * a su modelo.
 */
abstract class AbstractExtensionRequestService implements ExtensionRequestContract
{
    protected const WITH_BASE = ['evidenceAssignment.evidence', 'evidenceAssignment.process', 'elementAssignment.process', 'user', 'resolutor'];

    /**
     * Obtener todas las solicitudes con filtros y paginación.
     */
    public function getAll(array $filters = []): mixed
    {
        $perPage      = min($filters['per_page'] ?? 15, 100);
        $status       = $filters['estado'] ?? null;
        $userId       = $filters['usuario_id'] ?? null;
        $assignmentId = $filters['evidencia_asignacion_id'] ?? null;
        $dateFrom     = $filters['fecha_desde'] ?? null;
        $dateTo       = $filters['fecha_hasta'] ?? null;

        return ExtensionRequest::with(static::WITH_BASE)
            ->when($status,       fn($query) => $query->where('estado', $status))
            ->when($userId,       fn($query) => $query->where('usuario_id', $userId))
            ->when($assignmentId, fn($query) => $query->where('evidencia_asignacion_id', $assignmentId))
            ->when($dateFrom,     fn($query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo,       fn($query) => $query->where('created_at', '<=', $dateTo))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtener solicitudes pendientes.
     */
    public function getPending(array $filters = []): mixed
    {
        $filters['estado'] = ExtensionRequest::ESTADO_PENDIENTE;
        return $this->getAll($filters);
    }

    /**
     * Obtener solicitudes de un usuario específico.
     */
    public function getByUser(int $userId, array $filters = []): mixed
    {
        $filters['usuario_id'] = $userId;
        return $this->getAll($filters);
    }

    /**
     * Encontrar solicitud por ID.
     */
    public function findById(int $id): ?ExtensionRequest
    {
        return ExtensionRequest::with(static::WITH_BASE)->find($id);
    }

    /**
     * Aprobar una solicitud de ampliación.
     *
     * Patrón XOR: actualiza fecha_limite en EvidenceAssignment (tradicional)
     * O en ElementAssignment (flexible), según cuál FK tenga la solicitud.
     * Las notificaciones las maneja el controller vía event(ExtensionRequestApproved).
     */
    public function approve(int $requestId, int $resolverUserId, ?string $justification = null): ExtensionRequest
    {
        return DB::transaction(function () use ($requestId, $resolverUserId, $justification) {
            $extensionRequest = ExtensionRequest::with(['evidenceAssignment', 'elementAssignment'])->find($requestId);
            if (!$extensionRequest) {
                throw new \Exception('La solicitud no existe.');
            }

            if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden aprobar solicitudes pendientes.');
            }

            $extensionRequest->update([
                'estado'               => ExtensionRequest::ESTADO_APROBADA,
                'usuario_resolutor_id' => $resolverUserId,
                'justificacion'        => $justification,
                'fecha_resolucion'     => now(),
            ]);

            // Propagar nueva fecha al modelo correspondiente (patrón XOR)
            if ($extensionRequest->evidenceAssignment) {
                $extensionRequest->evidenceAssignment->update([
                    'fecha_limite' => $extensionRequest->fecha_sugerida,
                ]);
            } elseif ($extensionRequest->elementAssignment) {
                $extensionRequest->elementAssignment->update([
                    'fecha_limite' => $extensionRequest->fecha_sugerida,
                ]);
            }

            return $extensionRequest->load(static::WITH_BASE);
        });
    }

    /**
     * Rechazar una solicitud de ampliación.
     *
     * No toca ninguna asignación — la fecha_limite permanece sin cambios.
     * Las notificaciones las maneja el controller vía event(ExtensionRequestRejected).
     */
    public function reject(int $requestId, int $resolverUserId, string $justification): ExtensionRequest
    {
        return DB::transaction(function () use ($requestId, $resolverUserId, $justification) {
            $extensionRequest = ExtensionRequest::find($requestId);
            if (!$extensionRequest) {
                throw new \Exception('La solicitud no existe.');
            }

            if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden rechazar solicitudes pendientes.');
            }

            $extensionRequest->update([
                'estado'               => ExtensionRequest::ESTADO_RECHAZADA,
                'usuario_resolutor_id' => $resolverUserId,
                'justificacion'        => $justification,
                'fecha_resolucion'     => now(),
            ]);

            return $extensionRequest->load(static::WITH_BASE);
        });
    }
}
