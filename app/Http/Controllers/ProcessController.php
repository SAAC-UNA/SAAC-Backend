<?php

namespace App\Http\Controllers;

use App\Services\ProcessService;
use App\Http\Requests\ProcessRequest;

class ProcessController extends Controller
{
    protected $processService;

    public function __construct(ProcessService $processService)
    {
        $this->processService = $processService;
    }

    /**
     *  Listar todos los procesos (sin autenticación)
     */
    public function index()
    {
        $procesos = $this->processService->getAll();
        return response()->json($procesos, 200);
    }

    /**
     * 👁 Mostrar un proceso por su ID
     */
    public function show($id)
    {
        try {
            $proceso = $this->processService->getById($id);
            return response()->json($proceso, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Proceso no encontrado'], 404);
        }
    }

    /**
     * ➕ Crear un nuevo proceso
     */
    public function store(ProcessRequest $request)
    {
        $proceso = $this->processService->create($request->validated());

        return response()->json([
            'message' => 'Proceso creado correctamente',
            'data' => $proceso
        ], 201);
    }

    /**
     * ✏️ Actualizar un proceso existente
     */
    public function update(ProcessRequest $request, $id)
    {
        $updated = $this->processService->update($id, $request->validated());

        return response()->json([
            'message' => 'Proceso actualizado correctamente',
            'data' => $updated
        ], 200);
    }

    /**
     * 🗑 Eliminar un proceso
     */
    public function destroy($id)
    {
        $this->processService->delete($id);

        return response()->json(['message' => 'Proceso eliminado correctamente'], 200);
    }
}
