<?php

namespace App\Http\Controllers;

use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class CareerController extends Controller
{
    /**
     * GET /api/estructura/carreras?facultad_id=#
     */
    public function index(Request $request)
    {
        $q = Career::query()
            ->with('faculty')
            ->orderBy('nombre');

        if ($request->filled('facultad_id')) {
            $q->where('facultad_id', (int) $request->input('facultad_id'));
        }

        return response()->json($q->get(), 200);
    }

    /**
     * GET /api/estructura/carreras/{id}
     */
    public function show($id)
    {
        $car = Career::with('faculty')->find($id);
        if (!$car) {
            return response()->json(['message' => 'Carrera no encontrada.'], 404);
        }
        return response()->json($car, 200);
    }

    /**
     * POST /api/estructura/carreras
     * { "nombre":"Ing. Sistemas", "facultad_id":1 }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'       => ['required','string','max:250'],
            'facultad_id'  => ['required','integer','exists:FACULTAD,facultad_id'],
        ], [
            'nombre.required'      => 'El nombre es obligatorio.',
            'facultad_id.required' => 'La facultad es obligatoria.',
            'facultad_id.exists'   => 'La facultad no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=>'Datos inválidos.','errors'=>$validator->errors()], 422);
        }

        $car = Career::create($validator->validated());
        $pk  = $car->getKeyName(); // 'carrera_id'

        return response()
            ->json(['message'=>'Carrera creada correctamente.','data'=>$car], 201)
            ->header('Location', route('carreras.show', $car->$pk));
    }

    /**
     * PUT/PATCH /api/estructura/carreras/{id}
     */
    public function update(Request $request, $id)
    {
        $car = Career::find($id);
        if (!$car) {
            return response()->json(['message' => 'Carrera no encontrada.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre'       => ['required','string','max:250'],
            'facultad_id'  => ['required','integer','exists:FACULTAD,facultad_id'],
        ], [
            'nombre.required'      => 'El nombre es obligatorio.',
            'facultad_id.required' => 'La facultad es obligatoria.',
            'facultad_id.exists'   => 'La facultad no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=>'Datos inválidos.','errors'=>$validator->errors()], 422);
        }

        $car->update($validator->validated());

        return response()->json(['message'=>'Carrera actualizada correctamente.','data'=>$car], 200);
    }

    /**
     * DELETE /api/estructura/carreras/{id}
     */
    public function destroy($id)
    {
        $car = Career::find($id);
        if (!$car) {
            return response()->json(['message' => 'Carrera no encontrada.'], 404);
        }

        try {
            $car->delete();
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
}
