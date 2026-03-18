<?php

namespace App\Services;

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Servicio del profesor para solicitudes de ampliacion de plazo (RF-15).
 */
class ExtensionTimeRequestService
{
    private const WITH_BASE = ['evidenceAssignment.evidence.criterion', 'user', 'resolutor'];

    /**
     * Listar solicitudes con paginacion y filtros.
     */
    public function listRequests(int $perPage = 15, array $filters = [])
    {
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
     * Obtener una solicitud especifica por ID y usuario.
     */
    public function getRequest(int $requestId, int $userId): ?ExtensionRequest
    {
        $solicitud = ExtensionRequest::with(self::WITH_BASE)->find($requestId);
        if (!$solicitud || $solicitud->usuario_id !== $userId) {
            return null;
        }
        return $solicitud;
    }

    /**
     * Obtener evidencias proximas a vencer para un usuario.
     */
    public function getUpcomingEvidences(int $userId)
    {
        $limitDate = Carbon::now()->addDays(7);
        return EvidenceAssignment::with(['evidence.criterion', 'process'])
            ->where('usuario_id', $userId)
            ->whereIn('estado', ['Pendiente', 'En Progreso'])
            ->where('fecha_limite', '<=', $limitDate)
            ->orderBy('fecha_limite', 'asc')
            ->get();
    }

    /**
     * Crear una nueva solicitud de ampliacion con validaciones de negocio.
     */
    public function createRequest(array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Verificar que la asignacion existe
            $assignment = EvidenceAssignment::find($data['evidencia_asignacion_id']);

            if (!$assignment) {
                throw ValidationException::withMessages([
                    'evidencia_asignacion_id' => 'La asignacion de evidencia no existe o no esta activa.',
                ]);
            }

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
            $tienePendiente = ExtensionRequest::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();
            if ($tienePendiente) {
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

            // 8. Crear la solicitud
            return ExtensionRequest::create([
                'evidencia_asignacion_id' => $data['evidencia_asignacion_id'],
                'usuario_id'              => $userId,
                'motivo'                  => $data['motivo'],
                'fecha_sugerida'          => $data['fecha_sugerida'],
                'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
            ]);
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

            if (isset($data['fecha_sugerida']) && $solicitud->evidenceAssignment) {
                $originalDeadline = Carbon::parse($solicitud->evidenceAssignment->fecha_limite);
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

            $solicitud->update([
                'motivo'         => $data['motivo']         ?? $solicitud->motivo,
                'fecha_sugerida' => $data['fecha_sugerida'] ?? $solicitud->fecha_sugerida,
            ]);

            return $solicitud->refresh();
        });
    }

    /**
     * Eliminar una solicitud pendiente del profesor.
     */
    public function deleteRequest(int $requestId, int $userId): bool
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $solicitud = ExtensionRequest::find($requestId);

            if (!$solicitud) {
                throw ValidationException::withMessages(['solicitud' => 'La solicitud no existe.']);
            }
            if ($solicitud->usuario_id !== $userId) {
                throw ValidationException::withMessages(['solicitud' => 'Solo puede eliminar sus propias solicitudes.']);
            }
            if ($solicitud->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'solicitud' => 'Solo se pueden eliminar solicitudes pendientes.',
                ]);
            }

            $solicitud->delete();
            return true;
        });
    }
}