<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
<<<<<<< HEAD
use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;

class FacultyController extends Controller
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
    public function store(StoreFacultyRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Faculty $faculty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Faculty $faculty)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faculty $faculty)
    {
        //
    }
=======
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Services\FacultyService;
use App\Http\Requests\FacultyRequest;

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
        $faculty= $this->service->create($request->validated());
        $primaryKeyName = $faculty->getKeyName();

        return response()
        ->json(['message'=>'Facultad creada correctamente.','data'=>$faculty], 201)
        ->header('Location', route('facultades.show', $faculty->$primaryKeyName));
    }
    /**
     * PUT/PATCH /api/estructura/facultades/{id}
     */
   public function update(FacultyRequest $request, $id)
    {
        $faculty= Faculty::find($id);
        if (!$faculty) {
            return response()->json(['message' => 'Facultad no encontrada.'], 404);
        }

        $updated = $this->service->update($faculty, $request->validated());

        return response()->json(['message'=>'Facultad actualizada correctamente.','data'=>$updated], 200);
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
    }   
>>>>>>> 02_API_de_Endpoints_de_Estructura
}
