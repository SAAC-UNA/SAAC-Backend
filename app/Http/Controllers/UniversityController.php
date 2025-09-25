<?php

namespace App\Http\Controllers;

use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Services\UniversityService;

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
    public function store(Request $request)
    {
    $table = (new University)->getTable();

    $validator = Validator::make($request->all(), [
        'nombre' => ['required', 'string', 'max:250', Rule::unique($table, 'nombre')],
    ], [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.unique'   => 'Ya existe una universidad con ese nombre.',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Datos inválidos.',
            'errors'  => $validator->errors()
        ], 422);
    }

    $university = $this->service->create($validator->validated());

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
   public function update(Request $request, $id)
    {
    $university = University::find($id);

    if (!$university) {
        return response()->json(['message' => 'Universidad no encontrada.'], 404);
    }

    $table = (new University)->getTable(); // 'UNIVERSIDAD'

    $validator = Validator::make($request->all(), [
        'nombre' => [
            'required', 'string', 'max:250',
            Rule::unique($table, 'nombre')->ignore($university->universidad_id, 'universidad_id'),
        ],
    ], [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.unique'   => 'Ya existe otra universidad con ese nombre.',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Datos inválidos.',
            'errors'  => $validator->errors()
        ], 422);
    }

    // Ahora se usa el service
    $updatedUniversity = $this->service->update($university, $validator->validated());

    return response()->json([
        'message' => 'Universidad actualizada correctamente.',
        'data'    => $updatedUniversity
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
