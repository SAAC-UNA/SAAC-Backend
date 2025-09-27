<?php

namespace App\Http\Controllers;

use App\Models\EvidenceState;
<<<<<<< HEAD
use App\Http\Requests\StoreEvidenceStateRequest;
use App\Http\Requests\UpdateEvidenceStateRequest;

class EvidenceStateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEvidenceStateRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EvidenceState $evidenceState)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EvidenceState $evidenceState)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEvidenceStateRequest $request, EvidenceState $evidenceState)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EvidenceState $evidenceState)
    {
        //
=======
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Services\EvidenceStateService;
use App\Http\Requests\EvidenceStateRequest; 

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
        return response()->noContent(); // 204 No Content
    } catch (QueryException $ex) {
        return response()->json(['message' => 'No se puede eliminar: está en uso.'], 409);
    }
>>>>>>> 02_API_de_Endpoints_de_Estructura
    }
}
