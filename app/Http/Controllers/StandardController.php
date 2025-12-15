<?php

namespace App\Http\Controllers;

use App\Models\Standard;
use Illuminate\Database\QueryException;
use App\Services\StandardService;
use App\Http\Requests\StandardRequest;
use App\Services\AuditLogService;




class StandardController extends Controller
{
    protected $service; // <-- NUEVO

    public function __construct(StandardService $service) // <-- NUEVO
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/estandares
     */
    public function index()
    {

        $items = $this->service->getAll(); // antes: Standard::orderBy...
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/estandares/{id}
     */
    public function show($id)
    {
        $standar = $this->service->findById((int)$id); // antes: Standard::find
        if (!$standar) {
            return response()->json(['message' => 'Estándar no encontrado.'], 404);
        }
        return response()->json($standar, 200);
    }

    /**
     * POST /api/estructura/estandares
     */
    public function store(StandardRequest $request)
    {
        try {
            $standar = $this->service->create($request->validated());
            // Registro en el log de bitácora
            AuditLogService::log(
'crear',
    "Se creó el estándar \"{$standar->nombre}\" (ID: {$standar->estandar_id}), ".
            "perteneciente al criterio ID {$standar->criterio_id}.",
    'Estándar'
        );
            return response()->json($standar, 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'Error al crear el estándar.'], 500);
        }
    }

    /**
     * PUT /api/estructura/estandares/{id}
     */
    public function update(StandardRequest $request, $id)
    {
        $standar = \App\Models\Standard::find($id);
        if (!$standar) return response()->json(['message' => 'Estándar no encontrado.'], 404);

        try {
            $standar = $this->service->update($standar, $request->validated());
            // Registro en el log de bitácora si hubo cambios
            $oldName = $standar->getOriginal('nombre');
            $oldCode = $standar->getOriginal('codigo') ?? null;
            $oldDesc = $standar->getOriginal('descripcion') ?? null;

            if (
                $oldName !== $standar->nombre ||
                $oldCode !== ($standar->codigo ?? null) ||
                $oldDesc !== ($standar->descripcion ?? null)
            ) {
                AuditLogService::log(
                    'editar',
                    "Se actualizó el estándar ID {$standar->estandar_id}: ".
                    "nombre anterior \"{$oldName}\", nuevo nombre \"{$standar->nombre}\"; ".
                    "otros campos modificados según corresponda.",
                    'Estándar'
                );
            }

            return response()->json($standar, 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'Error al actualizar el estándar.'], 500);
        }
    }

    /**
     * DELETE /api/estructura/estandares/{id}
     */
    public function destroy($id)
    {

        $standar = Standard::find($id);
        if (!$standar) return response()->json(['message' => 'Estándar no encontrado.'], 404);

        try {
            $this->service->delete($standar); // antes: $std->delete()
            // Registro en el log de bitácora
            AuditLogService::log(
'eliminar',
    "Se eliminó el estándar \"{$standar->nombre}\" (ID: {$standar->estandar_id}), perteneciente al criterio ID {$standar->criterio_id}.",
    'Estándar'
            );
            return response()->noContent(); //204
        } catch (QueryException $e) {
            return response()->json(['message' => 'No se puede eliminar.'], 409);
        }
    }
    /**
     * PATCH /api/estructura/estandares/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(\Illuminate\Http\Request $request, $id)
    {
        $standar = Standard::find($id);
        if (!$standar) {
            return response()->json(['message' => 'Estándar no encontrado.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $standar->activo = $validated['active'];
        $standar->save();
        $estadoAnterior = $standar->activo ? 'ACTIVO' : 'INACTIVO';
        $estadoNuevo    = $validated['active'] ? 'ACTIVO' : 'INACTIVO';

        AuditLogService::log(
'editar',
    "Se actualizó el estado del estándar \"{$standar->nombre}\" (ID: {$standar->estandar_id}). ".
            "Estado anterior: {$estadoAnterior}. Estado nuevo: {$estadoNuevo}.",
    'Estándar'
        );

        return response()->json([
            'message' => 'Estado del estándar actualizado correctamente.',
            'data'    => $standar
        ], 200);
    }
}
