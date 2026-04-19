<?php

namespace App\Http\Controllers;

use App\Models\Dimension;
use Illuminate\Database\QueryException;
use App\Services\DimensionService;
use App\Http\Requests\DimensionRequest;
use App\Services\AuditLogService;


class DimensionController extends Controller
{
    protected $service; // Service

    public function __construct(DimensionService $service) // Service
    {
        $this->service = $service;
    }
    /**
     * GET /api/dimensiones
     */
    public function index()
    {
        $items = $this->service->getAll(); // antes: Dimension::orderBy(...)
        return response()->json($items, 200);
    }

    /**
     * GET /api/dimensiones/{id}
     */
    public function show($id)
    {
        $dimension = $this->service->findById((int)$id); // antes: Dimension::find($id)
        if (!$dimension) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }
        return response()->json($dimension, 200);
    }

    /**
     * POST /api/dimensiones
     * Body JSON: { "comentario_id": 1, "nombre": "Gestión Académica", "nomenclatura": "D1" }
     */
    public function store(DimensionRequest $request)
    {
        $dimension = $this->service->create($request->validated());
        $primaryKeyName = $dimension->getKeyName();

        return response()
            ->json(['message' => 'Dimensión creada correctamente.', 'data' => $dimension], 201)
            ->header('Location', route('dimensiones.show', $dimension->$primaryKeyName));
    }
    /**
     * PUT/PATCH /api/dimensiones/{id}
     * Body JSON: { "comentario_id": 1, "nombre": "Gestión Académica y Curricular", "nomenclatura": "D1" }
     */
    public function update(DimensionRequest $request, $id)
    {
        $dimension = Dimension::find($id);
        if (!$dimension) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }

        $updated = $this->service->update($dimension, $request->validated());

        return response()->json(['message' => 'Dimensión actualizada correctamente.', 'data' => $updated], 200);
    }

    /**
     * DELETE /api/dimensiones/{id}
     */
    public function destroy($id)
    {
        $dimension = Dimension::find($id);
        if (!$dimension) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }

        try {
            $this->service->delete($dimension); // antes: $d->delete()

            return response()->noContent(); // 204
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: la dimensión tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT'
                ], 409);
            }
            \Log::error('Error al eliminar dimensión', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al eliminar.'], 500);
        }
    }
    /**
     * PATCH /api/estructura/dimensiones/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(\Illuminate\Http\Request $request, $id)
    {
        $dimension = Dimension::find($id);
        if (!$dimension) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $newActiveState = $validated['active'];
        $estadoAnterior = $dimension->activo ? 'ACTIVA' : 'INACTIVA'; // capturar ANTES de modificar

        // Actualizar estado (saveQuietly: el log manual de abajo cubre esta acción)
        $dimension->activo = $newActiveState;
        $dimension->saveQuietly();

        // Aplicar cambio en cascada a todos los elementos hijos
        foreach ($dimension->components as $component) {
            $component->activo = $newActiveState;
            $component->saveQuietly();

            // Aplicar a criterios del componente
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
        }

        $cascadeMessage = $newActiveState
            ? ' Elementos hijos activados en cascada.'
            : ' Elementos hijos desactivados en cascada.';
        $estadoNuevo = $newActiveState ? 'ACTIVA' : 'INACTIVA';

        AuditLogService::log(
'editar',
    "Se actualizó el estado de la dimensión \"{$dimension->nombre}\" (ID: {$dimension->dimension_id}). ".
            "Estado anterior: {$estadoAnterior}. Estado nuevo: {$estadoNuevo}. ".
            "Se aplicó cambio en cascada a hijos.",
    'Dimensión'
        );

        return response()->json([
            'message' => 'Estado de la dimensión actualizado correctamente.' . $cascadeMessage,
            'data'    => $dimension
        ], 200);
    }
}
