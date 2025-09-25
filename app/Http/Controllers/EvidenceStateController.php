<?php

namespace App\Http\Controllers;

use App\Models\EvidenceState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class EvidenceStateController extends Controller
{
    /**
     * GET /api/estructura/estados-evidencia
     */
    public function index()
    {
        $items = EvidenceState::orderBy('nombre')->get();
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/estados-evidencia/{id}
     */
    public function show($id)
    {
        $estado = EvidenceState::find($id);
        if (!$estado) {
            return response()->json(['message' => 'Estado no encontrado.'], 404);
        }
        return response()->json($estado, 200);
    }

    /**
     * POST /api/estructura/estados-evidencia
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:100', 'unique:ESTADO_EVIDENCIA,nombre'],
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        try {
            $estado = EvidenceState::create([
                'nombre' => $request->nombre,
            ]);
            return response()->json($estado, 201);
        } catch (QueryException $ex) {
            return response()->json(['message' => 'Error al crear el estado.'], 500);
        }
    }

    /**
     * PUT /api/estructura/estados-evidencia/{id}
     */
    public function update(Request $request, $id)
    {
        $estado = EvidenceState::find($id);
        if (!$estado) {
            return response()->json(['message' => 'Estado no encontrado.'], 404);
        }

        if (!$request->has('nombre')) {
            return response()->json(['message' => 'Debes enviar el campo nombre.'], 422);
        }

        $v = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'string', 'max:100', 'unique:ESTADO_EVIDENCIA,nombre,'.$id.',estado_evidencia_id'],
        ]);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        try {
            $estado->update([
                'nombre' => $request->input('nombre', $estado->nombre),
            ]);
            return response()->json($estado, 200);
        } catch (QueryException $ex) {
            return response()->json(['message' => 'Error al actualizar el estado.'], 500);
        }
    }

    /**
     * DELETE /api/estructura/estados-evidencia/{id}
     */
    public function destroy($id)
    {
        $estado = EvidenceState::find($id);
        if (!$estado) {
            return response()->json(['message' => 'Estado no encontrado.'], 404);
        }

        try {
            $estado->delete();
            return response()->json(['message' => 'Eliminado.'], 200);
        } catch (QueryException $ex) {
            return response()->json(['message' => 'No se puede eliminar: está en uso.'], 409);
        }
    }
}
