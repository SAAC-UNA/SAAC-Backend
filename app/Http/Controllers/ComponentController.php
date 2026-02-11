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
        // Este es para registar en el log de bitacora
        AuditLogService::log(
'crear',
    "Se creó el componente \"{$component->nombre}\" (ID: {$component->componente_id}), ".
            "perteneciente a la dimensión ID {$component->dimension_id}.",
    'Componente'
        );

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
        $oldName = $component->nombre;
        $oldCode = $component->codigo ?? null; // si aplica
        $oldDesc = $component->descripcion ?? null;
        // Registro en el log de bitácora solo si hubo cambios relevantes
        if (
            $oldName !== $updated->nombre ||
            $oldCode !== ($updated->codigo ?? null) ||
            $oldDesc !== ($updated->descripcion ?? null)
        ) {
            AuditLogService::log(
                'editar',
                "Se actualizó el componente ID {$component->componente_id}: ".
                "nombre anterior \"{$oldName}\", nuevo nombre \"{$updated->nombre}\"; ".
                "otros campos actualizados según corresponda.",
                'Componente'
            );
        }

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
            // Registro en el log de bitácora
            AuditLogService::log(
    'eliminar',
        "Se eliminó el componente \"{$component->nombre}\" (ID: {$component->componente_id}), ".
                "perteneciente a la dimensión ID {$component->dimension_id}.",
        'Componente'
            );
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

            return response()->json([
                'message' => 'Error al eliminar.',
                'error'   => $e->getMessage(),
            ], 500);
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

        // Actualizar el estado del componente
        $component->activo = $newActiveState;
        $component->save();

        // Aplicar cambio en cascada a todos los elementos hijos
        foreach ($component->criteria as $criterion) {
            $criterion->activo = $newActiveState;
            $criterion->save();

            // Aplicar a estándares del criterio
            foreach ($criterion->standards as $standard) {
                $standard->activo = $newActiveState;
                $standard->save();
            }

            // Aplicar a evidencias del criterio
            foreach ($criterion->evidences as $evidence) {
                $evidence->activo = $newActiveState;
                $evidence->save();
            }
        }

        $cascadeMessage = $newActiveState 
            ? ' Elementos hijos activados en cascada.' 
            : ' Elementos hijos desactivados en cascada.';

        // Registro en el log de bitácora
        $estadoAnterior = $component->activo ? 'ACTIVO' : 'INACTIVO';
        $estadoNuevo    = $newActiveState ? 'ACTIVO' : 'INACTIVO';

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
