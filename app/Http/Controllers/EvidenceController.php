<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use Illuminate\Database\QueryException;
use App\Http\Resources\EvidenceResource;
use App\Services\EvidenceService;
use App\Http\Requests\EvidenceRequest;
use App\Services\AuditLogService;


class EvidenceController extends Controller
{
    protected $service; // Service

    public function __construct(EvidenceService $service)
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/evidencias
     */
    public function index()
    {
        $items = $this->service->getAll(); // antes: Evidence::orderBy(...)
        return EvidenceResource::collection($items)->response(); // 200
    }

    /**
     * GET /api/estructura/evidencias/{id}
     */
    public function show($id)
    {
        $evidence = $this->service->findById((int)$id); // antes: Evidence::find
        if (!$evidence) return response()->json(['message' => 'Evidencia no encontrada.'], 404);

        return EvidenceResource::make($evidence)->response(); // 200
    }

    /**
     * POST /api/estructura/evidencias
     */
    public function store(EvidenceRequest $request)
    {
        $evidence = $this->service->create($request->validated());
        // Registro en el log de bitácora
        AuditLogService::log(
'crear',
    "Se creó la evidencia \"{$evidence->nombre}\" (ID: {$evidence->evidencia_id}), ".
            "perteneciente al criterio ID {$evidence->criterio_id}.",
    'Evidencia'
        );

        return \App\Http\Resources\EvidenceResource::make($evidence)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/estructura/evidencias/{id}
     */
    public function update(EvidenceRequest $request, $id)
    {
        $evidence = \App\Models\Evidence::find($id);
        if (!$evidence) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        $updated = $this->service->update($evidence, $request->validated());
        // Registro en el log de bitácora
        $oldName = $evidence->nombre;
        $oldCode = $evidence->codigo ?? null;
        $oldDesc = $evidence->descripcion ?? null;

        if (
            $oldName !== $updated->nombre ||
            $oldCode !== ($updated->codigo ?? null) ||
            $oldDesc !== ($updated->descripcion ?? null)
        ) {
            AuditLogService::log(
    'editar',
        "Se actualizó la evidencia ID {$evidence->evidencia_id}: ".
                "nombre anterior \"{$oldName}\", nuevo nombre \"{$updated->nombre}\"; ".
                "otros campos modificados según corresponda.",
        'Evidencia'
            );
        }   

        return \App\Http\Resources\EvidenceResource::make($updated)
            ->response()
            ->setStatusCode(200);
    }
    /**
     * DELETE /api/estructura/evidencias/{id}
     */
    public function destroy($id)
    {
        $evidence = Evidence::find($id);
        if (!$evidence) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        try {
            $this->service->delete($evidence); // antes: $e->delete()
            // Registro en el log de bitácora
            AuditLogService::log(
    'eliminar',
        "Se eliminó la evidencia \"{$evidence->nombre}\" (ID: {$evidence->evidencia_id}), perteneciente al criterio ID {$evidence->criterio_id}.",
        'Evidencia'
            );

            return response()->noContent(); // 204
        } catch (QueryException $qe) {
            if ((int)($qe->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: la evidencia tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }
            return response()->json(['message' => 'Error al eliminar.', 'error' => $qe->getMessage()], 500);
        }
    }
    /**
     * PATCH /api/estructura/evidencias/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(\Illuminate\Http\Request $request, $id)
    {
        $evidence = Evidence::find($id);
        if (!$evidence) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $evidence->activo = $validated['active'];
        $evidence->save();
        // Registro en el log de bitácora
        $estadoAnterior = $validated['active'] ? 'INACTIVA' : 'ACTIVA';
        $estadoNuevo    = $validated['active'] ? 'ACTIVA' : 'INACTIVA';
        AuditLogService::log(
'editar',
    "Se actualizó el estado de la evidencia \"{$evidence->nombre}\" (ID: {$evidence->evidencia_id}). ".
            "Estado anterior: {$estadoAnterior}. Estado actual: {$estadoNuevo}.",
    'Evidencia'
        );

        return response()->json([
            'message' => 'Estado de la evidencia actualizado correctamente.',
            'data'    => $evidence
        ], 200);
    }
}
