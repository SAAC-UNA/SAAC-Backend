<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use Illuminate\Database\QueryException;
use App\Http\Resources\CriterionResource;
use App\Services\CriterionService;
use App\Http\Requests\CriterionRequest;
use App\Services\AuditLogService;


class CriterionController extends Controller
{
    protected $service; // Service

    public function __construct(CriterionService $service)
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/criterios
     */
    public function index()
    {
        $items = $this->service->getAll();
        return CriterionResource::collection($items)->response(); // 200
    }

    /**
     * GET /api/estructura/criterios/{id}
     */
    public function show($id)
    {
        $criterion = $this->service->findById((int)$id);
        if (!$criterion) return response()->json(['message' => 'Criterio no encontrado.'], 404);

        return CriterionResource::make($criterion)->response(); // 200
    }
    /**
     * POST /api/estructura/criterios
     */
    public function store(CriterionRequest $request)
    {
        $criterion = $this->service->create($request->validated());
        // registro en el log de bitacora
        AuditLogService::log(
'crear',
    "Se creó el criterio \"{$criterion->nombre}\" (ID: {$criterion->criterio_id}), ".
            "perteneciente al componente ID {$criterion->componente_id}.",
    'Criterio'
        );
        return \App\Http\Resources\CriterionResource::make($criterion)
            ->response()
            ->setStatusCode(201);
    }
    /**
     * PUT/PATCH /api/estructura/criterios/{id}
     */
    public function update(CriterionRequest $request, $id)
    {
        $criterion = \App\Models\Criterion::find($id);
        if (!$criterion) {
            return response()->json(['message' => 'Criterio no encontrado.'], 404);
        }

        $updated = $this->service->update($criterion, $request->validated());
        // Registro en el log de bitácora
        $oldName  = $criterion->nombre;
        $oldCode  = $criterion->codigo ?? null;
        $oldDesc  = $criterion->descripcion ?? null;

        if (
            $oldName !== $updated->nombre ||
            $oldCode !== ($updated->codigo ?? null) ||
            $oldDesc !== ($updated->descripcion ?? null)
        ) {
            AuditLogService::log(
                'editar',
                "Se actualizó el criterio ID {$criterion->criterio_id}: ".
                "nombre anterior \"{$oldName}\", nuevo nombre \"{$updated->nombre}\"; ".
                "otros campos actualizados según corresponda.",
                'Criterio'
            );
        }


        return \App\Http\Resources\CriterionResource::make($updated)
            ->response()
            ->setStatusCode(200);
    }
    /**
     * DELETE /api/estructura/criterios/{id}
     */
    public function destroy($id)
    {
        $criterion = Criterion::find($id);
        if (!$criterion) {
            return response()->json(['message' => 'Criterio no encontrado.'], 404);
        }

        try {
            $this->service->delete($criterion);
            // Registro en el log de bitácora
            AuditLogService::log(
    'eliminar',
        "Se eliminó el criterio \"{$criterion->nombre}\" (ID: {$criterion->criterio_id}), ".
                "perteneciente al componente ID {$criterion->componente_id}.",
        'Criterio'
            );

            return response()->noContent(); // 204
        } catch (QueryException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: el criterio tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }
            return response()->json(['message' => 'Error al eliminar.', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * PATCH /api/estructura/criterios/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(\Illuminate\Http\Request $request, $id)
    {
        $criterion = Criterion::find($id);
        if (!$criterion) {
            return response()->json(['message' => 'Criterio no encontrado.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $newActiveState = $validated['active'];

        // Actualizar el estado del criterio
        $criterion->activo = $newActiveState;
        $criterion->save();

        // Aplicar cambio en cascada a todos los elementos hijos
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

        $cascadeMessage = $newActiveState 
            ? ' Elementos hijos activados en cascada.' 
            : ' Elementos hijos desactivados en cascada.';
            // === Registrar en bitácora ===
        $estadoAnterior = $criterion->activo ? 'ACTIVO' : 'INACTIVO';
        $estadoNuevo    = $newActiveState ? 'ACTIVO' : 'INACTIVO';

        AuditLogService::log(
'editar',
    "Se actualizó el estado del criterio \"{$criterion->nombre}\" (ID: {$criterion->criterio_id}). ".
            "Estado anterior: {$estadoAnterior}. Estado nuevo: {$estadoNuevo}. ".
            "Se aplicó cambio en cascada a estándares y evidencias.",
    'Criterio'
        );

        return response()->json([
            'message' => 'Estado del criterio actualizado correctamente.' . $cascadeMessage,
            'data'    => $criterion
        ], 200);
    }
}
