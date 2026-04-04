<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Servicio CRUD para evidencias del modelo tradicional (Arquitectura B).
 *
 * Flujo tradicional:  PROCESO → CRITERIO → EVIDENCIA → EVIDENCIA_ASIGNACION → ARCHIVO
 * Flujo flexible:     PROCESO → ELEMENTO → ELEMENTO_ASIGNACION → ARCHIVO  (ElementAssignmentService)
 *
 * Este servicio reemplaza a EvidenceService y gestiona únicamente
 * evidencias ligadas a un criterio (criterio_id NOT NULL).
 */
class TradicionalEvidenceService
{
    private const CACHE_KEY = 'evidencias.tradicional.all';
    private const CACHE_TTL = 300;

    /** Relaciones eager-loaded en la mayoría de queries */
    private const WITH_BASE = ['criterion.component.dimension'];

    // =========================================================================
    // CONSULTA
    // =========================================================================

    public function getAll()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
            Evidence::with(self::WITH_BASE)->orderBy('nomenclatura')->get()
        );
    }

    public function findById(int $id): ?Evidence
    {
        return Evidence::with([...self::WITH_BASE, 'assignments.user', 'files'])->find($id);
    }

    // =========================================================================
    // ESCRITURA
    // =========================================================================

    public function create(array $data): Evidence
    {
        $evidence = Evidence::create([
            'criterio_id'  => $data['criterio_id'],
            'estado'       => $data['estado']       ?? 'Pendiente',
            'descripcion'  => $data['descripcion'],
            'nomenclatura' => $data['nomenclatura'],
            'activo'       => $data['activo']        ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $evidence->load(self::WITH_BASE);
    }

    public function update(Evidence $evidence, array $data): Evidence
    {
        $evidence->update([
            'criterio_id'  => array_key_exists('criterio_id', $data) ? $data['criterio_id'] : $evidence->criterio_id,
            'estado'       => $data['estado']       ?? $evidence->estado,
            'descripcion'  => $data['descripcion']  ?? $evidence->descripcion,
            'nomenclatura' => $data['nomenclatura'] ?? $evidence->nomenclatura,
            'activo'       => $data['activo']        ?? $evidence->activo,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $evidence->fresh(self::WITH_BASE);
    }

    public function delete(Evidence $evidence): void
    {
        $evidence->delete();
        Cache::forget(self::CACHE_KEY);
    }

    // =========================================================================
    // RETROALIMENTACIÓN (HU-013)
    // =========================================================================

    /**
     * El encargado de acreditación puede marcar la evidencia como 'Observada'
     * o 'Validada' y dejar un comentario. Las operaciones se ejecutan en una
     * transacción atómica:
     *   1. Actualiza el estado de la evidencia
     *   2. Guarda el comentario en COMENTARIO (relación polimórfica)
     *   3. Registra la acción en BITACORA vía AuditLogService
     *   4. Invalida el caché
     */
    public function retroalimentar(Evidence $evidence, array $data, User $reviewer): Evidence
    {
        $estadosNoRevisables = ['Pendiente'];
        if (in_array($evidence->estado, $estadosNoRevisables)) {
            throw new \InvalidArgumentException(
                "No se puede retroalimentar una evidencia en estado \"{$evidence->estado}\". " .
                "Debe estar en proceso, completada, aprobada, rechazada, observada, validada o vencida."
            );
        }

        $evidenceActualizada = DB::transaction(function () use ($evidence, $data, $reviewer) {
            $evidence->update(['estado' => $data['estado']]);

            Comment::create([
                'usuario_id'       => $reviewer->usuario_id,
                'commentable_type' => Evidence::class,
                'commentable_id'   => $evidence->evidencia_id,
                'texto'            => $data['comentario'],
            ]);

            AuditLogService::log(
                'retroalimentar',
                "Evidencia {$evidence->nomenclatura} (ID: {$evidence->evidencia_id}) marcada como \"{$data['estado']}\". Comentario: {$data['comentario']}",
                'Evidencias',
                $reviewer->usuario_id
            );

            Cache::forget(self::CACHE_KEY);

            return $evidence->fresh([...self::WITH_BASE, 'comments.user']);
        });

        // Notificación fuera de la transacción para aislar fallos de SMTP
        try {
            $evidenceActualizada->loadMissing('activeAssignments.user');
            $responsables = $evidenceActualizada->activeAssignments
                ->map(fn ($assignment) => $assignment->user)
                ->filter();

            if ($responsables->isNotEmpty()) {
                NotificationService::createMany(
                    $responsables->pluck('usuario_id')->toArray(),
                    [
                        'tipo_evento'  => $data['estado'] === 'Observada'
                            ? \App\Models\Notification::TIPO_DEVOLUCION_OBSERVACION
                            : \App\Models\Notification::TIPO_APROBACION_EVIDENCIA,
                        'titulo'       => "Evidencia {$evidenceActualizada->nomenclatura} — " . strtoupper($data['estado']),
                        'mensaje'      => "El evaluador {$reviewer->nombre} marcó la evidencia como \"{$data['estado']}\". Comentario: {$data['comentario']}",
                        'relacionado'  => $evidenceActualizada,
                        'enlace'       => "/evidencias/{$evidenceActualizada->evidencia_id}",
                        'forzar_email' => true,
                    ]
                );
            }
        } catch (\Throwable $e) {
            logger()->error('EvidenciaRetroalimentada notification failed', [
                'evidencia_id' => $evidenceActualizada->evidencia_id,
                'error'        => $e->getMessage(),
            ]);
        }

        return $evidenceActualizada;
    }

    // =========================================================================
    // ESTADO AUTOMÁTICO
    // =========================================================================

    /**
     * Recalcula el estado de la evidencia a partir del estado de sus asignaciones.
     * Lo llama EvidenceAssignmentObserver en created/updated/deleted.
     *
     * Reglas (por prioridad):
     *  1. Sin asignaciones                              → 'Pendiente'
     *  2. Todas 'Completado'                            → 'Completado'
     *  3. Alguna 'Vencido' (sin todas completadas)      → 'Vencido'
     *  4. Alguna 'En Progreso' o 'Completado' (mezcla)  → 'En Proceso'
     *  5. Todas 'Pendiente'                             → 'Pendiente'
     *
     * No sobreescribe estados de retroalimentación (Observada, Validada,
     * Aprobado, Rechazado).
     */
    public function recalcularEstadoEvidencia(int $evidenciaId): void
    {
        $evidence = Evidence::find($evidenciaId);
        if (!$evidence) return;

        if (in_array($evidence->estado, ['Observada', 'Validada', 'Aprobado', 'Rechazado'])) {
            return;
        }

        $asignaciones = EvidenceAssignment::where('evidencia_id', $evidenciaId)->get();
        $total        = $asignaciones->count();

        if ($total === 0) {
            $nuevoEstado = 'Pendiente';
        } else {
            $estados = $asignaciones->pluck('estado')
                ->map(fn ($estado) => EvidenceAssignment::apiStatusFromDb($estado));

            if ($estados->every(fn ($e) => $e === 'completado')) {
                $nuevoEstado = 'Completado';
            } elseif ($estados->contains('vencido')) {
                $nuevoEstado = 'Vencido';
            } elseif ($estados->contains(fn ($e) => in_array($e, ['en_progreso', 'completado']))) {
                $nuevoEstado = 'En Proceso';
            } else {
                $nuevoEstado = 'Pendiente';
            }
        }

        if ($evidence->estado !== $nuevoEstado) {
            $evidence->update(['estado' => $nuevoEstado]);
            Cache::forget(self::CACHE_KEY);
        }
    }
}
