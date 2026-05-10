<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalFilterContextRequest;
use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\Process;
use App\Services\GlobalFilterContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalFilterContextController extends Controller
{
    public function __construct(private readonly GlobalFilterContextService $service) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->get($request->user()),
        ]);
    }

    public function update(GlobalFilterContextRequest $request): JsonResponse
    {
        $context = $this->service->set($request->user(), $request->validated());

        return response()->json([
            'message' => 'Contexto global actualizado correctamente.',
            'data'    => $context,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $context = $this->service->clear($request->user());

        return response()->json([
            'message' => 'Contexto global reiniciado correctamente.',
            'data'    => $context,
        ]);
    }

    public function catalog(Request $request): JsonResponse
    {
        $user = $request->user();
        $context = $this->service->get($user);

        $careersQuery = CareerCampus::query()->with(['career', 'campus']);
        $cyclesQuery = AccreditationCycle::query()
            ->with('modeloEstructura')
            ->with(['careerCampus.career', 'careerCampus.campus']);
        $processesQuery = Process::query()->with('accreditationCycle');

        if (!$user->hasRole('Superusuario')) {
            $ids = $user->careers()->pluck('CARRERA_SEDE.carrera_sede_id');

            $careersQuery->whereIn('carrera_sede_id', $ids);
            $cyclesQuery->whereIn('carrera_sede_id', $ids);
            $processesQuery->whereHas('accreditationCycle', fn ($q) =>
                $q->whereIn('carrera_sede_id', $ids)
            );
        }

        return response()->json([
            'data' => [
                'context' => $context,
                'careers' => $careersQuery->orderBy('carrera_sede_id')->get()->map(fn (CareerCampus $item) => [
                    'carrera_sede_id' => $item->carrera_sede_id,
                    'carrera_nombre'  => $item->career?->nombre,
                    'sede_nombre'     => $item->campus?->nombre,
                ])->values(),
                'cycles' => $cyclesQuery->orderByDesc('ciclo_acreditacion_id')->get()->map(fn (AccreditationCycle $item) => [
                    'ciclo_acreditacion_id' => $item->ciclo_acreditacion_id,
                    'nombre'                => $item->nombre,
                    'estado'                => $item->estado,
                    'carrera_sede_id'       => $item->carrera_sede_id,
                    'modelo_tipo'           => $item->modeloEstructura?->tipo ?? null,
                ])->values(),
                'processes' => $processesQuery->orderByDesc('proceso_id')->get()->map(fn (Process $item) => [
                    'proceso_id'            => $item->proceso_id,
                    'tipo_proceso'          => $item->tipo_proceso,
                    'activo'                => (bool) $item->activo,
                    'ciclo_acreditacion_id' => $item->ciclo_acreditacion_id,
                ])->values(),
            ],
        ]);
    }
}
