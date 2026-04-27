<?php

namespace App\Http\Controllers;

use App\Models\StructureElement;
use App\Models\Process;
use App\Services\StructureElementService;
use App\Services\FilterElementService;
use App\Services\FlexibleHierarchyReportService;
use App\Services\AuditLogService;
use App\Exports\InformeExport;
use App\Http\Requests\StructureElementRequest;
use App\Http\Requests\FilterElementRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StructureElementController extends Controller
{
    protected $service;
    protected FilterElementService $filterService;
    protected FlexibleHierarchyReportService $reportService;

    public function __construct(
        StructureElementService $service,
        FilterElementService $filterService,
        FlexibleHierarchyReportService $reportService
    )
    {
        $this->service       = $service;
        $this->filterService = $filterService;
        $this->reportService = $reportService;
    }

    /**
     * GET /api/estructura/elementos/filter
     * Filtrado avanzado de elementos (modelo flexible).
     */
    public function filter(FilterElementRequest $request)
    {
        $user    = $request->user();
        $filters = $request->validated();
        $result  = $this->filterService->filter($filters, $user);
        return response()->json($result, 200);
    }

    /**
     * GET /api/estructura/elementos
     * Query params: ?tipo=pauta&modelo_estructura_id=2
     */
    public function index(Request $request)
    {
        $type = $request->query('tipo');
        $modelId = $request->query('modelo_estructura_id') ? (int)$request->query('modelo_estructura_id') : null;
        $items = $this->service->getAll($type, $modelId);
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/elementos/{id}
     */
    public function show($id)
    {
        $item = $this->service->findById((int)$id);

        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        return response()->json($item, 200);
    }

    /**
     * GET /api/estructura/jerarquia/arbol
     * Query params: ?root_id=5&modelo_estructura_id=2
     *
     * COMENTADO: Funcionalidad de árbol jerárquico para futuro.
     * Usará SP_OBTENER_ARBOL_JERARQUIA para construir estructura completa.
     */
    /*
    public function tree(Request $request)
    {
        $rootId = $request->query('root_id') ? (int)$request->query('root_id') : null;
        $modeloId = $request->query('modelo_estructura_id') ? (int)$request->query('modelo_estructura_id') : null;
        $tree = $this->service->getTree($rootId, $modeloId);
        return response()->json($tree, 200);
    }
    */

    /**
     * POST /api/estructura/elementos
     *
     * HU-012 (escritura flexible) — Gap 6:
     * ANTES: el response devolvía $item crudo (sin evidencias).
     *        Si el cliente enviaba evidencias[], se creaban en la BD pero no
     *        aparecían en la respuesta, forzando un segundo GET para confirmarlas.
     * DESPUÉS: el service ya retorna $item->load('evidencias') (dentro de la
     *          transacción), por lo que el response incluye el array 'evidencias'
     *          directamente en el 201, confirmando atómicamente la creación.
     */
    public function store(StructureElementRequest $request)
    {
        $item = $this->service->create($request->validated());

        AuditLogService::log(
            'crear',
            "Se creó el elemento \"{$item->nombre}\" (Tipo: {$item->tipo}, ID: {$item->elemento_id}).",
            'Elemento'
        );

        return response()->json([
            'message' => 'Elemento creado correctamente.',
            'data'    => $item,
        ], 201);
    }

    /**
     * PUT/PATCH /api/estructura/elementos/{id}
     */
    public function update(StructureElementRequest $request, $id)
    {
        $item = StructureElement::find($id);

        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        $updated = $this->service->update($item, $request->validated());

        AuditLogService::log(
            'editar',
            "Se actualizó el elemento ID {$item->elemento_id} (Tipo: {$item->tipo}).",
            'Elemento'
        );

        return response()->json([
            'message' => 'Elemento actualizado correctamente.',
            'data' => $updated
        ], 200);
    }

    /**
     * DELETE /api/estructura/elementos/{id}
     */
    public function destroy($id)
    {
        $item = StructureElement::find($id);

        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        // Verificar si tiene hijos
        if ($item->hasChildren()) {
            return response()->json([
                'message' => 'No se puede eliminar un elemento que tiene hijos. Elimine primero los elementos hijos.'
            ], 422);
        }

        $this->service->delete($item);

        AuditLogService::log(
            'eliminar',
            "Se eliminó el elemento \"{$item->nombre}\" (Tipo: {$item->tipo}, ID: {$item->elemento_id}).",
            'Elemento'
        );

        return response()->json([
            'message' => 'Elemento eliminado correctamente.'
        ], 200);
    }

    /**
     * PATCH /api/estructura/elementos/{id}/active
     * Activar/Desactivar un elemento.
     * ACTUALIZADO: Ahora usa ElementoService (patrón Service consistente)
     */
    public function setActive(Request $request, $id)
    {
        $item = StructureElement::find($id);

        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $newActiveState = $validated['active'];
        $this->service->setActiveWithCascade($item, $newActiveState);

        $statusText = $newActiveState ? 'activado' : 'desactivado';
        $cascadeMessage = ' Elementos hijos actualizados en cascada.';

        AuditLogService::log(
            'editar',
            "Se {$statusText} el elemento tipo \"{$item->tipo}\" [nomenclatura: {$item->nomenclatura}] (ID: {$item->elemento_id}). Se aplicó cambio en cascada a hijos.",
            'Elemento'
        );

        return response()->json([
            'message' => "Elemento {$statusText} correctamente.{$cascadeMessage}",
            'active'  => $newActiveState,
        ], 200);
    }

    /**
     * GET /api/estructura/elementos/export/excel
     * Exportar elementos filtrados a Excel.
     */
    public function exportExcel(FilterElementRequest $request)
    {
        $user    = $request->user();
        $filters = $request->validated();

        $collection = $this->getHierarchyCollectionForExport($filters, $user);
        $report = $this->reportService->build(
            $collection,
            isset($filters['proceso_id']) ? (int) $filters['proceso_id'] : null
        );

        $exporter   = new InformeExport($report);
        $filePath   = $exporter->generate();
        $filename   = 'informe_estructura_' . now()->format('Y-m-d_His') . '.xlsx';

        AuditLogService::log(
            'exportar',
            "Exportó elementos en Excel con {$report['total_evidences']} registro(s).",
            'Reportes',
            $user?->usuario_id
        );

        return response()
            ->download(
                $filePath,
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    private function getHierarchyCollectionForExport(array $filters, $user): Collection
    {
        $modeloId = $filters['modelo_estructura_id'] ?? null;

        if (!$modeloId && !empty($filters['proceso_id'])) {
            $modeloId = Process::query()
                ->with('accreditationCycle')
                ->find($filters['proceso_id'])
                ?->accreditationCycle
                ?->modelo_estructura_id;
        }

        if (!$modeloId) {
            $filters['per_page'] = 999999;
            return collect($this->filterService->filter($filters, $user)->items());
        }

        $query = StructureElement::query()
            ->with(['parent', 'files', 'modeloEstructura'])
            ->where('modelo_estructura_id', $modeloId)
            ->orderBy('elemento_id');

        if (!empty($filters['elemento_raiz_id'])) {
            $ids = $this->getDescendantIds((int) $filters['elemento_raiz_id']);
            $query->whereIn('elemento_id', $ids);
        }

        return $query->get();
    }

    private function getDescendantIds(int $rootId): array
    {
        $allIds = [$rootId];
        $frontier = [$rootId];

        while (!empty($frontier)) {
            $children = StructureElement::query()
                ->whereIn('padre_id', $frontier)
                ->pluck('elemento_id')
                ->all();

            if (empty($children)) {
                break;
            }

            $allIds = array_merge($allIds, $children);
            $frontier = $children;
        }

        return $allIds;
    }

    /**
     * GET /api/estructura/elementos/export/pdf
     * Exportar elementos filtrados a PDF.
     */
    public function exportPDF(FilterElementRequest $request)
    {
        $user    = $request->user();
        $filters = $request->validated();

        $filters['per_page'] = 999999;
        $elements = $this->filterService->filter($filters, $user)->items();

        $collection = collect($elements);

        $pdf = Pdf::loadView('exports.elements', ['elements' => $collection])
            ->setPaper('a4', 'landscape');

        $filename = 'elementos_' . now()->format('Y-m-d_His') . '.pdf';

        AuditLogService::log(
            'exportar',
            "Exportó elementos en PDF con {$collection->count()} registro(s).",
            'Reportes',
            $user?->usuario_id
        );

        return $pdf->download($filename);
    }
}
