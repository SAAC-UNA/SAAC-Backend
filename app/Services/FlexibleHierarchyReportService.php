<?php

namespace App\Services;

use App\Models\StructureElement;
use App\Models\StructureModel;
use Illuminate\Support\Collection;

class FlexibleHierarchyReportService
{
    public function build(Collection $elements, ?int $procesoId = null): array
    {
        $nodes = $elements
            ->filter(fn ($item) => $item instanceof StructureElement)
            ->keyBy('elemento_id');

        $model = $elements->first()?->modeloEstructura;
        $hierarchyTypes = $this->hierarchyTypes($model);
        $linkableTypes = $this->linkableTypes($model);

        $criterionType = $linkableTypes->first() ?? ($hierarchyTypes[2] ?? $hierarchyTypes[1] ?? $hierarchyTypes[0] ?? 'fuente');
        $componentType = $hierarchyTypes[1] ?? $hierarchyTypes[0] ?? $criterionType;
        $dimensionType = $hierarchyTypes[0] ?? $componentType;

        $criteriaNodes = $nodes
            ->filter(fn (StructureElement $node) => strtolower((string) $node->tipo) === strtolower((string) $criterionType))
            ->sort(function (StructureElement $a, StructureElement $b) {
                return strnatcasecmp(
                    (string) ($a->nomenclatura ?? $a->elemento_id),
                    (string) ($b->nomenclatura ?? $b->elemento_id)
                );
            })
            ->values();

        $reportRows = [];
        foreach ($criteriaNodes as $criterionNode) {
            $chain = $this->ancestorChain($criterionNode, $nodes);

            $dimensionNode = $this->findFirstByType($chain, $dimensionType) ?? $chain->first();
            $componentNode = $this->findFirstByType($chain, $componentType) ?? $dimensionNode;

            $links = $this->extractLinks($criterionNode, $procesoId);

            $reportRows[] = [
                'dimension' => [
                    'id' => $dimensionNode?->elemento_id ?? ('dim-' . $criterionNode->elemento_id),
                    'label' => $this->label($dimensionNode),
                ],
                'component' => [
                    'id' => $componentNode?->elemento_id ?? ('comp-' . $criterionNode->elemento_id),
                    'label' => $this->label($componentNode),
                ],
                'criterion' => [
                    'id' => $criterionNode->elemento_id,
                    'label' => $this->label($criterionNode),
                ],
                'evidences' => [[
                    'nomenclatura' => $criterionNode->nomenclatura ?: ('Elemento #' . $criterionNode->elemento_id),
                    'links' => $links,
                ]],
            ];
        }

        $dimensions = collect($reportRows)
            ->groupBy(fn (array $row) => (string) $row['dimension']['id'])
            ->map(function (Collection $dimensionRows) {
                $dimension = $dimensionRows->first()['dimension'];

                return [
                    'dimension' => $dimension,
                    'label' => $dimension['label'] ?? 'Sin dimensión',
                    'components' => $dimensionRows
                        ->groupBy(fn (array $row) => (string) $row['component']['id'])
                        ->map(function (Collection $componentRows) {
                            $component = $componentRows->first()['component'];

                            return [
                                'component' => $component,
                                'label' => $component['label'] ?? 'Sin componente',
                                'criteria' => $componentRows
                                    ->map(fn (array $row) => [
                                        'criterion' => $row['criterion'],
                                        'label' => $row['criterion']['label'] ?? 'Sin criterio',
                                        'evidences' => $row['evidences'],
                                    ])
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

        return [
            'generated_at' => now()->toIso8601String(),
            'generated_at_human' => now()->format('d/m/Y H:i'),
            'total_evidences' => $criteriaNodes->count(),
            'layout' => 'leaf_links_inline',
            'dimensions' => $dimensions,
        ];
    }

    private function hierarchyTypes(?StructureModel $model): array
    {
        if (!$model || !is_array($model->tipos_jerarquia)) {
            return ['dimension', 'pauta', 'fuente'];
        }

        $types = collect($model->tipos_jerarquia)
            ->map(fn ($item) => strtolower(trim((string) ($item['tipo'] ?? ''))))
            ->filter()
            ->values()
            ->all();

        return !empty($types) ? $types : ['dimension', 'pauta', 'fuente'];
    }

    private function linkableTypes(?StructureModel $model): Collection
    {
        $types = collect($model?->tipos_asignables ?? [])
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter()
            ->values();

        return $types->isNotEmpty() ? $types : collect(['fuente']);
    }

    private function ancestorChain(StructureElement $node, Collection $nodes): Collection
    {
        $chain = collect([$node]);
        $current = $node;
        $guard = 0;

        while (!empty($current->padre_id) && $guard < 30) {
            $parent = $nodes->get((int) $current->padre_id);
            if (!$parent) {
                break;
            }

            $chain->prepend($parent);
            $current = $parent;
            $guard++;
        }

        return $chain;
    }

    private function findFirstByType(Collection $chain, string $tipo): ?StructureElement
    {
        return $chain->first(fn (StructureElement $node) => strtolower((string) $node->tipo) === strtolower($tipo));
    }

    private function label(?StructureElement $node): string
    {
        if (!$node) {
            return 'Sin nivel';
        }

        $nomenclatura = trim((string) ($node->nomenclatura ?? ''));
        $descripcion = trim((string) ($node->descripcion ?? $node->nombre ?? ''));

        if ($nomenclatura !== '' && $descripcion !== '') {
            return $nomenclatura . ' - ' . $descripcion;
        }

        return $nomenclatura !== '' ? $nomenclatura : ($descripcion !== '' ? $descripcion : ('Elemento #' . $node->elemento_id));
    }

    private function extractLinks(StructureElement $node, ?int $procesoId): array
    {
        $files = collect($node->files ?? [])
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
}
