<?php

namespace App\Http\Controllers;

use App\Models\AccreditationCycle;
use App\Services\AccreditationCycleService;
use App\Http\Requests\AccreditationCycleRequest;
use App\Http\Resources\AccreditationCycleResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AccreditationCycleController extends Controller
{
    use AuthorizesRequests;

    protected $service;

    public function __construct(AccreditationCycleService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/ciclos
     * AC-3: El filtro por rol se aplica automáticamente via BaseCareer
     */
    public function index(AccreditationCycleRequest $request)
    {
        $this->authorize('viewAny', AccreditationCycle::class);

        $cycles = $this->service->getAll($request->validated());

        return AccreditationCycleResource::collection($cycles);
    }

    /**
     * GET /api/ciclos/{id}
     */
    public function show($id)
    {
        $cycle = $this->service->findById($id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        $this->authorize('view', $cycle);

        return new AccreditationCycleResource($cycle);
    }

    /**
     * POST /api/ciclos
     * AC-1: Crear ciclo con nombre, carrera_sede y estado
     * AC-2: Validaciones en AccreditationCycleRequest
     */
    public function store(AccreditationCycleRequest $request)
    {
        $this->authorize('create', AccreditationCycle::class);

        $cycle = $this->service->create($request->validated());

        return AccreditationCycleResource::make($cycle)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/ciclos/{id}
     * AC-4: Bloquea edición si el ciclo no está activo (verificado en Policy)
     * AC-5: Requiere permiso ciclos.edit (verificado en Policy)
     */
    public function update(AccreditationCycleRequest $request, $id)
    {
        $cycle = $this->service->findById($id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        $this->authorize('update', $cycle);

        $updated = $this->service->update($cycle, $request->validated());

        return AccreditationCycleResource::make($updated)->response();
    }

    /**
     * DELETE /api/ciclos/{id}
     * Los ciclos NO se eliminan físicamente.
     * Solo se inactivan mediante PATCH con estado='inactivo'.
     * La Policy siempre deniega esta acción (delete → false).
     */
    public function destroy($id)
    {
        $cycle = $this->service->findById($id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        $this->authorize('delete', $cycle);

        // Nunca se alcanza: la Policy bloquea el DELETE físico.
        return response()->noContent();
    }
}