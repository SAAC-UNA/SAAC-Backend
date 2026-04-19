<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Services\CampusService;
use App\Http\Requests\CampusRequest;
use Illuminate\Support\Facades\Log;

class CampusController extends Controller
{
    protected $service; // Para service

    public function __construct(CampusService $service) // Para service
    {
        $this->service = $service;
    }

    /**
     * GET /api/campus?universidad_id=#
     */
    public function index(Request $request)
    {
        // Delegar al service no directo  (mismo comportamiento)
        $universityId = $request->filled('universidad_id')
            ? (int) $request->input('universidad_id')
            : null;

        $items = $this->service->getAll($universityId);
        return response()->json($items, 200);
    }

    /**
     * GET /api/campus/{id}
     */
    public function show($id)
    {
        $campus = $this->service->findById((int)$id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }
        return response()->json($campus, 200);
    }

    /**
     * POST /api/campus
     * { "nombre":"Occidente", "universidad_id":1 }
     */
    public function store(CampusRequest $request)
    {
        $campus = $this->service->create($request->validated());

        $primaryKeyName = $campus->getKeyName();

        return response()
            ->json([
                'message' => 'Campus creado correctamente.',
                'data'    => $campus
            ], 201)
            ->header('Location', route('campuses.show', $campus->$primaryKeyName));
    }

    /**
     * PUT/PATCH /api/campus/{id}
     */
    public function update(CampusRequest $request, $id)
    {
        $campus = Campus::find($id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }

        $updated = $this->service->update($campus, $request->validated());

        return response()->json([
            'message' => 'Campus actualizado correctamente.',
            'data'    => $updated
        ], 200);
    }
    /**
     * DELETE /api/campus/{id}
     */
    public function destroy($id)
    {
        $campus = Campus::find($id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }

        try {
            $this->service->delete($campus);

            return response()->noContent(); // 204
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT'
                ], 409);
            }
            Log::error('Error deleting campus', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al eliminar.'], 500);
        }
    }
    /**
     * PATCH /api/campuses/{id}/active
     * Body JSON: { "active": true }
     * 
     * COMENTADO: Este método intentaba desactivar en cascada carreras a través de facultades,
     * pero:
     * 1. No existe la tabla FACULTAD ni el modelo Faculty en la aplicación
     * 2. Las carreras tienen relación N:M con sedes (CARRERA_SEDE)
     * 3. Una carrera puede estar en múltiples sedes, por lo que NO debe desactivarse
     *    en cascada al desactivar una sede específica
     * 
     * Si se necesita desactivar la relación específica sede-carrera, se debe trabajar
     * con la tabla pivote CARRERA_SEDE, no con la carrera directamente.
     */
    /*
    public function setActive(Request $request, $id)
    {
        $campus = Campus::find($id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $newActiveState = $validated['active'];

        // Actualizar el estado del campus
        $campus->activo = $newActiveState;
        $campus->save();

        // Aplicar cambio en cascada a todos los elementos hijos
        foreach ($campus->faculties as $faculty) {
            $faculty->activo = $newActiveState;
            $faculty->save();

            // Aplicar a carreras de la facultad
            foreach ($faculty->careers as $career) {
                $career->activo = $newActiveState;
                $career->save();
            }
        }

        $cascadeMessage = $newActiveState 
            ? ' Elementos hijos activados en cascada.' 
            : ' Elementos hijos desactivados en cascada.';
        $estadoAnterior = $campus->getOriginal('activo') ? 'ACTIVO' : 'INACTIVO';
        $estadoNuevo    = $newActiveState ? 'ACTIVO' : 'INACTIVO';

        AuditLogService::log(
            'editar',
            "Se actualizó el estado del campus \"{$campus->nombre}\" (ID: {$campus->campus_id}). " .
            "Estado anterior: {$estadoAnterior}. Estado actual: {$estadoNuevo}. " .
            "El cambio se aplicó también a sus facultades y carreras asociadas.",
            'Campus'
        );

        return response()->json([
            'message' => 'Estado del campus actualizado correctamente.' . $cascadeMessage,
            'data'    => $campus
        ], 200);
    }
    */
}
