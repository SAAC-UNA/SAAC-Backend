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

            return response()->noContent(); // 204
        } catch (QueryException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: el criterio tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }
            \Log::error('Error al eliminar criterio', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al eliminar.'], 500);
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
        $estadoAnterior = $criterion->activo ? 'ACTIVO' : 'INACTIVO'; // capturar ANTES de modificar

        // Actualizar el estado del criterio (saveQuietly: el log manual cubre esta acción)
        $criterion->activo = $newActiveState;
        $criterion->saveQuietly();

        // Aplicar cambio en cascada a todos los Elements hijos
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

        $cascadeMessage = $newActiveState
            ? ' Elements hijos activados en cascada.'
            : ' Elements hijos desactivados en cascada.';
        $estadoNuevo = $newActiveState ? 'ACTIVO' : 'INACTIVO';

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
