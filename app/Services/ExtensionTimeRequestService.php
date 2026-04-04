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
    private const WITH_BASE = [
        'evidenceAssignment.evidence.criterion',
        'user',
        'resolutor',
    ];

    /**
     * Listar solicitudes con paginacion y filtros.
     */
    public function listRequests(int $perPage = 15, array $filters = [])
    {
        $estado       = $filters['estado'] ?? null;
        $usuarioId    = $filters['usuario_id'] ?? null;
        $asignacionId = $filters['evidencia_asignacion_id'] ?? null;
        $fechaDesde   = $filters['fecha_desde'] ?? null;
        $fechaHasta   = $filters['fecha_hasta'] ?? null;

        return ExtensionRequest::with(self::WITH_BASE)
            ->whereNotNull('evidencia_asignacion_id')
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
     * Obtener evidencias próximas a vencer para un usuario (modelo tradicional).
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
     * Crear una nueva solicitud de ampliación con validaciones de negocio.
     * Soporta tanto asignaciones de evidencia (modelo tradicional) como de elemento (modelo flexible).
     */
    public function createRequest(array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            $asignacionKey = 'evidencia_asignacion_id';
            $asignacionId  = $data[$asignacionKey];

            // 1. Verificar que la asignación existe
            $assignment = EvidenceAssignment::find($asignacionId);

            if (!$assignment) {
                throw ValidationException::withMessages([
                    $asignacionKey => 'La asignación no existe o no está activa.',
                ]);
            }

            // 2. Verificar que el usuario es el asignado
            if ($assignment->usuario_id !== $userId) {
                throw ValidationException::withMessages([
                    $asignacionKey => 'Solo puede solicitar ampliación para asignaciones propias.',
                ]);
            }

            // 3. Verificar que la asignación no esté aprobada/validada
            if (in_array($assignment->estado, ['Aprobada', 'aprobada', 'Validada'])) {
                throw ValidationException::withMessages([
                    $asignacionKey => 'No se puede solicitar ampliación para asignaciones ya aprobadas.',
                ]);
            }

            // 4. Validar que el plazo no haya vencido
            $fechaLimite = Carbon::parse($assignment->fecha_limite);
            if ($fechaLimite->lt(Carbon::now())) {
                throw ValidationException::withMessages([
                    $asignacionKey => 'No se puede solicitar ampliación para asignaciones con plazo ya vencido.',
                ]);
            }

            // 5. Verificar que no tenga solicitud pendiente para esta asignación
            $tienePendiente = ExtensionRequest::where($asignacionKey, $asignacionId)
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();
            if ($tienePendiente) {
                throw ValidationException::withMessages([
                    $asignacionKey => 'Ya tiene una solicitud pendiente para esta asignación.',
                ]);
            }

            // 6. Validar que la fecha sugerida sea posterior a la fecha límite
            $suggestedDate    = Carbon::parse($data['fecha_sugerida']);
            $originalDeadline = $fechaLimite;

            if ($suggestedDate->lte($originalDeadline)) {
                throw ValidationException::withMessages([
                    'fecha_sugerida' => 'La fecha sugerida debe ser posterior a la fecha límite original (' . $originalDeadline->format('d/m/Y H:i') . ').',
                ]);
            }

            // 7. Validar que la ampliación sea de máximo 30 días
            $extensionDays = $originalDeadline->diffInDays($suggestedDate);
            if ($extensionDays > 30) {
                throw ValidationException::withMessages([
                    'fecha_sugerida' => 'La ampliación solicitada no puede exceder 30 días desde la fecha límite original.',
                ]);
            }

            // 8. Crear la solicitud
            $nueva = ExtensionRequest::create([
                $asignacionKey   => $asignacionId,
                'usuario_id'     => $userId,
                'motivo'         => $data['motivo'],
                'fecha_sugerida' => $data['fecha_sugerida'],
                'estado'         => ExtensionRequest::ESTADO_PENDIENTE,
            ]);
            return $nueva->load(self::WITH_BASE);
        });
    }

    public function cancelRequest(int $requestId, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $solicitud = ExtensionRequest::find($requestId);

            if (!$solicitud) {
                throw ValidationException::withMessages(['solicitud' => 'La solicitud no existe.']);
            }
            if ($solicitud->usuario_id !== $userId) {
                throw ValidationException::withMessages(['solicitud' => 'Solo puede cancelar sus propias solicitudes.']);
            }
            if (strtolower($solicitud->estado) !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'solicitud' => 'Solo se pueden cancelar solicitudes pendientes.',
                ]);
            }

            $solicitud->update(['estado' => ExtensionRequest::ESTADO_CANCELADA]);
            $solicitud->refresh();

            return $solicitud->load(self::WITH_BASE);
        });
    }

}