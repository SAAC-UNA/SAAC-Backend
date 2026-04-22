<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de filtrado de evidencias del modelo tradicional (HU-012).
 *
 * En Arquitectura B, EVIDENCIA pertenece exclusivamente al modelo tradicional:
 *   PROCESO → CRITERIO → EVIDENCIA → EVIDENCIA_ASIGNACION → ARCHIVO
 *
 * El modelo flexible (ELEMENTO → ELEMENTO_ASIGNACION → ARCHIVO) tiene su
 * propio servicio: ElementAssignmentService.
 */
class TradicionalEvidenceFilterService
{
    private const WITH_BASE = ['criterion.component.dimension', 'assignments.user.roles', 'files'];
    private const CACHE_TTL = 60;

    /**
     * Filtrar evidencias tradicionales con paginación.
     *
     * @param  array  $filters  Parámetros validados por FilterEvidenceRequest
     * @param  User   $user     Usuario autenticado (para restricción por rol)
     * @return LengthAwarePaginator
     */
    public function filter(array $filters, User $user): LengthAwarePaginator
    {
        $cacheKey = 'tradicional-evidences.filter:' . md5(json_encode([
            'filters' => $filters,
            'user' => $user->usuario_id,
            'roles' => $user->roles()->pluck('name')->sort()->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters, $user) {
            $perPage            = (int) ($filters['per_page'] ?? 15);
            $page               = (int) ($filters['page']     ?? 1);
            $criterioId         = $filters['criterio_id']           ?? null;
            $componenteId       = $filters['componente_id']         ?? null;
            $dimensionId        = $filters['dimension_id']          ?? null;
            $estandarId         = $filters['estandar_id']           ?? null;
            $cicloId            = $filters['ciclo_acreditacion_id'] ?? null;
            $procesoId          = $filters['proceso_id']            ?? null;
            $modeloEstructuraId = $filters['modelo_estructura_id']  ?? null;
            $estado             = $filters['estado']                ?? null;
            $estadoId           = $filters['estado_evidencia_id']   ?? null;
            $fechaDesde         = $filters['fecha_desde']           ?? null;
            $fechaHasta         = $filters['fecha_hasta']           ?? null;
            $rolId              = $filters['rol_id']                ?? null;
            $responsableId      = $filters['responsable_id']        ?? null;

            $sortBy    = $filters['sort_by'] ?? 'created_at';
            $sortOrder = in_array(strtolower($filters['sort_order'] ?? ''), ['asc', 'desc'])
                ? strtolower($filters['sort_order'])
                : 'desc';

        // Mapa seguro frontend → columna BD
            $sortColumn = match ($sortBy) {
                'fecha', 'fecha_publicacion' => 'created_at',
                'nomenclatura'               => 'nomenclatura',
                'descripcion'                => 'descripcion',
                'estado'                     => 'estado',
                default                      => 'created_at',
            };

            $query = Evidence::with([
                    ...self::WITH_BASE,
                    'criterion.standards',
                    'assignments.user.roles',
                    'latestFile',
                ])
                ->withCount([
                    'files as archivos_count' => fn ($q) => $q->where('tipo', 'archivo'),
                    'files as enlaces_count'  => fn ($q) => $q->where('tipo', 'enlace'),
                ]);

            // Restricción por rol: Profesor/Evaluador solo ven sus evidencias asignadas
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
            if ($procesoId) {
                $query->whereHas('assignments', fn ($q) =>
                    $q->where('proceso_id', $procesoId)
                );
            }
            if ($modeloEstructuraId) {
                $query->whereHas('assignments.process.accreditationCycle', fn ($q) =>
                    $q->where('modelo_estructura_id', $modeloEstructuraId)
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
            if ($responsableId) {
                $query->whereHas('assignments', fn ($q) => $q->where('usuario_id', $responsableId));
            }

            return $query->orderBy($sortColumn, $sortOrder)->paginate($perPage, ['*'], 'page', $page);
        });
    }
}
