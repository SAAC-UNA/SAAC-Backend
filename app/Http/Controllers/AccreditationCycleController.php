<?php

namespace App\Http\Controllers;

use App\Models\AccreditationCycle;
use App\Services\AccreditationCycleService;
use App\Services\AuditLogService;
use App\Http\Requests\AccreditationCycleRequest;
use App\Http\Resources\AccreditationCycleResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

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
     *
     * HU-030 (modelo flexible): si se intenta cambiar modelo_estructura_id
     * en un ciclo con procesos, el service lanza InvalidArgumentException → 422.
     */
    public function update(AccreditationCycleRequest $request, $id)
    {
        $cycle = $this->service->findById($id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        $this->authorize('update', $cycle);

        try {
            $updated = $this->service->update($cycle, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return AccreditationCycleResource::make($updated)->response();
    }

    /**
     * DELETE /api/ciclos/{id}
     * Solo Superusuario. Requiere confirmación con el nombre exacto del ciclo.
     * Body: { "confirmacion": "nombre exacto del ciclo" }
     */
    public function destroy(Request $request, $id)
    {
        $cycle = $this->service->findById($id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        $this->authorize('delete', $cycle);

        $confirmacion = $request->input('confirmacion', '');

        if ($confirmacion !== $cycle->nombre) {
            return response()->json([
                'message'       => 'Confirmación incorrecta. Envíe el nombre exacto del ciclo en el campo "confirmacion" para confirmar la eliminación.',
                'ciclo_nombre'  => $cycle->nombre,
                'advertencia'   => 'Esta acción es irreversible.',
            ], 422);
        }

        try {
            $this->service->delete($cycle);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        AuditLogService::log(
            'eliminar',
            "Se eliminó el ciclo de acreditación \"{$cycle->nombre}\" (ID: {$id}).",
            'Ciclo Acreditación'
        );

        return response()->json(['message' => "Ciclo \"{$cycle->nombre}\" eliminado exitosamente."]);
    }

    /**
     * PATCH /api/estructura/ciclos-acreditacion/{id}/reactivar
     * AC-R: Solo Superusuario. Reactiva un ciclo inactivo/completado.
     * Sigue respetando AC-6: no puede haber otro activo en la misma carrera+sede.
     */
    public function reactivate($id)
    {
        $cycle = $this->service->findById($id);

        if (!$cycle) {
            return response()->json(['message' => 'Ciclo de acreditación no encontrado.'], 404);
        }

        $this->authorize('reactivate', $cycle);

        if ($cycle->estado === AccreditationCycle::STATUS_ACTIVE) {
            return response()->json(['message' => 'El ciclo ya está activo.'], 422);
        }

        // AC-6: verificar que no haya otro activo en la misma carrera+sede
        $conflicto = AccreditationCycle::where('carrera_sede_id', $cycle->carrera_sede_id)
            ->where('estado', AccreditationCycle::STATUS_ACTIVE)
            ->where('ciclo_acreditacion_id', '!=', $cycle->ciclo_acreditacion_id)
            ->exists();

        if ($conflicto) {
            return response()->json([
                'errors' => [
                    'carrera_sede_id' => ['Ya existe un ciclo activo para esta carrera en esta sede.'],
                ],
            ], 422);
        }

        $updated = $this->service->update($cycle, ['estado' => AccreditationCycle::STATUS_ACTIVE]);

        return AccreditationCycleResource::make($updated)->response();
    }
}