<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccreditationCycleRequest;
use App\Services\AccreditationCycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccreditationCycleController extends Controller
{
    protected AccreditationCycleService $cycleService;

    public function __construct(AccreditationCycleService $cycleService)
    {
        $this->cycleService = $cycleService;
    }

    /**
     * 📋 Listar todos los ciclos de acreditación.
     */
    public function index(): JsonResponse
    {
        $cycles = $this->cycleService->getAll();
        return response()->json($cycles, 200);
    }

    /**
     * 👁 Mostrar un ciclo específico por ID.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $cycle = $this->cycleService->getById($id);
            return response()->json($cycle, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Ciclo no encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener el ciclo.'], 500);
        }
    }

    /**
     * ➕ Crear un nuevo ciclo de acreditación.
     */
    public function store(AccreditationCycleRequest $request): JsonResponse
    {
        try {
            $cycle = $this->cycleService->create($request->validated());

            return response()->json([
                'message' => 'Ciclo de acreditación creado correctamente.',
                'data' => $cycle
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo crear el ciclo.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✏️ Actualizar un ciclo existente.
     */
    public function update(AccreditationCycleRequest $request, int $id): JsonResponse
    {
        try {
            $cycle = $this->cycleService->update($id, $request->validated());

            return response()->json([
                'message' => 'Ciclo de acreditación actualizado correctamente.',
                'data' => $cycle
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Ciclo no encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo actualizar el ciclo.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑 Eliminar un ciclo de acreditación.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->cycleService->delete($id);

            return response()->json([
                'message' => 'Ciclo de acreditación eliminado correctamente.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Ciclo no encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo eliminar el ciclo.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
