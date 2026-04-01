<?php

namespace App\Http\Controllers;

use App\Models\StructureElement;
use App\Services\StructureElementService;
use App\Services\FilterElementService;
use App\Services\AuditLogService;
use App\Exports\ElementsExport;
use App\Http\Requests\StructureElementRequest;
use App\Http\Requests\FilterElementRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StructureElementController extends Controller
{
    protected $service;
    protected FilterElementService $filterService;

    public function __construct(StructureElementService $service, FilterElementService $filterService)
    {
        $this->service       = $service;
        $this->filterService = $filterService;
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
        $tipo = $request->query('tipo');
        $modeloId = $request->query('modelo_estructura_id') ? (int)$request->query('modelo_estructura_id') : null;
        $items = $this->service->getAll($tipo, $modeloId);
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
            'message'   => 'Elemento creado correctamente.',
            'data'      => $item,
            'evidencias' => $item->evidencias,
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

        $filters['per_page'] = 999999;
        $elements = $this->filterService->filter($filters, $user)->items();

        $collection = collect($elements);
        $exporter   = new ElementsExport($collection);
        $filePath   = $exporter->generate();

        return response()->download($filePath)->deleteFileAfterSend(true);
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

        return $pdf->download($filename);
    }
}
