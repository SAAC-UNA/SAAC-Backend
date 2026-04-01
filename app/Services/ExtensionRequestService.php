<?php

namespace App\Services;

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Notifications\ExtensionRequestCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Servicio para solicitudes de ampliacion de plazo.
 */
class ExtensionRequestService
{
    private const WITH_BASE = ['evidenceAssignment.evidence', 'elementAssignment', 'user', 'resolutor'];

    /**
     * Obtener todas las solicitudes con filtros y paginacion.
     */
    public function getAll(array $filters = [])
    {
        $perPage     = min($filters['per_page'] ?? 15, 100);
        $estado      = $filters['estado'] ?? null;
        $usuarioId   = $filters['usuario_id'] ?? null;
        $asignacionId= $filters['evidencia_asignacion_id'] ?? null;
        $fechaDesde  = $filters['fecha_desde'] ?? null;
        $fechaHasta  = $filters['fecha_hasta'] ?? null;

        return ExtensionRequest::with(self::WITH_BASE)
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
    public function getPending(array $filters = [])
    {
        $filters['estado'] = ExtensionRequest::ESTADO_PENDIENTE;
        return $this->getAll($filters);
    }

    /**
     * Obtener solicitudes de un usuario especifico.
     */
    public function getByUser(int $usuarioId, array $filters = [])
    {
        $filters['usuario_id'] = $usuarioId;
        return $this->getAll($filters);
    }

    /**
     * Encontrar solicitud por ID.
     */
    public function findById(int $id): ?ExtensionRequest
    {
        return ExtensionRequest::with(self::WITH_BASE)->find($id);
    }

    /**
     * Crear una nueva solicitud de ampliacion.
     * Mantiene logica de notificacion por email a encargados de acreditacion (HU-16).
     */
    public function createRequest(array $data, int $usuarioId): ExtensionRequest
    {
        return DB::transaction(function () use ($data, $usuarioId) {
            $asignacion = EvidenceAssignment::find($data['evidencia_asignacion_id']);
            if (!$asignacion) {
                throw new \Exception('La asignacion de evidencia no existe.');
            }

            $tienePendiente = ExtensionRequest::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($tienePendiente) {
                throw new \Exception('Ya existe una solicitud pendiente para esta asignacion.');
            }

            $extensionRequest = ExtensionRequest::create([
                'evidencia_asignacion_id' => $data['evidencia_asignacion_id'],
                'usuario_id'              => $usuarioId,
                'motivo'                  => $data['motivo'],
                'fecha_sugerida'          => $data['fecha_sugerida'],
                'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            // HU-16: Notificacion a encargados de acreditacion de la carrera
            try {
                $assignment = EvidenceAssignment::with(
                    'process.accreditationCycle.careerCampus.career'
                )->find($data['evidencia_asignacion_id']);

                if ($assignment) {
                    $careerId = $assignment->process->accreditationCycle->careerCampus->carrera_id ?? null;

                    $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))
                        ->when($careerId, fn($q) => $q->whereHas('careers', fn($q2) => $q2->where('carrera_id', $careerId)))
                        ->get();

                    if ($managers->isEmpty() && $careerId) {
                        $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))->get();
                    }

                    if ($managers->count() > 0) {
                        Notification::send($managers, new ExtensionRequestCreated($extensionRequest));
                    }
                }
            } catch (\Exception $n) {
                Log::warning('No se pudo enviar notificacion de solicitud de ampliacion', [
                    'solicitud_id' => $extensionRequest->solicitud_ampliacion_id,
                    'error'        => $n->getMessage(),
                ]);
            }

            return $extensionRequest->load(self::WITH_BASE);
        });
    }

    /**
     * Aprobar una solicitud de ampliacion.
     * El SP actualiza tambien la fecha_limite de la asignacion.
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

            // Actualizar fecha_limite en la asignacion correspondiente (patron XOR)
            if ($solicitud->evidenceAssignment) {
                $solicitud->evidenceAssignment->update([
                    'fecha_limite' => $solicitud->fecha_sugerida,
                ]);
            } elseif ($solicitud->elementAssignment) {
                $solicitud->elementAssignment->update([
                    'fecha_limite' => $solicitud->fecha_sugerida,
                ]);
            }

            return $solicitud->load(self::WITH_BASE);
        });
    }

    /**
     * Rechazar una solicitud de ampliacion.
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

            return $solicitud->load(self::WITH_BASE);
        });
    }
}