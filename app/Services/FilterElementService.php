<?php

namespace App\Services;

use App\Models\ElementAssignment;
use App\Models\StructureElement;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio de filtrado avanzado de elementos del modelo flexible.
 *
 * Flujo flexible: PROCESO → ELEMENTO → ELEMENTO_ASIGNACION → ARCHIVO
 *
 * Equivalente a TradicionalEvidenceFilterService pero para ELEMENTO.
 */
class FilterElementService
{
    /**
     * Filtrar elementos con paginación.
     *
     * @param  array  $filters  Parámetros validados por FilterElementRequest
     * @param  User   $user     Usuario autenticado (para restricción por rol)
     * @return LengthAwarePaginator
     */
    public function filter(array $filters, User $user): LengthAwarePaginator
    {
        $perPage            = (int) ($filters['per_page'] ?? 15);
        $page               = (int) ($filters['page']     ?? 1);
        $tipo               = $filters['tipo']                 ?? null;
        $categoria          = $filters['categoria']            ?? null;
        $estado             = $filters['estado']               ?? null;
        $activo             = array_key_exists('activo', $filters) ? $filters['activo'] : null;
        $modeloEstructuraId = $filters['modelo_estructura_id'] ?? null;
        $padreId            = $filters['padre_id']             ?? null;
        $fechaLimiteDesde   = $filters['fecha_limite_desde']   ?? null;
        $fechaLimiteHasta   = $filters['fecha_limite_hasta']   ?? null;
        $cicloId            = $filters['ciclo_acreditacion_id'] ?? null;
        $procesoId          = $filters['proceso_id']           ?? null;
        $rolId              = $filters['rol_id']               ?? null;
        $responsableId      = $filters['responsable_id']       ?? null;
        $elementoRaizId     = $filters['elemento_raiz_id']     ?? null;
        $busqueda           = $filters['busqueda']             ?? null;
        $requiresFile       = $filters['requiere_archivo']     ?? null;
        $missingFile        = $filters['falta_archivo']        ?? null;

        $sortBy    = $filters['sort_by'] ?? 'elemento_id';
        $sortOrder = in_array(strtolower($filters['sort_order'] ?? ''), ['asc', 'desc'])
            ? strtolower($filters['sort_order'])
            : 'asc';

        $sortColumn = match ($sortBy) {
            'nombre', 'descripcion' => 'descripcion',
            'nomenclatura'          => 'nomenclatura',
            'estado'                => 'estado',
            'fecha_limite'          => 'fecha_limite',
            'tipo'                  => 'tipo',
            default                 => 'elemento_id',
        };

        $query = StructureElement::with(['parent', 'assignments', 'files']);

        // Restricción por rol: Profesor/Evaluador solo ven elementos que tienen asignados
        if (!$user->hasRole('Superusuario') && $user->hasRole(['Profesor', 'Evaluador'])) {
            $query->whereHas('assignments', fn ($q) =>
                $q->where('usuario_id', $user->usuario_id)
                  ->whereIn('estado', [
                      ElementAssignment::ESTADO_PENDIENTE,
                      ElementAssignment::ESTADO_EN_PROGRESO,
                  ])
            );
        }

        if ($tipo !== null) {
            $query->where('tipo', $tipo);
        }
        if ($categoria !== null) {
            $query->where('categoria', $categoria);
        }
        if ($estado !== null) {
            $query->where('estado', $estado);
        }
        if ($activo !== null) {
            $query->where('activo', (bool) $activo);
        }
        if ($modeloEstructuraId !== null) {
            $query->where('modelo_estructura_id', $modeloEstructuraId);
        }
        if ($padreId !== null) {
            $query->where('padre_id', $padreId);
        }
        // Filtro jerárquico recursivo: devuelve el nodo raíz y TODOS sus descendientes
        // sin importar la profundidad del árbol (Dimensión → Pauta → Fuente → ...).
        if ($elementoRaizId !== null) {
            $descendantIds = $this->getAllDescendantIds((int) $elementoRaizId);
            $query->whereIn('elemento_id', $descendantIds);
        }
        if ($fechaLimiteDesde !== null) {
            $query->where('fecha_limite', '>=', $fechaLimiteDesde);
        }
        if ($fechaLimiteHasta !== null) {
            $query->where('fecha_limite', '<=', $fechaLimiteHasta);
        }
        if ($cicloId !== null) {
            $query->whereHas('assignments.process', fn ($q) =>
                $q->where('ciclo_acreditacion_id', $cicloId)
            );
        }
        if ($procesoId !== null) {
            $query->whereHas('assignments', fn ($q) =>
                $q->where('proceso_id', $procesoId)
            );
        }
        if ($rolId !== null) {
            $query->whereHas('assignments.user.roles', fn ($q) =>
                $q->where('id', $rolId)
            );
        }
        if ($responsableId !== null) {
            $query->whereHas('assignments', fn ($q) =>
                $q->where('usuario_id', $responsableId)
            );
        }
        if ($busqueda !== null) {
            // MATCH...AGAINST usa el índice FULLTEXT ft_elemento_busqueda
            // Modo BOOLEAN para soportar palabras parciales con wildcard: "plan*"
            $query->whereRaw(
                'MATCH(nomenclatura, descripcion) AGAINST (? IN BOOLEAN MODE)',
                [$busqueda . '*']
            );
        }

        // Filter: Elements that CAN have files (based on model configuration)
        if ($requiresFile && $requiresFile === true) {
            $query->whereHas('modeloEstructura', function ($subQuery) {
                $subQuery->whereRaw("JSON_CONTAINS(tipos_requieren_archivo, JSON_QUOTE(ELEMENTO.tipo))");
            });
        }

        // Filter: Elements that require files but don't have any uploaded
        if ($missingFile && $missingFile === true) {
            $query->whereHas('modeloEstructura', function ($subQuery) {
                $subQuery->whereRaw("JSON_CONTAINS(tipos_requieren_archivo, JSON_QUOTE(ELEMENTO.tipo))");
            })->whereDoesntHave('files');
        }

        $paginator = $query->orderBy($sortColumn, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        // Ocultar el objeto user de las asignaciones en este endpoint
        $paginator->through(function ($elemento) {
            foreach ($elemento->assignments as $assignment) {
                $assignment->makeHidden(['user']);
            }
            return $elemento;
        });

        return $paginator;
    }

    /**
     * Recolecta recursivamente todos los IDs de descendientes de un nodo,
     * incluyendo el propio nodo raíz.
     *
     * Algoritmo BFS Level-by-level para evitar recursión profunda en PHP.
     * Ejemplo: elemento_raiz_id=5 (Dimensión)
     *   → IDs de Pautas (hijos directos)
     *   → IDs de Fuentes (hijos de Pautas)
     *   → IDs de cualquier nivel más profundo
     */
    private function getAllDescendantIds(int $elementoRaizId): array
    {
        $allIds   = [$elementoRaizId];
        $frontier = [$elementoRaizId];

        while (!empty($frontier)) {
            $childIds = StructureElement::whereIn('padre_id', $frontier)
                ->pluck('elemento_id')
                ->all();

            if (empty($childIds)) {
                break;
            }

            $allIds   = array_merge($allIds, $childIds);
            $frontier = $childIds;
        }

        return $allIds;
    }
}
