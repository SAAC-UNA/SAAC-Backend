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

            $tienePendiente = ExtensionRequest::where('elemento_asignacion_id', $assignment->elemento_asignacion_id)
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($tienePendiente) {
                throw new \InvalidArgumentException(
                    'Ya existe una solicitud de ampliación pendiente para esta asignación.'
                );
            }

            if ($assignment->fecha_limite) {
                $fechaLimite = \Carbon\Carbon::parse($assignment->fecha_limite);
                $sugerida    = \Carbon\Carbon::parse($data['fecha_sugerida']);

                if ($sugerida->lte($fechaLimite)) {
                    throw new \InvalidArgumentException(
                        'La fecha sugerida debe ser posterior a la fecha límite actual ('
                        . $fechaLimite->format('d/m/Y') . ').'
                    );
                }

                if ($fechaLimite->diffInDays($sugerida) > 30) {
                    throw new \InvalidArgumentException(
                        'La ampliación no puede exceder 30 días desde la fecha límite actual.'
                    );
                }
            }

            $solicitud = ExtensionRequest::create([
                'elemento_asignacion_id'  => $assignment->elemento_asignacion_id,
                'evidencia_asignacion_id' => null,
                'usuario_id'              => $userId,
                'motivo'                  => $data['motivo'],
                'fecha_sugerida'          => $data['fecha_sugerida'],
                'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            // Email a encargados de acreditación de la carrera
            try {
                $proceso  = $assignment->proceso ?? \App\Models\Process::find($assignment->proceso_id);
                $careerId = $proceso?->accreditationCycle?->careerCampus?->carrera_id ?? null;

                $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))
                    ->when($careerId, fn($q) => $q->whereHas('careers', fn($q2) => $q2->where('carrera_id', $careerId)))
                    ->get();

                if ($managers->isEmpty() && $careerId) {
                    $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))->get();
                }

                if ($managers->count() > 0) {
                    Notification::send($managers, new ExtensionRequestCreated($solicitud->load('user')));
                }
            } catch (\Exception $e) {
                Log::warning('[Flexible] No se pudo enviar email de solicitud de ampliacion', [
                    'solicitud_id' => $solicitud->solicitud_ampliacion_id,
                    'error'        => $e->getMessage(),
                ]);
            }

            return $solicitud->load(static::WITH_BASE);
        });
    }
}
