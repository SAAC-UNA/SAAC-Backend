<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\Evidence;
use Illuminate\Support\Collection;

class EvidenceReportService
{
    public function build(Collection $evidences, array $filters = []): array
    {
        $procesoId = isset($filters['proceso_id']) ? (int) $filters['proceso_id'] : null;

        $items = $evidences
            ->map(fn (Evidence $evidence) => $this->normalizeEvidence($evidence, $procesoId));

        $criteria = $this->loadCriteriaStructure($filters);

        return [
            'generated_at' => now()->toIso8601String(),
            'generated_at_human' => now()->format('d/m/Y H:i'),
            'total_evidences' => $items->count(),
            'dimensions' => $this->groupByHierarchy($criteria, $items),
        ];
    }

    private function normalizeEvidence(Evidence $evidence, ?int $procesoId = null): array
    {
        $criterion = $evidence->criterion;
        $component = $criterion?->component;
        $dimension = $component?->dimension;

        return [
            'dimension' => $this->formatDimension($dimension),
            'component' => $this->formatComponent($component),
            'criterion' => $this->formatCriterion($criterion),
            'evidence' => [
                'id' => $evidence->evidencia_id,
                'nomenclatura' => $evidence->nomenclatura,
                'descripcion' => $evidence->descripcion,
                'estado' => $evidence->estado ?? 'N/A',
                'activo' => (bool) ($evidence->activo ?? true),
                'fecha_publicacion' => optional($evidence->created_at)->format('d/m/Y H:i') ?? 'N/A',
                'responsables' => $this->formatResponsables($evidence),
                'links' => $this->extractLinks($evidence, $procesoId),
            ],
        ];
    }

    private function loadCriteriaStructure(array $filters): Collection
    {
        $query = Criterion::query()->with(['component.dimension']);

        if (!empty($filters['dimension_id'])) {
            $dimensionId = (int) $filters['dimension_id'];
            $query->whereHas('component', fn ($q) => $q->where('dimension_id', $dimensionId));
        }

        if (!empty($filters['componente_id'])) {
            $query->where('componente_id', (int) $filters['componente_id']);
        }

        if (!empty($filters['criterio_id'])) {
            $query->where('criterio_id', (int) $filters['criterio_id']);
        }

        if (!empty($filters['estandar_id'])) {
            $estandarId = (int) $filters['estandar_id'];
            $query->whereHas('standards', fn ($q) => $q->where('estandar_id', $estandarId));
        }

        return $query->orderBy('criterio_id')->get();
    }

    private function formatDimension($dimension): ?array
    {
        if (!$dimension) {
            return null;
        }

        return [
            'id' => $dimension->dimension_id,
            'nomenclatura' => $dimension->nomenclatura,
            'nombre' => $dimension->nombre,
            'label' => trim(($dimension->nomenclatura ?? '') . ' - ' . ($dimension->nombre ?? '')),
        ];
    }

    private function formatComponent($component): ?array
    {
        if (!$component) {
            return null;
        }

        return [
            'id' => $component->componente_id,
            'nomenclatura' => $component->nomenclatura,
            'nombre' => $component->nombre,
            'label' => trim(($component->nomenclatura ?? '') . ' - ' . ($component->nombre ?? '')),
        ];
    }

    private function formatCriterion($criterion): ?array
    {
        if (!$criterion) {
            return null;
        }

        return [
            'id' => $criterion->criterio_id,
            'nomenclatura' => $criterion->nomenclatura,
            'descripcion' => $criterion->descripcion,
            'label' => trim(($criterion->nomenclatura ?? '') . ' - ' . ($criterion->descripcion ?? '')),
        ];
    }

    private function formatResponsables(Evidence $evidence): string
    {
        if (!$evidence->relationLoaded('assignments')) {
            return 'Sin asignar';
        }

        $responsables = $evidence->assignments
            ->map(fn ($assignment) => $assignment->user?->nombre)
            ->filter()
            ->unique()
            ->values();

        return $responsables->isNotEmpty() ? $responsables->join(', ') : 'Sin asignar';
    }

    private function extractLinks(Evidence $evidence, ?int $procesoId = null): array
    {
        if (!$evidence->relationLoaded('files')) {
            return [];
        }

        $files = $evidence->files
            ->filter(fn ($file) => !$procesoId || (int) $file->proceso_id === $procesoId)
            ->values();

        if ($files->isEmpty()) {
            return [];
        }

        $tokenFile = $files->first(fn ($file) => !empty($file->token_publico) && (bool) ($file->is_publico ?? false));
        if ($tokenFile) {
            $baseUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

            return [[
                'label' => 'Ver archivos (' . $files->count() . ')',
                'url' => $baseUrl . '/p/' . $tokenFile->token_publico,
            ]];
        }

        $firstUrl = $files->first(fn ($file) => !empty($file->url));
        if ($firstUrl) {
            return [[
                'label' => 'Ver enlace (' . $files->count() . ')',
                'url' => $firstUrl->url,
            ]];
        }

        return [];
    }

    private function groupByHierarchy(Collection $criteria, Collection $items): array
    {
        $itemsByCriterion = $items->groupBy(fn (array $item) => $item['criterion']['id'] ?? 'sin_criterion');

        return $criteria
            ->map(function (Criterion $criterion) use ($itemsByCriterion) {
                return [
                    'dimension' => $this->formatDimension($criterion->component?->dimension),
                    'component' => $this->formatComponent($criterion->component),
                    'criterion' => $this->formatCriterion($criterion),
                    'evidences' => $itemsByCriterion
                        ->get($criterion->criterio_id, collect())
                        ->map(fn (array $item) => $item['evidence'])
                        ->sort(function (array $a, array $b) {
                            return strnatcasecmp(
                                (string) ($a['nomenclatura'] ?? ''),
                                (string) ($b['nomenclatura'] ?? '')
                            );
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->groupBy(fn (array $row) => $row['dimension']['id'] ?? 'sin_dimension')
            ->map(function (Collection $dimensionRows) {
                $dimension = $dimensionRows->first()['dimension'];

                return [
                    'dimension' => $dimension,
                    'label' => $dimension['label'] ?? 'Sin dimensión',
                    'components' => $dimensionRows
                        ->groupBy(fn (array $row) => $row['component']['id'] ?? 'sin_component')
                        ->map(function (Collection $componentRows) {
                            $component = $componentRows->first()['component'];

                            return [
                                'component' => $component,
                                'label' => $component['label'] ?? 'Sin componente',
                                'criteria' => $componentRows
                                    ->map(function (array $row) {
                                        return [
                                            'criterion' => $row['criterion'],
                                            'label' => $row['criterion']['label'] ?? 'Sin criterio',
                                            'evidences' => $row['evidences'],
                                        ];
                                    })
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}