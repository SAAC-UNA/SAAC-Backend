<?php

namespace App\Http\Controllers;

use App\Models\Jerarquia;
use App\Services\JerarquiaService;
use App\Services\AuditLogService;
use App\Http\Requests\JerarquiaRequest;
use Illuminate\Http\Request;

class JerarquiaController extends Controller
{
    protected $service;

    public function __construct(JerarquiaService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/estructura/jerarquia
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
     * GET /api/estructura/jerarquia/{id}
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
     * POST /api/estructura/jerarquia
     */
    public function store(JerarquiaRequest $request)
    {
        $item = $this->service->create($request->validated());

        AuditLogService::log(
            'crear',
            "Se creó el elemento de jerarquía \"{$item->nombre}\" (Tipo: {$item->tipo}, ID: {$item->jerarquia_id}).",
            'Jerarquía'
        );

        return response()->json([
            'message' => 'Elemento creado correctamente.',
            'data' => $item
        ], 201);
    }

    /**
     * PUT/PATCH /api/estructura/jerarquia/{id}
     */
    public function update(JerarquiaRequest $request, $id)
    {
        $item = Jerarquia::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        $updated = $this->service->update($item, $request->validated());

        AuditLogService::log(
            'editar',
            "Se actualizó el elemento de jerarquía ID {$item->jerarquia_id} (Tipo: {$item->tipo}).",
            'Jerarquía'
        );

        return response()->json([
            'message' => 'Elemento actualizado correctamente.',
            'data' => $updated
        ], 200);
    }

    /**
     * DELETE /api/estructura/jerarquia/{id}
     */
    public function destroy($id)
    {
        $item = Jerarquia::find($id);
        
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
            "Se eliminó el elemento de jerarquía \"{$item->nombre}\" (Tipo: {$item->tipo}, ID: {$item->jerarquia_id}).",
            'Jerarquía'
        );

        return response()->json([
            'message' => 'Elemento eliminado correctamente.'
        ], 200);
    }

    /**
     * PATCH /api/estructura/jerarquia/{id}/active
     * Activar/Desactivar un elemento de jerarquía.
     * ACTUALIZADO: Ahora usa JerarquiaService (patrón Service consistente)
     */
    public function setActive(Request $request, $id)
    {
        $item = Jerarquia::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        // Si el estado solicitado es diferente al actual, hacer toggle
        if ($item->activo !== $validated['active']) {
            $item = $this->service->toggleActive($item);
        }

        $statusText = $item->activo ? 'activado' : 'desactivado';

        AuditLogService::log(
            'editar',
            "Se {$statusText} el elemento de jerarquía \"{$item->nombre}\" (ID: {$item->jerarquia_id}).",
            'Jerarquía'
        );

        return response()->json([
            'message' => "Elemento {$statusText} correctamente.",
            'data' => $item
        ], 200);
    }
}
