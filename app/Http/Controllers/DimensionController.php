<?php

namespace App\Http\Controllers;

use App\Models\Dimension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class DimensionController extends Controller
{
    /**
     * GET /api/dimensiones
     */
    public function index()
    {
        $items = Dimension::orderBy('nomenclatura')->get();
        return response()->json($items, 200);
    }

    /**
     * GET /api/dimensiones/{id}
     */
    public function show($id)
    {
        $d = Dimension::find($id); // usa $primaryKey del modelo (dimension_id)
        if (!$d) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }
        return response()->json($d, 200);
    }

    /**
     * POST /api/dimensiones
     * Body JSON: { "comentario_id": 1, "nombre": "Gestión Académica", "nomenclatura": "D1" }
     */
    public function store(Request $request)
    {
        $table = (new Dimension)->getTable(); // 'DIMENSION'

        $validator = Validator::make($request->all(), [
            'comentario_id' => ['required','integer','exists:COMENTARIO,comentario_id'],
            'nombre'        => ['required','string','max:100'],
            'nomenclatura'  => ['required','string','max:20', Rule::unique($table, 'nomenclatura')],
        ], [
            'comentario_id.required' => 'El comentario es obligatorio.',
            'comentario_id.exists'   => 'El comentario no existe.',
            'nombre.required'        => 'El nombre es obligatorio.',
            'nomenclatura.required'  => 'La nomenclatura es obligatoria.',
            'nomenclatura.unique'    => 'Ya existe una dimensión con esa nomenclatura.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $d  = Dimension::create($validator->validated());
        $pk = $d->getKeyName(); // 'dimension_id'

        return response()
            ->json([
                'message' => 'Dimensión creada correctamente.',
                'data'    => $d
            ], 201)
            ->header('Location', route('dimensiones.show', $d->$pk));
    }

    /**
     * PUT/PATCH /api/dimensiones/{id}
     * Body JSON: { "comentario_id": 1, "nombre": "Gestión Académica y Curricular", "nomenclatura": "D1" }
     */
    public function update(Request $request, $id)
    {
        $d = Dimension::find($id);
        if (!$d) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }

        $table = (new Dimension)->getTable(); // 'DIMENSION'
        $pk    = $d->getKeyName();            // 'dimension_id'

        $validator = Validator::make($request->all(), [
            'comentario_id' => ['sometimes','integer','exists:COMENTARIO,comentario_id'],
            'nombre'        => ['sometimes','string','max:100'],
            'nomenclatura'  => [
                'sometimes','string','max:20',
                Rule::unique($table, 'nomenclatura')->ignore($d->$pk, $pk),
            ],
        ], [
            'comentario_id.exists'  => 'El comentario no existe.',
            'nomenclatura.unique'   => 'Ya existe otra dimensión con esa nomenclatura.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $d->update($validator->validated());

        return response()->json([
            'message' => 'Dimensión actualizada correctamente.',
            'data'    => $d
        ], 200);
    }

    /**
     * DELETE /api/dimensiones/{id}
     */
    public function destroy($id)
     {
        $d = Dimension::find($id);
        if (!$d) {
            return response()->json(['message' => 'Dimensión no encontrada.'], 404);
        }

        try {
            $d->delete();
            return response()->noContent(); // 204
        } catch (QueryException $e) {
            // Error 1451: violación de FK
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: la dimensión tiene registros relacionados.',
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
