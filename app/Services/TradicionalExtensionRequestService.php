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
     * Crear una nueva solicitud de ampliación para el modelo tradicional.
     *
     * Notifica por email a los encargados de acreditación de la carrera.
     * Las notificaciones internas (in-app) las dispara el controller
     * vía event(ExtensionRequestCreated).
     */
    public function createRequest(array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            $assignment = EvidenceAssignment::find($data['evidencia_asignacion_id']);
            if (!$assignment) {
                throw new \Exception('La asignacion de evidencia no existe.');
            }

            $hasPendingRequest = ExtensionRequest::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($hasPendingRequest) {
                throw new \Exception('Ya existe una solicitud pendiente para esta asignacion.');
            }

            $extensionRequest = ExtensionRequest::create([
                'evidencia_asignacion_id' => $data['evidencia_asignacion_id'],
                'elemento_asignacion_id'  => null,
                'usuario_id'              => $userId,
                'motivo'                  => $data['motivo'],
                'fecha_sugerida'          => $data['fecha_sugerida'],
                'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            // Email a encargados de acreditación de la carrera
            try {
                $assignmentWithRelations = EvidenceAssignment::with(
                    'process.accreditationCycle.careerCampus.career'
                )->find($data['evidencia_asignacion_id']);

                if ($assignmentWithRelations) {
                    $careerId = $assignmentWithRelations->process?->accreditationCycle?->careerCampus?->carrera_id ?? null;

                    $managers = User::whereHas('roles', fn($query) => $query->where('name', 'Encargado de Acreditacion'))
                        ->when($careerId, fn($query) => $query->whereHas('careers', fn($subQuery) => $subQuery->where('carrera_id', $careerId)))
                        ->get();

                    if ($managers->isEmpty() && $careerId) {
                        $managers = User::whereHas('roles', fn($query) => $query->where('name', 'Encargado de Acreditacion'))->get();
                    }

                    if ($managers->count() > 0) {
                        Notification::send($managers, new ExtensionRequestCreated($extensionRequest->load('user')));
                    }
                }
            } catch (\Exception $exception) {
                Log::warning('[Tradicional] No se pudo enviar email de solicitud de ampliacion', [
                    'solicitud_id' => $extensionRequest->solicitud_ampliacion_id,
                    'error'        => $exception->getMessage(),
                ]);
            }

            return $extensionRequest->load(static::WITH_BASE);
        });
    }
}
