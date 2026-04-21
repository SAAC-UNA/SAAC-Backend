<?php

namespace App\Services;

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Notifications\ExtensionRequestCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Servicio de ampliaciones para el modelo TRADICIONAL (evidencia_asignacion_id).
 *
 * Responsabilidad única: crear solicitudes de ampliación vinculadas a una
 * EvidenceAssignment. Toda la lógica compartida (approve, reject, consultas)
 * vive en AbstractExtensionRequestService.
 */
class TradicionalExtensionRequestService extends AbstractExtensionRequestService
{
    /**
     * Obtener solicitudes de ampliación del modelo tradicional (solo evidencia_asignacion_id).
     *
     * Sobrescribe el método base para excluir solicitudes del modelo flexible,
     * garantizando que solo se retornen registros tradicionales.
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
            ->whereNull('elemento_asignacion_id')
            ->when($estado,       fn($q) => $q->where('estado', $estado))
            ->when($usuarioId,    fn($q) => $q->where('usuario_id', $usuarioId))
            ->when($asignacionId, fn($q) => $q->where('evidencia_asignacion_id', $asignacionId))
            ->when($fechaDesde,   fn($q) => $q->where('created_at', '>=', $fechaDesde))
            ->when($fechaHasta,   fn($q) => $q->where('created_at', '<=', $fechaHasta))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Crear una nueva solicitud de ampliación para el modelo tradicional.
     *
     * Notifica por email a los encargados de acreditación de la carrera.
     * Las notificaciones internas (in-app) las dispara el controller
     * vía event(ExtensionRequestCreated).
     */
    public function createRequest(array $data, int $usuarioId): ExtensionRequest
    {
        return DB::transaction(function () use ($data, $usuarioId) {
            $asignacion = EvidenceAssignment::find($data['evidencia_asignacion_id']);
            if (!$asignacion) {
                throw new \InvalidArgumentException('La asignacion de evidencia no existe.');
            }

            $tienePendiente = ExtensionRequest::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($tienePendiente) {
                throw new \InvalidArgumentException('Ya existe una solicitud pendiente para esta asignacion.');
            }

            $solicitud = ExtensionRequest::create([
                'evidencia_asignacion_id' => $data['evidencia_asignacion_id'],
                'elemento_asignacion_id'  => null,
                'usuario_id'              => $usuarioId,
                'motivo'                  => $data['motivo'],
                'fecha_sugerida'          => $data['fecha_sugerida'],
                'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            // Email a encargados de acreditación de la carrera
            try {
                $asignacionConRelaciones = EvidenceAssignment::with(
                    'process.accreditationCycle.careerCampus.career'
                )->find($data['evidencia_asignacion_id']);

                if ($asignacionConRelaciones) {
                    $careerId = $asignacionConRelaciones->process?->accreditationCycle?->careerCampus?->carrera_id ?? null;

                    $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))
                        ->when($careerId, fn($q) => $q->whereHas('careers', fn($q2) => $q2->where('carrera_id', $careerId)))
                        ->get();

                    if ($managers->isEmpty() && $careerId) {
                        $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))->get();
                    }

                    if ($managers->count() > 0) {
                        Notification::send($managers, new ExtensionRequestCreated($solicitud->load('user')));
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[Tradicional] No se pudo enviar email de solicitud de ampliacion', [
                    'solicitud_id' => $solicitud->solicitud_ampliacion_id,
                    'error'        => $e->getMessage(),
                ]);
            }

            return $solicitud->load(static::WITH_BASE);
        });
    }
}
