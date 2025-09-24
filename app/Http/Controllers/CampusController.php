<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
//use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class CampusController extends Controller
{
    /**
     * GET /api/campus?universidad_id=#
     */
    public function index(Request $request)
    {
        $q = Campus::query()->with('university')->orderBy('nombre');

        if ($request->filled('universidad_id')) {
            $q->where('universidad_id', (int) $request->input('universidad_id'));
        }

        return response()->json($q->get(), 200);
    }

    /**
     * GET /api/campus/{id}
     */
    public function show($id)
    {
        $campus = Campus::with('university')->find($id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }
        return response()->json($campus, 200);
    }

    /**
     * POST /api/campus
     * { "nombre":"Occidente", "universidad_id":1 }
     */
    public function store(Request $request)
    {
        $table = (new Campus)->getTable(); // 'SEDE'

        $validator = Validator::make($request->all(), [
            'nombre'         => ['required','string','max:250'],
            'universidad_id' => ['required','integer','exists:UNIVERSIDAD,universidad_id'],
            // Opcional: evitar duplicados dentro de la misma universidad
            // Rule::unique($table, 'nombre')->where(fn($q)=>$q->where('universidad_id',$request->universidad_id)),
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'universidad_id.required' => 'La universidad es obligatoria.',
            'universidad_id.exists'   => 'La universidad no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message'=>'Datos inválidos.',
                'errors'=>$validator->errors()
            ], 422);
        }

        $campus = Campus::create($validator->validated());
        $pk     = $campus->getKeyName(); // 'sede_id'

        return response()
            ->json(['message'=>'Campus creado correctamente.','data'=>$campus], 201)
            ->header('Location', route('campuses.show', $campus->$pk));
    }

    /**
     * PUT/PATCH /api/campus/{id}
     */
    public function update(Request $request, $id)
    {
        $campus = Campus::find($id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }

        $table = (new Campus)->getTable();
        $pk    = $campus->getKeyName();

        $validator = Validator::make($request->all(), [
            'nombre'         => ['required','string','max:250'],
            'universidad_id' => ['required','integer','exists:UNIVERSIDAD,universidad_id'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'universidad_id.required' => 'La universidad es obligatoria.',
            'universidad_id.exists'   => 'La universidad no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message'=>'Datos inválidos.',
                'errors'=>$validator->errors()
            ], 422);
        }

        $campus->update($validator->validated());

        return response()->json(['message'=>'Campus actualizado correctamente.','data'=>$campus], 200);
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
            $campus->delete();
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
