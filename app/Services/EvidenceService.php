<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\User;
use App\Models\Comment;
use App\Models\EvidenceAssignment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EvidenceService
{
    private const CACHE_KEY = 'evidencias.all';
    private const CACHE_TTL = 300;

    /** Relaciones eager-loaded en la mayoría de queries */
    private const WITH_BASE = ['criterion.component.dimension'];

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

    public function create(array $data): Evidence
    {
        $evidence = Evidence::create([
            'criterio_id'  => $data['criterio_id'],
            'estado'       => $data['estado']       ?? 'Pendiente',
            'descripcion'  => $data['descripcion'],
            'nomenclatura' => $data['nomenclatura'],
            'activo'       => $data['activo']       ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $evidence->load(self::WITH_BASE);
    }

    public function update(Evidence $evidence, array $data): Evidence
    {
        $evidence->update([
            'criterio_id'  => array_key_exists('criterio_id', $data)  ? $data['criterio_id']  : $evidence->criterio_id,
            'estado'       => $data['estado']       ?? $evidence->estado,
            'descripcion'  => $data['descripcion']  ?? $evidence->descripcion,
            'nomenclatura' => $data['nomenclatura'] ?? $evidence->nomenclatura,
            'activo'       => $data['activo']       ?? $evidence->activo,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $evidence->fresh(self::WITH_BASE);
    }

    public function delete(Evidence $evidence): void
    {
        $evidence->delete();
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Retroalimentar una evidencia (HU-013).
     *
     * El encargado de acreditación puede marcarla como 'observada' o 'validada'
     * y dejar un comentario. Las 4 operaciones se ejecutan en una transacción
     * atómica para garantizar consistencia:
     *   1. Actualiza el estado de la evidencia
     *   2. Guarda el comentario en COMENTARIO (relación polimórfica)
     *   3. Registra la acción en BITACORA vía AuditLogService
     *   4. Invalida el caché de lista de evidencias
     *
     * @param  Evidence  $evidence  Evidencia a retroalimentar
     * @param  array     $data      ['estado' => ..., 'comentario' => ...]
     * @param  User      $reviewer  Usuario que realiza la acción
     * @return Evidence  La evidencia actualizada con sus relaciones base y comentarios
     */
    public function retroalimentar(Evidence $evidence, array $data, User $reviewer): Evidence
    {
        // Solo se puede retroalimentar si la evidencia tiene un estado revisable.
        // 'pendiente' significa que aún no fue enviada — no hay nada que revisar.
        // 'vencido' NO se bloquea: existe una HU de ampliación de plazo que permite
        //  gestionar evidencias vencidas, por lo que el evaluador sí puede retroalimentarlas.
        $estadosNoRevisables = ['Pendiente'];
        if (in_array($evidence->estado, $estadosNoRevisables)) {
            throw new \InvalidArgumentException(
                "No se puede retroalimentar una evidencia en estado \"{$evidence->estado}\". ".
                "Debe estar en proceso, completada, aprobada, rechazada, observada, validada o vencida."
            );
        }

        $evidenceActualizada = DB::transaction(function () use ($evidence, $data, $reviewer) {
            // 1. Cambia el estado ('observada' o 'validada')
            $evidence->update(['estado' => $data['estado']]);

            // 2. Guarda el comentario polimórfico enlazado a esta evidencia
            Comment::create([
                'usuario_id'       => $reviewer->usuario_id,
                'commentable_type' => Evidence::class,
                'commentable_id'   => $evidence->evidencia_id,
                'texto'            => $data['comentario'],
            ]);

            // 3. Registra en bitácora con el tipo de acción 'retroalimentar'
            AuditLogService::log(
                'retroalimentar',
                "Evidencia {$evidence->nomenclatura} (ID: {$evidence->evidencia_id}) marcada como \"{$data['estado']}\". Comentario: {$data['comentario']}",
                'Evidencias',
                $reviewer->usuario_id
            );

            // 4. Invalida el caché para que la lista se regenere con el nuevo estado
            Cache::forget(self::CACHE_KEY);

            // Retorna la evidencia fresca junto con sus relaciones base y sus comentarios
            return $evidence->fresh([...self::WITH_BASE, 'comments.user']);
        });

        // 5. Notificación automática — FUERA de la transacción para que un fallo
        //    de email no revierta el cambio de estado ni el comentario ya guardado.
        //    Se notifica a todos los profesores con asignación activa en esta evidencia.
        //    El try/catch aísla cualquier fallo de SMTP o de carga de relaciones — el
        //    endpoint ya respondió con 200; el error queda solo en el log.
        try {
            $evidenceActualizada->loadMissing('activeAssignments.user');
            $responsables = $evidenceActualizada->activeAssignments
                ->map(fn($assignment) => $assignment->user)
                ->filter(); // descarta asignaciones sin usuario

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
            // Loguear el fallo sin interrumpir la respuesta al cliente
            logger()->error('EvidenciaRetroalimentada notification failed', [
                'evidencia_id' => $evidenceActualizada->evidencia_id,
                'error'        => $e->getMessage(),
            ]);
        }

        return $evidenceActualizada;
    }

    /**
     * Filtrar evidencias con paginación (HU-012).
     * Usa Eloquent puro para ambos roles — elimina los SPs de filtrado y conteo.
     */
    public function filterEvidences(array $filters, User $user): LengthAwarePaginator
    {
        $perPage            = (int) ($filters['per_page'] ?? 15);
        $page               = (int) ($filters['page']     ?? 1);
        $criterioId         = $filters['criterio_id']            ?? null;
        $componenteId       = $filters['componente_id']          ?? null;
        $dimensionId        = $filters['dimension_id']           ?? null;
        $estandarId         = $filters['estandar_id']            ?? null;
        // HU-012 (modelo flexible): filtros contextuales por ciclo y modelo.
        $cicloId            = $filters['ciclo_acreditacion_id']  ?? null;
        $modeloEstructuraId = $filters['modelo_estructura_id']   ?? null;
        $procesoId          = $filters['proceso_id']             ?? null;
        $estado             = $filters['estado']                 ?? null;
        $estadoId           = $filters['estado_evidencia_id']    ?? null;
        $fechaDesde         = $filters['fecha_desde']            ?? null;
        $fechaHasta         = $filters['fecha_hasta']            ?? null;
        $sortBy             = $filters['sort_by']                ?? 'created_at';
        $sortOrder          = in_array(strtolower($filters['sort_order'] ?? ''), ['asc', 'desc'])
                        ? strtolower($filters['sort_order'])
                        : 'desc';

        // Mapa seguro frontend => columna BD (evita SQL injection aunque sea Eloquent)
        $sortColumn = match ($sortBy) {
            'fecha', 'fecha_publicacion' => 'created_at',
            'nomenclatura'               => 'nomenclatura',
            'descripcion'                => 'descripcion',
            'estado'                     => 'estado',
            default                      => 'created_at',
        };

        $rolId = $filters['rol_id'] ?? null;

        $query = Evidence::with([...self::WITH_BASE, 'criterion.standards', 'assignments.user.roles', 'latestFile'])
            ->withCount([
                'files as archivos_count' => fn ($q) => $q->where('tipo', 'archivo'),
                'files as enlaces_count'  => fn ($q) => $q->where('tipo', 'enlace'),
            ]);

        // Restricción por rol: Profesor/Evaluador solo ven sus asignadas
        if (!$user->hasRole('Superusuario') && $user->hasRole(['Profesor', 'Evaluador'])) {
            $query->withoutGlobalScope('byCareerCampus')
                  ->whereHas('assignments', fn ($q) =>
                      $q->where('usuario_id', $user->usuario_id)
                        ->whereIn('estado', [
                            EvidenceAssignment::ESTADO_PENDIENTE,
                            EvidenceAssignment::ESTADO_EN_PROGRESO,
                        ])
                  );
        }

        if ($criterioId) {
            $query->where('criterio_id', $criterioId);
        }
        if ($componenteId) {
            $query->whereHas('criterion.component', fn ($q) =>
                $q->where('componente_id', $componenteId)
            );
        }
        if ($dimensionId) {
            $query->whereHas('criterion.component.dimension', fn ($q) =>
                $q->where('dimension_id', $dimensionId)
            );
        }
        if ($estandarId) {
            $query->whereHas('criterion.standards', fn ($q) =>
                $q->where('estandar_id', $estandarId)
            );
        }
        if ($cicloId) {
            $query->whereHas('assignments.process', fn ($q) =>
                $q->where('ciclo_acreditacion_id', $cicloId)
            );
        }
        if ($modeloEstructuraId) {
            $query->whereHas('assignments.process.accreditationCycle', fn ($q) =>
                $q->where('modelo_estructura_id', $modeloEstructuraId)
            );
        }
        if ($procesoId) {
            $query->whereHas('assignments', fn ($q) =>
                $q->where('proceso_id', $procesoId)
            );
        }
        if ($estadoId) {
            $query->where('estado_evidencia_id', $estadoId);
        }
        if ($estado) {
            $query->where('estado', $estado);
        }
        if ($fechaDesde) {
            $query->where('created_at', '>=', $fechaDesde . ' 00:00:00');
        }
        if ($fechaHasta) {
            $query->where('created_at', '<=', $fechaHasta . ' 23:59:59');
        }
        if ($rolId) {
            $query->whereHas('assignments.user.roles', fn ($q) => $q->where('id', $rolId));
        }

        return $query->orderBy($sortColumn, $sortOrder)->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Recalcula el estado de una evidencia a partir del estado de sus asignaciones.
     *
        * Nota: EVIDENCIA_ASIGNACION.estado se persiste en Title Case
        * ('Pendiente', 'En Progreso', 'Completado', 'Vencido').
     *
     * Reglas (por orden de prioridad):
     *  1. Sin asignaciones                                           → 'Pendiente'
        *  2. Todas las asignaciones 'Completado'                        → 'Completado'
        *  3. Alguna asignación 'Vencido' (sin todas completadas)        → 'Vencido'
        *  4. Alguna 'En Progreso' o 'Completado' (mezcla, sin 2/3)     → 'En Proceso'
        *  5. Todas 'Pendiente'                                          → 'Pendiente'
     *
     * No sobreescribe estados de retroalimentación (Observada, Validada, Aprobado,
     * Rechazado) — esos los gestiona exclusivamente el encargado (HU-013).
     */
    public function recalcularEstadoEvidencia(int $evidenciaId): void
    {
        $evidence = Evidence::find($evidenciaId);
        if (!$evidence) return;

        // Respetar los estados de retroalimentación del encargado
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

            if ($estados->every(fn($e) => $e === 'completado')) {
                $nuevoEstado = 'Completado';
            } elseif ($estados->contains('vencido')) {
                $nuevoEstado = 'Vencido';
            } elseif ($estados->contains(fn($e) => in_array($e, ['en_progreso', 'completado']))) {
                $nuevoEstado = 'En Proceso';
            } else {
                $nuevoEstado = 'Pendiente';
            }
        }

        if ($evidence->estado !== $nuevoEstado) {
            // update() dispara EvidenceObserver::updated → CriterionService::recalcularEstado()
            $evidence->update(['estado' => $nuevoEstado]);
            Cache::forget(self::CACHE_KEY);
        }
    }
}