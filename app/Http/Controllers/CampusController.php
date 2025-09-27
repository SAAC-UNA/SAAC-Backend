<?php

namespace App\Http\Controllers;

use App\Models\Campus;
<<<<<<< HEAD
use App\Http\Requests\StoreCampusRequest;
use App\Http\Requests\UpdateCampusRequest;

class CampusController extends Controller
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
    public function store(StoreCampusRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Campus $campus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Campus $campus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCampusRequest $request, Campus $campus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Campus $campus)
    {
        //
=======
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Services\CampusService;
use App\Http\Requests\CampusRequest;

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
        $universidadId = $request->filled('universidad_id')
            ? (int) $request->input('universidad_id')
            : null;

        $items = $this->service->getAll($universidadId);
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
            // antes: $campus->delete()
            // ahora: service->delete()
            $this->service->delete($campus);
            return response()->noContent(); // 204
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT'
                ], 409);
            }
            return response()->json(['message'=>'Error al eliminar.','error'=>$e->getMessage()], 500);
        }

>>>>>>> 02_API_de_Endpoints_de_Estructura
    }
}
