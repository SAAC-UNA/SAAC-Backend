<?php

namespace App\Http\Controllers;

use App\Models\Standard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class StandardController extends Controller
{
    /**
     * GET /api/estructura/estandares
     */
    public function index()
    {
        $items = Standard::orderBy('estandar_id')->get();
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/estandares/{id}
     */
    public function show($id)
    {
        $std = Standard::find($id);
        if (!$std) {
            return response()->json(['message' => 'Estándar no encontrado.'], 404);
        }
        return response()->json($std, 200);
    }

    /**
     * POST /api/estructura/estandares
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'criterio_id' => ['required','integer','exists:CRITERIO,criterio_id'],
            'descripcion' => ['required','string','max:250'],
            // Si quieres evitar duplicados por criterio+descripcion, descomenta:
            // Rule::unique('ESTANDAR','descripcion')->where(fn($q)=>$q->where('criterio_id',$request->criterio_id)),
        ]);

        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        try {
            $std = Standard::create([
                'criterio_id' => $request->criterio_id,
                'descripcion' => $request->descripcion,
            ]);
            return response()->json($std, 201);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error al crear el estándar.'], 500);
        }
    }

    /**
     * PUT /api/estructura/estandares/{id}
     */
    public function update(Request $request, $id)
    {
        $std = Standard::find($id);
        if (!$std) return response()->json(['message'=>'Estándar no encontrado.'], 404);

        if (!$request->hasAny(['criterio_id','descripcion'])) {
            return response()->json(['message'=>'Debes enviar al menos un campo para actualizar.'], 422);
        }

        $v = Validator::make($request->all(), [
            'criterio_id' => ['sometimes','integer','exists:CRITERIO,criterio_id'],
            'descripcion' => ['sometimes','string','max:250'],
            // Si usas la unicidad por par, descomenta y ajusta:
            // Rule::unique('ESTANDAR','descripcion')
            //     ->ignore($std->estandar_id,'estandar_id')
            //     ->where(fn($q)=>$q->where('criterio_id',$request->input('criterio_id',$std->criterio_id))),
        ]);

        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        try {
            $std->update([
                'criterio_id' => $request->input('criterio_id', $std->criterio_id),
                'descripcion' => $request->input('descripcion', $std->descripcion),
            ]);
            return response()->json($std, 200);
        } catch (QueryException $e) {
            return response()->json(['message'=>'Error al actualizar el estándar.'], 500);
        }
    }

    /**
     * DELETE /api/estructura/estandares/{id}
     */
    public function destroy($id)
    {
        $std = Standard::find($id);
        if (!$std) return response()->json(['message'=>'Estándar no encontrado.'], 404);

        try {
            $std->delete();
            return response()->json(['message'=>'Eliminado.'], 200);
        } catch (QueryException $e) {
            return response()->json(['message'=>'No se puede eliminar.'], 409);
        }
    }
}
