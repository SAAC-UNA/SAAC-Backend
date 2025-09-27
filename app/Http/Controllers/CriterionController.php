<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Http\Resources\CriterionResource;
use App\Services\CriterionService;
use App\Http\Requests\CriterionRequest;

class CriterionController extends Controller
{
    protected $service; // Service

    public function __construct(CriterionService $service)
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/criterios
     */
    public function index()
    {
        $items = $this->service->getAll();
        return CriterionResource::collection($items)->response(); // 200
    }

    /**
     * GET /api/estructura/criterios/{id}
     */
    public function show($id)
    {
        $criterion = $this->service->findById((int)$id);
        if (!$criterion) return response()->json(['message' => 'Criterio no encontrado.'], 404);

        return CriterionResource::make($criterion)->response(); // 200
    }
    /**
     * POST /api/estructura/criterios
     */
    public function store(CriterionRequest $request)
    {
        $criterion = $this->service->create($request->validated());

        return \App\Http\Resources\CriterionResource::make($criterion)
            ->response()
            ->setStatusCode(201);
    }
    /**
     * PUT/PATCH /api/estructura/criterios/{id}
     */
    public function update(CriterionRequest $request, $id)
    {
        $criterion = \App\Models\Criterion::find($id);
        if (!$criterion) {
            return response()->json(['message' => 'Criterio no encontrado.'], 404);
        }

        $updated = $this->service->update($criterion, $request->validated());

        return \App\Http\Resources\CriterionResource::make($updated)
            ->response()
            ->setStatusCode(200);
    }
    /**
     * DELETE /api/estructura/criterios/{id}
     */
    public function destroy($id)
    {
        $criterion = Criterion::find($id);
        if (!$criterion) {
            return response()->json(['message' => 'Criterio no encontrado.'], 404);
        }

        try {
            $this->service->delete($criterion);
            return response()->noContent(); // 204
        } catch (QueryException $e) {
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
