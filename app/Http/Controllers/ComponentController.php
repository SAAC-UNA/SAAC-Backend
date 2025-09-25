<?php

namespace App\Http\Controllers;

use App\Models\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class ComponentController extends Controller
{
    /**
     * GET /api/estructura/componentes
     */
    public function index()
    {
        $items = Component::with(['dimension', 'comment'])
            ->orderBy('nombre')
            ->get();

        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/componentes/{id}
     */
    public function show($id)
    {
        $c = Component::with(['dimension', 'comment'])->find($id);
        if (!$c) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }
        return response()->json($c, 200);
    }

    /**
     * POST /api/estructura/componentes
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'dimension_id'  => 'required|integer|exists:DIMENSION,dimension_id',
            'comentario_id' => 'nullable|integer|exists:COMENTARIO,comentario_id',
            'nombre'        => 'required|string|max:80',
            'nomenclatura'  => 'required|string|max:20',
        ], [
            'dimension_id.required'  => 'La dimensión es obligatoria.',
            'dimension_id.exists'    => 'La dimensión no existe.',
            'comentario_id.exists'   => 'El comentario no existe.',
            'nombre.required'        => 'El nombre es obligatorio.',
            'nomenclatura.required'  => 'La nomenclatura es obligatoria.',
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $c = Component::create($v->validated());

        return response()->json([
            'message'   => 'Componente creado correctamente.',
            'data'      => $c->load(['dimension','comment']),
        ], 201);
    }

    /**
     * PUT/PATCH /api/estructura/componentes/{id}
     * (permite actualización parcial)
     */
    public function update(Request $request, $id)
    {
        $c = Component::find($id);
        if (!$c) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }

        if (!$request->hasAny(['dimension_id','comentario_id','nombre','nomenclatura'])) {
            return response()->json([
                'message' => 'Debes enviar al menos un campo para actualizar.'
            ], 422);
        }

        $v = Validator::make($request->all(), [
            'dimension_id'  => 'sometimes|integer|exists:DIMENSION,dimension_id',
            'comentario_id' => 'nullable|integer|exists:COMENTARIO,comentario_id',
            'nombre'        => 'sometimes|string|max:80',
            'nomenclatura'  => 'sometimes|string|max:20',
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $c->fill($v->validated())->save();

        return response()->json([
            'message' => 'Componente actualizado correctamente.',
            'data'    => $c->load(['dimension','comment']),
        ], 200);
    }

    /**
     * DELETE /api/estructura/componentes/{id}
     */
    public function destroy($id)
    {
        $c = Component::find($id);
        if (!$c) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }

        try {
            $c->delete();
            return response()->noContent(); // 204
        } catch (QueryException $e) {
            // 1451: violación de FK (tiene hijos relacionados)
            if ((int)($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: el componente tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }
            return response()->json([
                'message' => 'Error al eliminar.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
