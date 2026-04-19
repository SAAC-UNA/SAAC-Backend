<?php

namespace App\Http\Controllers;

use App\Models\Component;
use Illuminate\Database\QueryException;
use App\Services\ComponentService;
use App\Http\Requests\ComponentRequest;
use App\Services\AuditLogService;



class ComponentController extends Controller
{
    protected $service; // Service

    public function __construct(ComponentService $service) // Service
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/componentes
     */
    public function index()
    {
        $items = $this->service->getAll(); // antes: Eloquent directo
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/componentes/{id}
     */
    public function show($id)
    {
        $component = $this->service->findById((int)$id); // antes: Eloquent directo
        if (!$component) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }
        return response()->json($component, 200);
    }

    /**
     * POST /api/estructura/componentes
     */
    public function store(ComponentRequest $request)
    {
        $component = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Componente creado correctamente.',
            'data'    => $component->load(['dimension']),
        ], 201);
    }

    /**
     * PUT/PATCH /api/estructura/componentes/{id}
     * (permite actualización parcial)
     */
    public function update(ComponentRequest $request, $id)
    {
        $component = Component::find($id);
        if (!$component) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }

        $updated = $this->service->update($component, $request->validated());

        return response()->json([
            'message' => 'Componente actualizado correctamente.',
            'data'    => $updated,
        ], 200);
    }

    /**
     * DELETE /api/estructura/componentes/{id}
     */
    public function destroy($id)
    {
        $component = Component::find($id);
        if (!$component) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }

        try {
            $this->service->delete($component); // antes: $c->delete()

            return response()->noContent(); // 204
        } catch (QueryException $e) {
            $sqlState  = $e->errorInfo[0] ?? null;   // '23000' => integridad
            $driverErr = (int)($e->errorInfo[1] ?? 0); // 1451 => FK en DELETE

            if ($sqlState === '23000' && $driverErr === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: el componente tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }

            \Log::error('Error al eliminar componente', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al eliminar.'], 500);
        }
    }
    /**
     * PATCH /api/estructura/componentes/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(\Illuminate\Http\Request $request, $id)
    {
        $component = Component::find($id);
        if (!$component) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $newActiveState = $validated['active'];
        $estadoAnterior = $component->activo ? 'ACTIVO' : 'INACTIVO'; // capturar ANTES de modificar

        // Actualizar estado (saveQuietly: el log manual de abajo cubre esta acción)
        $component->activo = $newActiveState;
        $component->saveQuietly();

        // Aplicar cambio en cascada a todos los elementos hijos
        foreach ($component->criteria as $criterion) {
            $criterion->activo = $newActiveState;
            $criterion->saveQuietly();

            // Aplicar a estándares del criterio
            foreach ($criterion->standards as $standard) {
                $standard->activo = $newActiveState;
                $standard->saveQuietly();
            }

            // Aplicar a evidencias del criterio
            foreach ($criterion->evidences as $evidence) {
                $evidence->activo = $newActiveState;
                $evidence->saveQuietly();
            }
        }

        $cascadeMessage = $newActiveState
            ? ' Elementos hijos activados en cascada.'
            : ' Elementos hijos desactivados en cascada.';

        // Registro en el log de bitácora
        $estadoNuevo = $newActiveState ? 'ACTIVO' : 'INACTIVO';

        AuditLogService::log(
'editar',
    "Se actualizó el estado del componente \"{$component->nombre}\" (ID: {$component->componente_id}). ".
            "Estado anterior: {$estadoAnterior}. Estado nuevo: {$estadoNuevo}. ".
            "Se aplicó cambio en cascada a hijos (criterios, estándares, evidencias).",
    'Componente'
        );

        return response()->json([
            'message' => 'Estado del componente actualizado correctamente.' . $cascadeMessage,
            'data'    => $component
        ], 200);
    }
}
