<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EvidenceService
{
    public function getAll()
    {
        return Cache::remember('evidencias.all', 300, function () {
            $rows = DB::select('CALL SP_OBTENER_EVIDENCIAS()');
            return Evidence::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    public function findById(int $id): ?Evidence
    {
        $rows = DB::select('CALL SP_BUSCAR_EVIDENCIA(?)', [$id]);
        return $rows ? Evidence::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    public function create(array $data): Evidence
    {
        $rows = DB::select('CALL SP_CREAR_EVIDENCIA(?, ?, ?, ?, ?)', [
            $data['criterio_id'],
            $data['estado_evidencia_id'],
            $data['descripcion'],
            $data['nomenclatura'],
            $data['activo'] ?? 1,
        ]);
        Cache::forget('evidencias.all');
        return Evidence::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function update(Evidence $evidence, array $data): Evidence
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_EVIDENCIA(?, ?, ?, ?, ?, ?)', [
            $evidence->evidencia_id,
            $data['criterio_id'] ?? $evidence->criterio_id,
            $data['estado_evidencia_id'] ?? $evidence->estado_evidencia_id,
            $data['descripcion'] ?? $evidence->descripcion,
            $data['nomenclatura'] ?? $evidence->nomenclatura,
            $data['activo'] ?? $evidence->activo,
        ]);
        Cache::forget('evidencias.all');
        return Evidence::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    public function delete(Evidence $evidence): void
    {
        DB::statement('CALL SP_ELIMINAR_EVIDENCIA(?)', [$evidence->evidencia_id]);
        Cache::forget('evidencias.all');
    }

    /**
     * Filtrar evidencias con paginacion (HU-012).
     * Usa SP_FILTRAR_EVIDENCIAS + SP_CONTAR_FILTRO_EVIDENCIAS.
     * Nota: la restriccion por rol Profesor se resuelve a nivel de SP cuando
     * el SP soporte parametro de usuario; por ahora se filtra post-query.
     */
    public function filterEvidences(array $filters, User $user): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page    = $filters['page'] ?? 1;
        $offset  = ($page - 1) * $perPage;

        $criterioId       = $filters['criterio_id'] ?? null;
        $estadoId         = $filters['estado_evidencia_id'] ?? null;
        $fechaDesde       = $filters['fecha_desde'] ?? null;
        $fechaHasta       = $filters['fecha_hasta'] ?? null;
        $sortBy           = $filters['sort_by'] ?? 'created_at';
        $sortOrder        = $filters['sort_order'] ?? 'desc';

        // Mapear nombre de columna frontend => columna BD
        $sortColumn = match ($sortBy) {
            'fecha', 'fecha_publicacion' => 'created_at',
            'nomenclatura'               => 'nomenclatura',
            'descripcion'                => 'descripcion',
            'estado'                     => 'estado_evidencia_id',
            default                      => 'created_at',
        };

        // Para Profesor: obtener IDs asignados y filtrar
        if (!$user->hasRole('Superusuario') && $user->hasRole(['Profesor', 'Evaluador'])) {
            // Obtener evidencias proximas/asignadas al usuario
            $assigned = DB::select('CALL SP_OBTENER_EVIDENCIAS_PROXIMAS(?, ?)', [
                $user->usuario_id,
                now()->addYears(10)->format('Y-m-d H:i:s'),
            ]);
            $assignedEvidenciaIds = array_unique(array_column($assigned, 'evidencia_id'));

            if (empty($assignedEvidenciaIds)) {
                return new LengthAwarePaginator(collect(), 0, $perPage, $page);
            }

            // Filtrar con Eloquent para respetar lista de IDs asignados
            $query = Evidence::withoutGlobalScope('byCareerCampus')
                ->with('criterion', 'evidenceState', 'assignments.user')
                ->withCount([
                    'files as archivos_count' => fn($q) => $q->where('tipo', 'archivo'),
                    'files as enlaces_count'  => fn($q) => $q->where('tipo', 'enlace'),
                ])
                ->whereIn('evidencia_id', $assignedEvidenciaIds);

            if ($criterioId) $query->where('criterio_id', $criterioId);
            if ($estadoId)   $query->where('estado_evidencia_id', $estadoId);
            if ($fechaDesde) $query->where('created_at', '>=', $fechaDesde . ' 00:00:00');
            if ($fechaHasta) $query->where('created_at', '<=', $fechaHasta . ' 23:59:59');

            $query->orderBy($sortColumn, $sortOrder);
            return $query->paginate($perPage);
        }

        // Para Superusuario / Coordinador / Administrador: usar SPs
        $total = DB::select('CALL SP_CONTAR_FILTRO_EVIDENCIAS(?, ?, ?, ?)', [
            $criterioId,
            $estadoId,
            $fechaDesde,
            $fechaHasta,
        ])[0]->total ?? 0;

        $rows = DB::select('CALL SP_FILTRAR_EVIDENCIAS(?, ?, ?, ?, ?, ?, ?, ?)', [
            $criterioId,
            $estadoId,
            $fechaDesde,
            $fechaHasta,
            $sortColumn,
            $sortOrder,
            $offset,
            $perPage,
        ]);

        $items = Evidence::hydrate(array_map(fn($r) => (array) $r, $rows));
        $items->load('assignments.user');

        return new LengthAwarePaginator($items, (int) $total, $perPage, $page, [
            'path' => request()->url(),
        ]);
    }
}