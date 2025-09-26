<?php

namespace App\Http\Controllers;

use App\Models\Standard;
use Illuminate\Database\QueryException;
use App\Services\StandardService;
use App\Http\Requests\StandardRequest;



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
        if (!$standar) return response()->json(['message'=>'Estándar no encontrado.'], 404);

        try {
            $standar= $this->service->update($standar, $request->validated());
            return response()->json($standar, 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message'=>'Error al actualizar el estándar.'], 500);
        }
    }

    /**
     * DELETE /api/estructura/estandares/{id}
     */
    public function destroy($id)
    {
        
        $standar = Standard::find($id);
        if (!$standar) return response()->json(['message'=>'Estándar no encontrado.'], 404);

        try {
            $this->service->delete($standar); // antes: $std->delete()
            return response()->noContent(); //204
        } catch (QueryException $e) {
            return response()->json(['message'=>'No se puede eliminar.'], 409);
        }
    }
}
