<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Http\Resources\EvidenceResource;

class EvidenceController extends Controller
{
    /**
     * GET /api/estructura/evidencias
     */
    public function index()
    {
        // igual que en Criterion: listado simple, sin with()
        $items = Evidence::orderBy('nomenclatura')->get();

        return EvidenceResource::collection($items)->response(); // 200
    }

    /**
     * GET /api/estructura/evidencias/{id}
     */
    public function show($id)
    {
        $e = Evidence::find($id);
        if (!$e) return response()->json(['message' => 'Evidencia no encontrada.'], 404);

        return EvidenceResource::make($e)->response(); // 200
    }

    /**
     * POST /api/estructura/evidencias
     */
    public function store(Request $request)
    {
        $table = (new Evidence)->getTable(); // 'EVIDENCIA'

        // Regla de unicidad sugerida: nomenclatura única por criterio
        $v = Validator::make($request->all(), [
            'criterio_id'          => ['required','integer','exists:CRITERIO,criterio_id'],
            'estado_evidencia_id'  => ['required','integer','exists:ESTADO_EVIDENCIA,estado_evidencia_id'],
            'descripcion'          => ['required','string','max:80'],
            'nomenclatura'         => [
                'required','string','max:20',
                Rule::unique($table, 'nomenclatura')->where(function($q) use ($request) {
                    return $q->where('criterio_id', $request->input('criterio_id'));
                }),
            ],
        ], [
            'criterio_id.required'         => 'El criterio es obligatorio.',
            'criterio_id.exists'           => 'El criterio no existe.',
            'estado_evidencia_id.required' => 'El estado es obligatorio.',
            'estado_evidencia_id.exists'   => 'El estado no existe.',
            'descripcion.required'         => 'La descripción es obligatoria.',
            'nomenclatura.required'        => 'La nomenclatura es obligatoria.',
            'nomenclatura.unique'          => 'Ya existe una evidencia con esa nomenclatura en este criterio.',
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $v->errors()], 422);
        }

        $e  = Evidence::create($v->validated());

        return EvidenceResource::make($e)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/estructura/evidencias/{id}
     */
    public function update(Request $request, $id)
    {
        $e = Evidence::find($id);
        if (!$e) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        if (!$request->hasAny(['criterio_id','estado_evidencia_id','descripcion','nomenclatura'])) {
            return response()->json(['message' => 'Debes enviar al menos un campo para actualizar.'], 422);
        }

        $table = (new Evidence)->getTable();
        $pk    = $e->getKeyName(); // 'evidencia_id'
        // si no envían criterio_id, la unicidad se valida contra el que ya tiene
        $criterioId = $request->input('criterio_id', $e->criterio_id);

        $v = Validator::make($request->all(), [
            'criterio_id'          => ['sometimes','integer','exists:CRITERIO,criterio_id'],
            'estado_evidencia_id'  => ['sometimes','integer','exists:ESTADO_EVIDENCIA,estado_evidencia_id'],
            'descripcion'          => ['sometimes','string','max:80'],
            'nomenclatura'         => [
                'sometimes','string','max:20',
                Rule::unique($table, 'nomenclatura')
                    ->where(fn($q) => $q->where('criterio_id', $criterioId))
                    ->ignore($e->$pk, $pk),
            ],
        ], [
            'criterio_id.exists'         => 'El criterio no existe.',
            'estado_evidencia_id.exists' => 'El estado no existe.',
            'nomenclatura.unique'        => 'Ya existe una evidencia con esa nomenclatura en este criterio.',
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $v->errors()], 422);
        }

        $e->fill($v->validated())->save();

        return EvidenceResource::make($e)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * DELETE /api/estructura/evidencias/{id}
     */
    public function destroy($id)
    {
        $e = Evidence::find($id);
        if (!$e) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        try {
            $e->delete();
            return response()->noContent(); // 204
        } catch (QueryException $qe) {
            // 1451: FK constraint
            if ((int)($qe->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: la evidencia tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }
            return response()->json(['message' => 'Error al eliminar.', 'error' => $qe->getMessage()], 500);
        }
    }
}
