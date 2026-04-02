<?php

namespace App\Services;

use App\Models\ElementAssignment;
use App\Models\ElementExtensionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Servicio para solicitudes de ampliación de plazo — modelo flexible (elemento).
 * Opera exclusivamente sobre solicitudes con elemento_asignacion_id.
 */
class ElementExtensionTimeRequestService
{
    private const WITH_BASE = [
        'elementAssignment.element',
        'user',
        'resolutor',
    ];

    /**
     * Listar solicitudes de elemento con paginación y filtros.
     * Siempre filtra whereNotNull('elemento_asignacion_id').
     */
    public function listRequests(int $perPage = 15, array $filters = [])
    {
        $estado               = $filters['estado'] ?? null;
        $usuarioId            = $filters['usuario_id'] ?? null;
        $elementoAsignacionId = $filters['elemento_asignacion_id'] ?? null;
        $fechaDesde           = $filters['fecha_desde'] ?? null;
        $fechaHasta           = $filters['fecha_hasta'] ?? null;

        return ElementExtensionRequest::with(self::WITH_BASE)
            ->when($estado,               fn($q) => $q->where('estado', $estado))
            ->when($usuarioId,            fn($q) => $q->where('usuario_id', $usuarioId))
            ->when($elementoAsignacionId, fn($q) => $q->where('elemento_asignacion_id', $elementoAsignacionId))
            ->when($fechaDesde,           fn($q) => $q->where('created_at', '>=', $fechaDesde))
            ->when($fechaHasta,           fn($q) => $q->where('created_at', '<=', $fechaHasta))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtener una solicitud de elemento específica por ID.
     */
    public function getById(int $requestId): ?ElementExtensionRequest
    {
        return ElementExtensionRequest::with(self::WITH_BASE)
            ->find($requestId);
    }

    /**
     * Crear solicitud de ampliación para una asignación de elemento.
     */
    public function createRequest(array $data, int $userId): ElementExtensionRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            $asignacionId = $data['elemento_asignacion_id'];

            $assignment = ElementAssignment::find($asignacionId);

            if (!$assignment) {
                throw ValidationException::withMessages([
                    'elemento_asignacion_id' => 'La asignación de elemento no existe.',
                ]);
            }

            if ($assignment->usuario_id !== $userId) {
                throw ValidationException::withMessages([
                    'elemento_asignacion_id' => 'Solo puede solicitar ampliación para asignaciones propias.',
                ]);
            }

            if (in_array($assignment->estado, ['Validada', 'Completado'])) {
                throw ValidationException::withMessages([
                    'elemento_asignacion_id' => 'No se puede solicitar ampliación para asignaciones ya completadas o validadas.',
                ]);
            }

            $fechaLimite = Carbon::parse($assignment->fecha_limite);
            if ($fechaLimite->lt(Carbon::now())) {
                throw ValidationException::withMessages([
                    'elemento_asignacion_id' => 'No se puede solicitar ampliación para asignaciones con plazo ya vencido.',
                ]);
            }

            $tienePendiente = ElementExtensionRequest::where('elemento_asignacion_id', $asignacionId)
                ->where('estado', ElementExtensionRequest::ESTADO_PENDIENTE)
                ->exists();
            if ($tienePendiente) {
                throw ValidationException::withMessages([
                    'elemento_asignacion_id' => 'Ya tiene una solicitud pendiente para esta asignación.',
                ]);
            }

            $suggestedDate = Carbon::parse($data['fecha_sugerida']);
            if ($suggestedDate->lte($fechaLimite)) {
                throw ValidationException::withMessages([
                    'fecha_sugerida' => 'La fecha sugerida debe ser posterior a la fecha límite original (' . $fechaLimite->format('d/m/Y') . ').',
                ]);
            }

            if ($fechaLimite->diffInDays($suggestedDate) > 30) {
                throw ValidationException::withMessages([
                    'fecha_sugerida' => 'La ampliación solicitada no puede exceder 30 días desde la fecha límite original.',
                ]);
            }

            $solicitud = ElementExtensionRequest::create([
                'elemento_asignacion_id' => $asignacionId,
                'usuario_id'             => $userId,
                'motivo'                 => $data['motivo'],
                'fecha_sugerida'         => $data['fecha_sugerida'],
                'estado'                 => ElementExtensionRequest::ESTADO_PENDIENTE,
            ]);

            return $solicitud->load(self::WITH_BASE);
        });
    }

    /**
     * Actualizar una solicitud de elemento pendiente.
     */
    public function updateRequest(int $requestId, array $data, int $userId): ElementExtensionRequest
    {
        return DB::transaction(function () use ($requestId, $data, $userId) {
            $solicitud = ElementExtensionRequest::with('elementAssignment')
                ->find($requestId);

            if (!$solicitud) {
                throw ValidationException::withMessages(['solicitud' => 'Solicitud no encontrada.']);
            }
            if ($solicitud->usuario_id !== $userId) {
                throw ValidationException::withMessages(['solicitud' => 'No tiene permisos para editar esta solicitud.']);
            }
            if ($solicitud->estado !== ElementExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages(['solicitud' => 'Solo se pueden editar solicitudes pendientes.']);
            }

            if (isset($data['fecha_sugerida']) && $solicitud->elementAssignment) {
                $originalDeadline = Carbon::parse($solicitud->elementAssignment->fecha_limite);
                $suggestedDate    = Carbon::parse($data['fecha_sugerida']);

                if ($suggestedDate->lte($originalDeadline)) {
                    throw ValidationException::withMessages([
                        'fecha_sugerida' => 'La fecha sugerida debe ser posterior a la fecha límite original.',
                    ]);
                }
                if ($originalDeadline->diffInDays($suggestedDate) > 30) {
                    throw ValidationException::withMessages([
                        'fecha_sugerida' => 'La ampliación no puede exceder 30 días.',
                    ]);
                }
            }

            $solicitud->update([
                'motivo'         => $data['motivo']         ?? $solicitud->motivo,
                'fecha_sugerida' => $data['fecha_sugerida'] ?? $solicitud->fecha_sugerida,
            ]);

            return $solicitud->load(self::WITH_BASE);
        });
    }

    /**
     * Eliminar una solicitud de elemento pendiente.
     */
    public function deleteRequest(int $requestId, int $userId): bool
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $solicitud = ElementExtensionRequest::find($requestId);

            if (!$solicitud) {
                throw ValidationException::withMessages(['solicitud' => 'La solicitud no existe.']);
            }
            if ($solicitud->usuario_id !== $userId) {
                throw ValidationException::withMessages(['solicitud' => 'Solo puede eliminar sus propias solicitudes.']);
            }
            if ($solicitud->estado !== ElementExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages(['solicitud' => 'Solo se pueden eliminar solicitudes pendientes.']);
            }

            $solicitud->delete();
            return true;
        });
    }

    /**
     * Cancelar una solicitud de elemento pendiente (cambia estado a 'cancelada').
     */
    public function cancelRequest(int $requestId, int $userId): ElementExtensionRequest
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $solicitud = ElementExtensionRequest::find($requestId);

            if (!$solicitud) {
                throw ValidationException::withMessages(['solicitud' => 'La solicitud no existe.']);
            }
            if ($solicitud->usuario_id !== $userId) {
                throw ValidationException::withMessages(['solicitud' => 'Solo puede cancelar sus propias solicitudes.']);
            }
            if (strtolower($solicitud->estado) !== ElementExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages(['solicitud' => 'Solo se pueden cancelar solicitudes pendientes.']);
            }

            $solicitud->update(['estado' => ElementExtensionRequest::ESTADO_CANCELADA]);

            return $solicitud->load(self::WITH_BASE);
        });
    }

    /**
     * Obtener asignaciones de elemento próximas a vencer del usuario (ventana 7 días).
     */
    public function getUpcomingElements(int $userId)
    {
        $limitDate = Carbon::now()->addDays(7);
        return ElementAssignment::with(['element', 'process'])
            ->where('usuario_id', $userId)
            ->whereIn('estado', ['Pendiente', 'En Progreso'])
            ->where('fecha_limite', '<=', $limitDate)
            ->orderBy('fecha_limite', 'asc')
            ->get();
    }
}
