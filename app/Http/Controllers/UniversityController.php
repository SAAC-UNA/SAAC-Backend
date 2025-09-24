<?php

namespace App\Http\Controllers;

use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class UniversityController extends Controller
{
    /**
     * GET /api/universidades
     */
    public function index()
    {
        $items = University::orderBy('nombre')->get();
        return response()->json($items, 200);
    }

    /**
     * GET /api/universidades/{id}
     */
    public function show($id)
    {
        $u = University::find($id); // usa $primaryKey del modelo (universidad_id)
        if (!$u) {
            return response()->json(['message' => 'Universidad no encontrada.'], 404);
        }
        return response()->json($u, 200);
    }

    /**
     * POST /api/universidades
     * Body JSON: { "nombre": "UNA" }
     */
    public function store(Request $request)
    {
        // La tabla saldrá como 'UNIVERSIDAD' porque el modelo está configurado así
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

        $u  = University::create($validator->validated());
        $pk = $u->getKeyName(); // 'universidad_id'

        return response()
            ->json([
                'message' => 'Universidad creada correctamente.',
                'data'    => $u
            ], 201)
            // Usa la ruta nombrada del apiResource: universidades.show
            ->header('Location', route('universidades.show', $u->$pk));
    }

    /**
     * PUT/PATCH /api/universidades/{id}
     * Body JSON: { "nombre": "UCR" }
     */
    public function update(Request $request, $id)
    {
        $u = University::find($id);
        if (!$u) {
            return response()->json(['message' => 'Universidad no encontrada.'], 404);
        }

        $table = (new University)->getTable(); // 'UNIVERSIDAD'
        $pk    = $u->getKeyName();             // 'universidad_id'

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'required', 'string', 'max:250',
                Rule::unique($table, 'nombre')->ignore($u->$pk, $pk),
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

        $u->update($validator->validated());

        return response()->json([
            'message' => 'Universidad actualizada correctamente.',
            'data'    => $u
        ], 200);
    }

    /**
     * DELETE /api/universidades/{id}
     */
    public function destroy($id)
    {
        $u = University::find($id);
        if (!$u) {
            return response()->json(['message' => 'Universidad no encontrada.'], 404);
        }

        try {
            $u->delete();
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
