<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class FacultyController extends Controller
{
    /**
     * GET /api/estructura/facultades?universidad_id=#&sede_id=#
     */
    public function index(Request $request)
    {
        $q = Faculty::query()
            ->with(['university','campus'])
            ->orderBy('nombre');

        if ($request->filled('universidad_id')) {
            $q->where('universidad_id', (int) $request->input('universidad_id'));
        }
        if ($request->filled('sede_id')) {
            $q->where('sede_id', (int) $request->input('sede_id'));
        }

        return response()->json($q->get(), 200);
    }

    /**
     * GET /api/estructura/facultades/{id}
     */
    public function show($id)
    {
        $fac = Faculty::with(['university','campus'])->find($id);
        if (!$fac) {
            return response()->json(['message' => 'Facultad no encontrada.'], 404);
        }
        return response()->json($fac, 200);
    }

    /**
     * POST /api/estructura/facultades
     * { "nombre":"Ciencias", "universidad_id":1, "sede_id":1 }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'         => ['required','string','max:250'],
            'universidad_id' => ['required','integer','exists:UNIVERSIDAD,universidad_id'],
            'sede_id'        => ['required','integer','exists:SEDE,sede_id'],
        ], [
            'nombre.required'         => 'El nombre es obligatorio.',
            'universidad_id.required' => 'La universidad es obligatoria.',
            'universidad_id.exists'   => 'La universidad no existe.',
            'sede_id.required'        => 'La sede es obligatoria.',
            'sede_id.exists'          => 'La sede no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=>'Datos inválidos.','errors'=>$validator->errors()], 422);
        }

        $fac = Faculty::create($validator->validated());
        $pk  = $fac->getKeyName(); // 'facultad_id'

        return response()
            ->json(['message'=>'Facultad creada correctamente.','data'=>$fac], 201)
            ->header('Location', route('facultades.show', $fac->$pk));
    }

    /**
     * PUT/PATCH /api/estructura/facultades/{id}
     */
    public function update(Request $request, $id)
    {
        $fac = Faculty::find($id);
        if (!$fac) {
            return response()->json(['message' => 'Facultad no encontrada.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre'         => ['required','string','max:250'],
            'universidad_id' => ['required','integer','exists:UNIVERSIDAD,universidad_id'],
            'sede_id'        => ['required','integer','exists:SEDE,sede_id'],
        ], [
            'nombre.required'         => 'El nombre es obligatorio.',
            'universidad_id.required' => 'La universidad es obligatoria.',
            'universidad_id.exists'   => 'La universidad no existe.',
            'sede_id.required'        => 'La sede es obligatoria.',
            'sede_id.exists'          => 'La sede no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=>'Datos inválidos.','errors'=>$validator->errors()], 422);
        }

        $fac->update($validator->validated());

        return response()->json(['message'=>'Facultad actualizada correctamente.','data'=>$fac], 200);
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
            $fac->delete();
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
