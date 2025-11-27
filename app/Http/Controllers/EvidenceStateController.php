<?php

namespace App\Http\Controllers;

use App\Models\EvidenceState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Services\EvidenceStateService;
use App\Http\Requests\EvidenceStateRequest;
use App\Services\AuditLogService;


class EvidenceStateController extends Controller
{
    protected $service;

    public function __construct(EvidenceStateService $service) // <-- NUEVO
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/estados-evidencia
     */
    public function index()
    {
        $items = $this->service->getAll(); // antes: EvidenceState::orderBy(...)
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/estados-evidencia/{id}
     */
    public function show($id)
    {
        $estado = $this->service->findById((int)$id); // antes: EvidenceState::find
        if (!$estado) {
            return response()->json(['message' => 'Estado no encontrado.'], 404);
        }
        return response()->json($estado, 200);
    }

    /**
     * POST /api/estructura/estados-evidencia
     */
    public function store(EvidenceStateRequest $request)
    {
        try {
            $estado = $this->service->create($request->validated());
            // Registro en el log de bitácora
            AuditLogService::log(
'crear',
    "Se creó el estado de evidencia \"{$estado->nombre}\" (ID: {$estado->estado_evidencia_id}).",
    'Estado de Evidencia'
             );
            return response()->json($estado, 201);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json(['message' => 'Error al crear el estado.'], 500);
        }
    }

    /**
     * PUT /api/estructura/estados-evidencia/{id}
     */
    public function update(EvidenceStateRequest $request, $id)
    {
        $estado = \App\Models\EvidenceState::find($id);
        if (!$estado) {
            return response()->json(['message' => 'Estado no encontrado.'], 404);
        }

        try {
            $estado = $this->service->update($estado, $request->validated());
            // Registro en el log de bitácora si hubo cambios
            $oldName = $estado->getOriginal('nombre');

            if ($oldName !== $estado->nombre) {
                AuditLogService::log(
        'editar',
            "Se actualizó el estado de evidencia ID {$estado->estado_id}: ".
                    "nombre anterior \"{$oldName}\", nuevo nombre \"{$estado->nombre}\".",
            'Estado de Evidencia'
                );
            }
            return response()->json($estado, 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json(['message' => 'Error al actualizar el estado.'], 500);
        }
    }

    /**
     * DELETE /api/estructura/estados-evidencia/{id}
     */
    public function destroy($id)
    {
        $estado = EvidenceState::find($id);
        if (!$estado) {
            return response()->json(['message' => 'Estado no encontrado.'], 404);
        }

        try {
            $this->service->delete($estado);
            // Registro en el log de bitácora
            AuditLogService::log(
'eliminar',
    "Se eliminó el estado de evidencia \"{$estado->nombre}\" (ID: {$estado->estado_evidencia_id}).",
    'Estado de Evidencia'
            );
            return response()->noContent(); // 204 No Content
        } catch (QueryException $ex) {
            return response()->json(['message' => 'No se puede eliminar: está en uso.'], 409);
        }
    }
}
