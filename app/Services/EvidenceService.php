<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class EvidenceService
{
    private const CACHE_KEY = 'evidencias.all';
    private const CACHE_TTL = 300;

    /** Relaciones eager-loaded en la mayoría de queries */
    private const WITH_BASE = ['criterion.component.dimension', 'evidenceState'];

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
            'criterio_id'        => $data['criterio_id'],
            'estado_evidencia_id' => $data['estado_evidencia_id'],
            'descripcion'        => $data['descripcion'],
            'nomenclatura'       => $data['nomenclatura'],
            'activo'             => $data['activo'] ?? true,
        ]);
        Cache::forget(self::CACHE_KEY);
        return $evidence->load(self::WITH_BASE);
    }

    public function update(Evidence $evidence, array $data): Evidence
    {
        $evidence->update([
            'criterio_id'        => $data['criterio_id']        ?? $evidence->criterio_id,
            'estado_evidencia_id' => $data['estado_evidencia_id'] ?? $evidence->estado_evidencia_id,
            'descripcion'        => $data['descripcion']        ?? $evidence->descripcion,
            'nomenclatura'       => $data['nomenclatura']       ?? $evidence->nomenclatura,
            'activo'             => $data['activo']             ?? $evidence->activo,
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
     * Filtrar evidencias con paginación (HU-012).
     * Usa Eloquent puro para ambos roles — elimina los SPs de filtrado y conteo.
     */
    public function filterEvidences(array $filters, User $user): LengthAwarePaginator
    {
        $perPage    = (int) ($filters['per_page'] ?? 15);
        $page       = (int) ($filters['page']     ?? 1);
        $criterioId = $filters['criterio_id']         ?? null;
        $estadoId   = $filters['estado_evidencia_id'] ?? null;
        $fechaDesde = $filters['fecha_desde']         ?? null;
        $fechaHasta = $filters['fecha_hasta']         ?? null;
        $sortBy     = $filters['sort_by']             ?? 'created_at';
        $sortOrder  = in_array(strtolower($filters['sort_order'] ?? ''), ['asc', 'desc'])
                        ? strtolower($filters['sort_order'])
                        : 'desc';

        // Mapa seguro frontend => columna BD (evita SQL injection aunque sea Eloquent)
        $sortColumn = match ($sortBy) {
            'fecha', 'fecha_publicacion' => 'created_at',
            'nomenclatura'               => 'nomenclatura',
            'descripcion'                => 'descripcion',
            'estado'                     => 'estado_evidencia_id',
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
        if ($estadoId) {
            $query->where('estado_evidencia_id', $estadoId);
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