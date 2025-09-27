<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Models\university;
use App\Http\Requests\StoreuniversityRequest;
use App\Http\Requests\UpdateuniversityRequest;

class UniversityController extends Controller
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
    public function store(StoreuniversityRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(university $university)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(university $university)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateuniversityRequest $request, university $university)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(university $university)
    {
        //
    }
}
=======
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Services\UniversityService;
use App\Http\Requests\UniversityRequest;

class UniversityController extends Controller
{
    protected $service;

    public function __construct(UniversityService $service)
    {
        $this->service = $service;
    }
    /**
     * GET /api/universidades
     */
    public function index()
    {
        $items = $this->service->getAll();
        return response()->json($items, 200);
    }

    /**
     * GET /api/universidades/{id}
     */
    public function show($id)
    {
        $university = $this->service->findById($id);

        if (!$university) {
        return response()->json(['message' => 'Universidad no encontrada.'], 404);
        }
        return response()->json($university, 200);
    }

    /**
     * POST /api/universidades
     * Body JSON: { "nombre": "UNA" }
     */
    public function store(UniversityRequest $request)
    {
    $university = $this->service->create($request->validated());

    return response()
        ->json([
            'message' => 'Universidad creada correctamente.',
            'data'    => $university
        ], 201)
        ->header('Location', route('universidades.show', $university->universidad_id));

    }

    /**
     * PUT/PATCH /api/universidades/{id}
     * Body JSON: { "nombre": "UCR" }
     */
   public function update(UniversityRequest $request, $id)
    {
    $university = University::find($id);
    if (!$university) {
        return response()->json(['message' => 'Universidad no encontrada.'], 404);
    }

    // Datos ya validados por el FormRequest (incluye la regla unique con ignore)
    $updated = $this->service->update($university, $request->validated());

    return response()->json([
        'message' => 'Universidad actualizada correctamente.',
        'data'    => $updated
    ], 200);
    }

    /**
     * DELETE /api/universidades/{id}
     */
    public function destroy($id)
{
    $university = University::find($id);
    if (!$university) {
        return response()->json(['message' => 'Universidad no encontrada.'], 404);
    }

    try {
        // ahora elimina el service
        $this->service->delete($university);
        return response()->noContent(); // 204
    } catch (QueryException $e) {
        // Error 1451: violación de FK (registro relacionado)
        if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
            return response()->json([
                'message' => 'No se puede eliminar: la universidad tiene registros relacionados.',
                'code'    => 'FK_CONSTRAINT'
            ], 409);
        }
        return response()->json([
            'message' => 'Error al eliminar.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

}
>>>>>>> 02_API_de_Endpoints_de_Estructura
