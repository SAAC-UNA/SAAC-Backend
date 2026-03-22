<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\User;
use App\Models\Comment;
use App\Notifications\EvidenciaRetroalimentada;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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
            'criterio_id' => $data['criterio_id'],
            'estado'      => $data['estado'] ?? 'pendiente',
            'descripcion' => $data['descripcion'],
            'nomenclatura' => $data['nomenclatura'],
            'activo'      => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $evidence->load(self::WITH_BASE);
    }

    public function update(Evidence $evidence, array $data): Evidence
    {
        $evidence->update([
            'criterio_id'  => $data['criterio_id']  ?? $evidence->criterio_id,
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
        $estadosNoRevisables = ['pendiente'];
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
        $evidenceActualizada->loadMissing('activeAssignments.user');
        $responsables = $evidenceActualizada->activeAssignments
            ->map(fn($a) => $a->user)
            ->filter(); // descarta asignaciones sin usuario

        if ($responsables->isNotEmpty()) {
            Notification::send(
                $responsables,
                new EvidenciaRetroalimentada($evidenceActualizada, $reviewer, $data['comentario'], $data['estado'])
            );
        }

        return $evidenceActualizada;
    }

    /**
     * Filtrar evidencias con paginación (HU-012).
     * Usa Eloquent puro para ambos roles — elimina los SPs de filtrado y conteo.
     */
    public function filterEvidences(array $filters, User $user): LengthAwarePaginator
    {
        $perPage    = (int) ($filters['per_page'] ?? 15);
        $page       = (int) ($filters['page']     ?? 1);
        $criterioId = $filters['criterio_id'] ?? null;
        $estado     = $filters['estado']      ?? null;
        $fechaDesde = $filters['fecha_desde'] ?? null;
        $fechaHasta = $filters['fecha_hasta'] ?? null;
        $sortBy     = $filters['sort_by']     ?? 'created_at';
        $sortOrder  = in_array(strtolower($filters['sort_order'] ?? ''), ['asc', 'desc'])
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

        $query = Evidence::with([...self::WITH_BASE, 'assignments.user'])
            ->withCount([
                'files as archivos_count' => fn ($q) => $q->where('tipo', 'archivo'),
                'files as enlaces_count'  => fn ($q) => $q->where('tipo', 'enlace'),
            ]);

        // Restricción por rol: Profesor/Evaluador solo ven sus asignadas
        if (!$user->hasRole('Superusuario') && $user->hasRole(['Profesor', 'Evaluador'])) {
            $query->withoutGlobalScope('byCareerCampus')
                  ->whereHas('assignments', fn ($q) =>
                      $q->where('usuario_id', $user->usuario_id)
                        ->whereIn('estado', ['Pendiente', 'En Progreso'])
                  );
        }

        if ($criterioId) {
            $query->where('criterio_id', $criterioId);
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

        return $query->orderBy($sortColumn, $sortOrder)->paginate($perPage, ['*'], 'page', $page);
    }
}