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
    protected const WITH_BASE = ['evidenceAssignment.evidence', 'elementAssignment', 'user', 'resolutor'];

    /**
     * Obtener todas las solicitudes con filtros y paginación.
     */
    public function getAll(array $filters = []): mixed
    {
        $perPage      = min($filters['per_page'] ?? 15, 100);
        $estado       = $filters['estado'] ?? null;
        $usuarioId    = $filters['usuario_id'] ?? null;
        $asignacionId = $filters['evidencia_asignacion_id'] ?? null;
        $fechaDesde   = $filters['fecha_desde'] ?? null;
        $fechaHasta   = $filters['fecha_hasta'] ?? null;

        return ExtensionRequest::with(static::WITH_BASE)
            ->when($estado,       fn($q) => $q->where('estado', $estado))
            ->when($usuarioId,    fn($q) => $q->where('usuario_id', $usuarioId))
            ->when($asignacionId, fn($q) => $q->where('evidencia_asignacion_id', $asignacionId))
            ->when($fechaDesde,   fn($q) => $q->where('created_at', '>=', $fechaDesde))
            ->when($fechaHasta,   fn($q) => $q->where('created_at', '<=', $fechaHasta))
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
    public function getByUser(int $usuarioId, array $filters = []): mixed
    {
        $filters['usuario_id'] = $usuarioId;
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
    public function approve(int $solicitudId, int $resolutorId, ?string $justificacion = null): ExtensionRequest
    {
        return DB::transaction(function () use ($solicitudId, $resolutorId, $justificacion) {
            $solicitud = ExtensionRequest::with(['evidenceAssignment', 'elementAssignment'])->find($solicitudId);
            if (!$solicitud) {
                throw new \Exception('La solicitud no existe.');
            }

            if ($solicitud->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden aprobar solicitudes pendientes.');
            }

            $solicitud->update([
                'estado'               => ExtensionRequest::ESTADO_APROBADA,
                'usuario_resolutor_id' => $resolutorId,
                'justificacion'        => $justificacion,
                'fecha_resolucion'     => now(),
            ]);

            // Propagar nueva fecha al modelo correspondiente (patrón XOR)
            if ($solicitud->evidenceAssignment) {
                $solicitud->evidenceAssignment->update([
                    'fecha_limite' => $solicitud->fecha_sugerida,
                ]);
            } elseif ($solicitud->elementAssignment) {
                $solicitud->elementAssignment->update([
                    'fecha_limite' => $solicitud->fecha_sugerida,
                ]);
            }

            return $solicitud->load(static::WITH_BASE);
        });
    }

    /**
     * Rechazar una solicitud de ampliación.
     *
     * No toca ninguna asignación — la fecha_limite permanece sin cambios.
     * Las notificaciones las maneja el controller vía event(ExtensionRequestRejected).
     */
    public function reject(int $solicitudId, int $resolutorId, string $justificacion): ExtensionRequest
    {
        return DB::transaction(function () use ($solicitudId, $resolutorId, $justificacion) {
            $solicitud = ExtensionRequest::find($solicitudId);
            if (!$solicitud) {
                throw new \Exception('La solicitud no existe.');
            }

            if ($solicitud->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden rechazar solicitudes pendientes.');
            }

            $solicitud->update([
                'estado'               => ExtensionRequest::ESTADO_RECHAZADA,
                'usuario_resolutor_id' => $resolutorId,
                'justificacion'        => $justificacion,
                'fecha_resolucion'     => now(),
            ]);

            return $solicitud->load(static::WITH_BASE);
        });
    }
}
