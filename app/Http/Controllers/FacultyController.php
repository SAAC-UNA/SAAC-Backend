<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Services\FacultyService;
use App\Http\Requests\FacultyRequest;
use App\Services\AuditLogService;


class FacultyController extends Controller
{
    protected $service; // <-- NUEVO

    public function __construct(FacultyService $service) // <-- serivice
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/facultades?universidad_id=#&sede_id=#
     */
    public function index(Request $request)
    {
        $universidadId = $request->filled('universidad_id') ? (int) $request->input('universidad_id') : null;
        $sedeId        = $request->filled('sede_id')        ? (int) $request->input('sede_id')        : null;

        $items = $this->service->getAll($universidadId, $sedeId);
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/facultades/{id}
     */
    public function show($id)
    {
        $faculty = $this->service->findById((int)$id);
        if (!$faculty) {
            return response()->json(['message' => 'Facultad no encontrada.'], 404);
        }
        return response()->json($faculty, 200);
    }

    /**
     * POST /api/estructura/facultades
     * { "nombre":"Ciencias", "universidad_id":1, "sede_id":1 }
     */
    public function store(FacultyRequest $request)
    {
        $faculty = $this->service->create($request->validated());
        $primaryKeyName = $faculty->getKeyName();

        // Registro en el log de bitacora
        AuditLogService::log(
'crear',
    "Se creó la facultad \"{$faculty->nombre}\" (ID: {$faculty->facultad_id}), ".
            "perteneciente a la universidad ID {$faculty->universidad_id} y sede ID {$faculty->sede_id}.",
    'Facultad'
        );

        return response()
            ->json(['message' => 'Facultad creada correctamente.', 'data' => $faculty], 201)
            ->header('Location', route('facultades.show', $faculty->$primaryKeyName));
    }
    /**
     * PUT/PATCH /api/estructura/facultades/{id}
     */
    public function update(FacultyRequest $request, $id)
    {
        $faculty = Faculty::find($id);
        if (!$faculty) {
            return response()->json(['message' => 'Facultad no encontrada.'], 404);
        }

        $updated = $this->service->update($faculty, $request->validated());
        $oldName = $faculty->nombre;// Nombre antes de la actualización
        // Registro en el log de bitacora log
        AuditLogService::log(
'editar',
    "Se actualizó la facultad ID {$faculty->facultad_id}. Nombre anterior: \"{$oldName}\". Nombre actual: \"{$updated->nombre}\".",
    'Facultad'
        );

        return response()->json(['message' => 'Facultad actualizada correctamente.', 'data' => $updated], 200);
    }

    /**
     * DELETE /api/estructura/facultades/{id}
     */
    public function destroy($id)
    {
        $fac = Faculty::find($id);
        if (!$fac) {
            return response()->json(['message' => 'Facultad no encontrada.'], 404);
        }

        try {
            // ANTES: $fac->delete()
            // AHORA: service->delete(...)
            $this->service->delete($fac);
            // Registro en el log de bitacora
            $nombre = $fac->nombre;
            $idFacultad = $fac->facultad_id;
            $universidadId = $fac->universidad_id;
            $sedeId = $fac->sede_id;
            // Registro en el log de bitacora
            AuditLogService::log(
    'eliminar',
        "Se eliminó la facultad \"{$nombre}\" (ID: {$idFacultad}), ".
                "perteneciente a la universidad ID {$universidadId} y sede ID {$sedeId}.",
        'Facultad'
            );


            return response()->noContent(); // 204
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT'
                ], 409);
            }
            return response()->json(['message' => 'Error al eliminar.', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * PATCH /api/estructura/facultades/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(Request $request, $id)
    {
        $faculty = Faculty::find($id);
        if (!$faculty) {
            return response()->json(['message' => 'Facultad no encontrada.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $newActiveState = $validated['active'];

        // Actualizar el estado de la facultad
        $faculty->activo = $newActiveState;
        $faculty->save();

        // Aplicar cambio en cascada a todos los elementos hijos
        foreach ($faculty->careers as $career) {
            $career->activo = $newActiveState;
            $career->save();
        }

        $cascadeMessage = $newActiveState 
            ? ' Elementos hijos activados en cascada.' 
            : ' Elementos hijos desactivados en cascada.';
        // === Registrar en bitácora ===
        $estadoAnterior = $faculty->getOriginal('activo') ? 'ACTIVA' : 'INACTIVA';
        $estadoNuevo    = $newActiveState ? 'ACTIVA' : 'INACTIVA';

        AuditLogService::log(
            'editar',
            "Se actualizó el estado de la facultad \"{$faculty->nombre}\" (ID: {$faculty->facultad_id}). ".
            "Estado anterior: {$estadoAnterior}. Estado actual: {$estadoNuevo}. ".
            "El cambio se aplicó también a sus carreras asociadas.",
            'Facultad'
        );
        return response()->json([
            'message' => 'Estado de la facultad actualizado correctamente.' . $cascadeMessage,
            'data'    => $faculty
        ], 200);
    }
}
