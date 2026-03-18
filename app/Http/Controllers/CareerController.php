<?php

namespace App\Http\Controllers;

use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Services\CareerService;
use App\Http\Requests\CareerRequest;
use App\Services\AuditLogService;


class CareerController extends Controller
{
    protected $service; // <-- Service

    public function __construct(CareerService $service) // <-- Service
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/carreras
     */
    public function index(Request $request)
    {
        $items = $this->service->getAll();
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/carreras/{id}
     */
    public function show($id)
    {
        $career = $this->service->findById((int)$id);
        if (!$career) {
            return response()->json(['message' => 'Carrera no encontrada.'], 404);
        }
        return response()->json($career, 200);
    }

    /**
     * POST /api/estructura/carreras
     * { "nombre":"Ing. Sistemas", "facultad_id":1 }
     */
    public function store(CareerRequest $request)
    {
        $career = $this->service->create($request->validated());
        $primaryKeyName = $career->getKeyName();

        // Respuesta con código 201 y Location header
        return response()
            ->json(['message' => 'Carrera creada correctamente.', 'data' => $career], 201)
            ->header('Location', route('carreras.show', $career->$primaryKeyName));
    }

    /**
     * PUT/PATCH /api/estructura/carreras/{id}
     */
    public function update(CareerRequest $request, $id)
    {
        $career = Career::find($id);
        if (!$career) {
            return response()->json(['message' => 'Carrera no encontrada.'], 404);
        }

        $updated = $this->service->update($career, $request->validated());

        return response()->json(['message' => 'Carrera actualizada correctamente.', 'data' => $updated], 200);
    }

    /**
     * DELETE /api/estructura/carreras/{id}
     */
    public function destroy($id)
    {
        $career = Career::find($id);
        if (!$career) {
            return response()->json(['message' => 'Carrera no encontrada.'], 404);
        }

        try {
            $this->service->delete($career);

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
     * PATCH /api/estructura/carreras/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(Request $request, $id)
    {
        $career = Career::find($id);
        //agregar para log
        $previousState = $career->activo;
        if (!$career) {
            return response()->json(['message' => 'Carrera no encontrada.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $newActiveState = $validated['active'];

        // Actualizar el estado de la carrera
        $career->activo = $newActiveState;
        $career->saveQuietly(); // observer omitido: el log manual de abajo cubre esta acción
        //agregar para log
        $estadoAnterior = $previousState ? 'ACTIVA' : 'INACTIVA';
        $estadoNuevo    = $newActiveState ? 'ACTIVA' : 'INACTIVA';
        // Registro en el log de bitácora
        AuditLogService::log(
'editar',
    "Se actualizó el estado de la carrera \"{$career->nombre}\" (ID: {$career->carrera_id}). ".
            "Estado anterior: {$estadoAnterior}. Estado actual: {$estadoNuevo}.",
    'Carrera'
        );
        // Nota: Career no tiene elementos hijos en la jerarquía actual
        // Si en el futuro se agregan elementos hijos, se implementará aquí la cascada

        return response()->json([
            'message' => 'Estado de la carrera actualizado correctamente.',
            'data'    => $career
        ], 200);
    }
}
