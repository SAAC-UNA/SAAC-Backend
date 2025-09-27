<?php

namespace App\Http\Controllers;

use App\Models\Dimension;
<<<<<<< HEAD
use App\Http\Requests\StoreDimensionRequest;
use App\Http\Requests\UpdateDimensionRequest;

class DimensionController extends Controller
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
    public function store(StoreDimensionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Dimension $dimension)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dimension $dimension)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDimensionRequest $request, Dimension $dimension)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dimension $dimension)
    {
        //
    }
=======
use Illuminate\Database\QueryException;
use App\Services\DimensionService;
use App\Http\Requests\DimensionRequest;

class DimensionController extends Controller
{
    protected $service; // Service

    public function __construct(DimensionService $service) // Service
    {
        $this->service = $service;
    }
    /**
     * GET /api/dimensiones
     */
    public function index()
    {
        $items = $this->service->getAll(); // antes: Dimension::orderBy(...)
        return response()->json($items, 200);
    }

    /**
     * GET /api/dimensiones/{id}
     */
    public function show($id)
    {
         $dimension= $this->service->findById((int)$id); // antes: Dimension::find($id)
        if (!$dimension) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }
        return response()->json($dimension, 200);
    }

    /**
     * POST /api/dimensiones
     * Body JSON: { "comentario_id": 1, "nombre": "Gestión Académica", "nomenclatura": "D1" }
     */
    public function store(DimensionRequest $request)
    {
        $dimension = $this->service->create($request->validated());
        $primaryKeyName = $dimension->getKeyName();

        return response()
            ->json(['message' => 'Dimensión creada correctamente.','data' => $dimension], 201)
            ->header('Location', route('dimensiones.show', $dimension->$primaryKeyName));
    }
    /**
     * PUT/PATCH /api/dimensiones/{id}
     * Body JSON: { "comentario_id": 1, "nombre": "Gestión Académica y Curricular", "nomenclatura": "D1" }
     */
    public function update(DimensionRequest $request, $id)
    {
        $dimension = Dimension::find($id);
     if (!$dimension) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }

        $updated = $this->service->update($dimension, $request->validated());

        return response()->json(['message' => 'Dimensión actualizada correctamente.','data' => $updated], 200);
    }

    /**
     * DELETE /api/dimensiones/{id}
     */
    public function destroy($id)
     {
        $dimension = Dimension::find($id);
        if (!$dimension) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }

        try {
            $this->service->delete($dimension); // antes: $d->delete()
            return response()->noContent(); // 204
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: la dimensión tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT'
                ], 409);
            }
            return response()->json(['message' => 'Error al eliminar.','error' => $e->getMessage()], 500);
        }
    }
    
>>>>>>> 02_API_de_Endpoints_de_Estructura
}
