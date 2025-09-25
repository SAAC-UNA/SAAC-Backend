<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Http\Resources\CriterionResource;

class CriterionController extends Controller
{
    /**
     * GET /api/estructura/criterios
     */
   public function index()
    {
    $items = Criterion::orderBy('nomenclatura')->get(); // sin with()
    return CriterionResource::collection($items)->response(); // 200
    }   

    /**
     * GET /api/estructura/criterios/{id}
     */
   public function show($id)
{
    $c = \App\Models\Criterion::find($id);
    if (!$c) return response()->json(['message' => 'Criterio no encontrado.'], 404);

    return CriterionResource::make($c)->response(); // 200
}
    /**
     * POST /api/estructura/criterios
     */
    public function store(Request $request)
    {
        $table = (new Criterion)->getTable(); // 'CRITERIO'

        $v = Validator::make($request->all(), [
            'componente_id'  => ['required','integer','exists:COMPONENTE,componente_id'],
            'comentario_id'  => ['nullable','integer','exists:COMENTARIO,comentario_id'],
            'descripcion'    => ['required','string','max:300'],
            'nomenclatura'   => [
                'required','string','max:20',
                Rule::unique($table, 'nomenclatura')->where(function($q) use ($request) {
                    return $q->where('componente_id', $request->input('componente_id'));
                }),
            ],
        ], [
            'componente_id.required' => 'El componente es obligatorio.',
            'componente_id.exists'   => 'El componente no existe.',
            'comentario_id.exists'   => 'El comentario no existe.',
            'descripcion.required'   => 'La descripción es obligatoria.',
            'nomenclatura.required'  => 'La nomenclatura es obligatoria.',
            'nomenclatura.unique'    => 'Ya existe un criterio con esa nomenclatura en este componente.',
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $v->errors()], 422);
        }

        $c  = Criterion::create($v->validated());
        $pk = $c->getKeyName(); // 'criterio_id'

        // Respuesta “limpia” usando Resource, sin relaciones ni Location
    return CriterionResource::make($c)
        ->response()
        ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/estructura/criterios/{id}
     */
    public function update(Request $request, $id)
    {
        $c = Criterion::find($id);
        if (!$c) {
            return response()->json(['message' => 'Criterio no encontrado.'], 404);
        }

        if (!$request->hasAny(['componente_id','comentario_id','descripcion','nomenclatura'])) {
            return response()->json(['message' => 'Debes enviar al menos un campo para actualizar.'], 422);
        }

        $table  = (new Criterion)->getTable();
        $pk     = $c->getKeyName();
        $compId = $request->input('componente_id', $c->componente_id);

        $v = Validator::make($request->all(), [
            'componente_id'  => ['sometimes','integer','exists:COMPONENTE,componente_id'],
            'comentario_id'  => ['nullable','integer','exists:COMENTARIO,comentario_id'],
            'descripcion'    => ['sometimes','string','max:300'],
            'nomenclatura'   => [
                'sometimes','string','max:20',
                Rule::unique($table, 'nomenclatura')
                    ->where(fn($q) => $q->where('componente_id', $compId))
                    ->ignore($c->$pk, $pk),
            ],
        ], [
            'componente_id.exists' => 'El componente no existe.',
            'comentario_id.exists' => 'El comentario no existe.',
            'nomenclatura.unique'  => 'Ya existe un criterio con esa nomenclatura en este componente.',
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $v->errors()], 422);
        }

        $c->fill($v->validated())->save();

        return CriterionResource::make($c)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * DELETE /api/estructura/criterios/{id}
     */
    public function destroy($id)
    {
        $c = Criterion::find($id);
        if (!$c) {
            return response()->json(['message' => 'Criterio no encontrado.'], 404);
        }

        try {
            $c->delete();
            return response()->noContent(); // 204
        } catch (QueryException $e) {
            // 1451: violación de FK
            if ((int)($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: el criterio tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }
            return response()->json(['message' => 'Error al eliminar.', 'error' => $e->getMessage()], 500);
        }
    }
}
