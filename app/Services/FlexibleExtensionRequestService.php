<?php

namespace App\Services;

use App\Models\ElementAssignment;
use App\Models\ExtensionRequest;
use App\Models\User;
use App\Notifications\ExtensionRequestCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Servicio de ampliaciones para el modelo FLEXIBLE (elemento_asignacion_id).
 *
 * Responsabilidad única: crear solicitudes de ampliación vinculadas a un
 * ElementAssignment. Toda la lógica compartida (approve, reject, consultas)
 * vive en AbstractExtensionRequestService.
 */
class FlexibleExtensionRequestService extends AbstractExtensionRequestService
{
    /**
     * Obtener solicitudes de ampliación del modelo flexible (solo elemento_asignacion_id).
     *
     * Sobrescribe el método base para acotar la consulta exclusivamente a registros
     * del modelo flexible, excluyendo las solicitudes tradicionales de la vista.
     */
    public function getAll(array $filters = []): mixed
    {
        $filters['_scope'] = 'flexible';
        $perPage    = min($filters['per_page'] ?? 15, 100);
        $status     = $filters['estado']    ?? null;
        $userId     = $filters['usuario_id'] ?? null;
        $assignmentId = $filters['elemento_asignacion_id'] ?? null;
        $dateFrom   = $filters['fecha_desde'] ?? null;
        $dateTo     = $filters['fecha_hasta'] ?? null;

        return \App\Models\ExtensionRequest::with(static::WITH_BASE)
            ->whereNotNull('elemento_asignacion_id')
            ->when($status,       fn($query) => $query->where('estado', $status))
            ->when($userId,       fn($query) => $query->where('usuario_id', $userId))
            ->when($assignmentId, fn($query) => $query->where('elemento_asignacion_id', $assignmentId))
            ->when($dateFrom,     fn($query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo,       fn($query) => $query->where('created_at', '<=', $dateTo))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Crear una nueva solicitud de ampliación para el modelo flexible.
     *
     * Valida que el usuario sea el dueño de la asignación, que no haya
     * una solicitud pendiente, y que la fecha sugerida sea válida.
     * Notifica por email a los encargados de acreditación de la carrera.
     */
    public function createRequest(ElementAssignment $assignment, array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($assignment, $data, $userId) {
            if ($assignment->usuario_id !== $userId) {
                throw new \InvalidArgumentException(
                    'Solo puede solicitar ampliación para asignaciones asignadas a usted.'
                );
            }

            $hasPendingRequest = ExtensionRequest::where('elemento_asignacion_id', $assignment->elemento_asignacion_id)
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($hasPendingRequest) {
                throw new \InvalidArgumentException(
                    'Ya existe una solicitud de ampliación pendiente para esta asignación.'
                );
            }

            if ($assignment->fecha_limite) {
                $deadlineDate = \Carbon\Carbon::parse($assignment->fecha_limite);
                $suggestedDate = \Carbon\Carbon::parse($data['fecha_sugerida']);

                if ($suggestedDate->lte($deadlineDate)) {
                    throw new \InvalidArgumentException(
                        'La fecha sugerida debe ser posterior a la fecha límite actual ('
                        . $deadlineDate->format('d/m/Y') . ').'
                    );
                }

                if ($deadlineDate->diffInDays($suggestedDate) > 30) {
                    throw new \InvalidArgumentException(
                        'La ampliación no puede exceder 30 días desde la fecha límite actual.'
                    );
                }
            }

            $extensionRequest = ExtensionRequest::create([
                'elemento_asignacion_id'  => $assignment->elemento_asignacion_id,
                'usuario_id'              => $userId,
                'motivo'                  => $data['motivo'],
                'fecha_sugerida'          => $data['fecha_sugerida'],
                'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            // Email a encargados de acreditación de la carrera
            try {
                $proceso  = $assignment->proceso ?? \App\Models\Process::find($assignment->proceso_id);
                $careerId = $proceso?->accreditationCycle?->careerCampus?->carrera_id ?? null;

                $managers = User::whereHas('roles', fn($query) => $query->where('name', 'Encargado de Acreditacion'))
                    ->when($careerId, fn($query) => $query->whereHas('careers', fn($subQuery) => $subQuery->where('carrera_id', $careerId)))
                    ->get();

                if ($managers->isEmpty() && $careerId) {
                    $managers = User::whereHas('roles', fn($query) => $query->where('name', 'Encargado de Acreditacion'))->get();
                }

                if ($managers->count() > 0) {
                    Notification::send($managers, new ExtensionRequestCreated($extensionRequest->load('user')));
                }
            } catch (\Exception $exception) {
                Log::warning('[Flexible] No se pudo enviar email de solicitud de ampliacion', [
                    'solicitud_id' => $extensionRequest->solicitud_ampliacion_id,
                    'error'        => $exception->getMessage(),
                ]);
            }

            return $extensionRequest->load(static::WITH_BASE);
        });
    }
}
