<?php

namespace App\Http\Controllers;

use App\Models\AccreditationCycle;
use App\Services\AccreditationCycleService;
use App\Http\Requests\AccreditationCycleRequest;
use Illuminate\Database\QueryException;

class AccreditationCycleController extends Controller
{
    protected $service;

    public function __construct(AccreditationCycleService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/ciclos
     * AC-3: El filtro por rol se aplica automáticamente via BaseCareer
     */
    public function index()
    {
        $cycles = $this->service->getAll();

        return response()->json($cycles, 200);
    }

    /**
     * GET /api/ciclos/{id}
     */
    public function show($id)
    {
        $cycle = $this->service->findById((int)$id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        return response()->json($cycle, 200);
    }

    /**
     * POST /api/ciclos
     * AC-1: Crear ciclo con nombre, carrera_sede y estado
     * AC-2: Validaciones en AccreditationCycleRequest
     */
    public function store(AccreditationCycleRequest $request)
    {
        $cycle = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Ciclo de acreditación creado correctamente.',
            'data'    => $cycle,
        ], 201);
    }

    /**
     * PUT/PATCH /api/ciclos/{id}
     * AC-4: Bloquea edición si el ciclo no está activo
     */
    public function update(AccreditationCycleRequest $request, $id)
    {
        $cycle = AccreditationCycle::find($id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        if (!$cycle->isEditable()) {
            return response()->json([
                'message' => 'No se puede modificar un ciclo inactivo o completado.'
            ], 403);
        }

        $updated = $this->service->update($cycle, $request->validated());

        return response()->json([
            'message' => 'Ciclo de acreditación actualizado correctamente.',
            'data'    => $updated,
        ], 200);
    }

    /**
     * DELETE /api/ciclos/{id}
     * AC-4: Bloquea eliminación si tiene procesos asociados
     */
    public function destroy($id)
    {
        $cycle = AccreditationCycle::find($id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        try {
            $this->service->delete($cycle);

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 409);
        }
    }
}