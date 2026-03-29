<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AuditLogService;
use App\Services\ProcessService;

class ProcessController extends Controller
{
    protected ProcessService $service;

    public function __construct(ProcessService $service)
    {
        $this->service = $service;
    }

    /**
     * Listar todos los procesos con relaciones.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    /**
     * Crear un nuevo proceso.
     */
    public function store(ProcessRequest $request): JsonResponse
    {
        $process = $this->service->create($request->validated());

        AuditLogService::log(
            'crear',
            "Se creó el proceso ID {$process->proceso_id} (Tipo: {$process->tipo_proceso}).",
            'Proceso'
        );

        return response()->json([
            'message' => 'Proceso creado exitosamente.',
            'data'    => $process,
        ], 201);
    }

    /**
     * Mostrar un proceso específico.
     */
    public function show(int $id): JsonResponse
    {
        $process = $this->service->findById($id);

        return response()->json($process);
    }

    /**
     * Actualizar un proceso existente.
     */
    public function update(ProcessRequest $request, int $id): JsonResponse
    {
        $process = $this->service->findById($id);
        $updated = $this->service->update($process, $request->validated());

        AuditLogService::log(
            'editar',
            "Se actualizó el proceso ID {$updated->proceso_id} (Tipo: {$updated->tipo_proceso}).",
            'Proceso'
        );

        return response()->json([
            'message' => 'Proceso actualizado exitosamente.',
            'data'    => $updated,
        ]);
    }

    /**
     * Activar/Desactivar un proceso.
     * PATCH /api/estructura/procesos/{id}/active
     */
    public function setActive(Request $request, int $id): JsonResponse
    {
        $process = $this->service->findById($id);

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        try {
            $this->service->toggleActive($process, $validated['active']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $statusText = $process->activo ? 'activado' : 'desactivado';

        AuditLogService::log(
            'editar',
            "Se {$statusText} el proceso ID {$process->proceso_id} (Tipo: {$process->tipo_proceso}).",
            'Proceso'
        );

        return response()->json([
            'message' => "Proceso {$statusText} exitosamente.",
            'active'  => $process->activo,
        ]);
    }

    /**
     * Eliminar un proceso con confirmación por tipo (GitHub-style).
     *
     * Body: { "confirmacion": "Autoevaluación" }  (tipo_proceso exacto)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $process = $this->service->findById($id);
        $summary = $this->service->getDeleteSummary($process);

        $confirmacion = $request->input('confirmacion', '');

        if ($confirmacion !== $process->tipo_proceso) {
            return response()->json([
                'message'          => 'Confirmación incorrecta. Envíe el tipo exacto del proceso en el campo "confirmacion" para confirmar la eliminación.',
                'tipo_proceso'     => $process->tipo_proceso,
                'advertencia'      => 'Esta acción es irreversible y eliminará permanentemente el proceso y todos sus datos asociados.',
                'datos_a_eliminar' => $summary,
            ], 422);
        }

        $procesoId   = $process->proceso_id;
        $tipoProceso = $process->tipo_proceso;

        $this->service->delete($process);

        AuditLogService::log(
            'eliminar',
            "Se eliminó el proceso ID {$procesoId} (Tipo: {$tipoProceso}).",
            'Proceso'
        );

        return response()->json([
            'message'          => "Proceso \"{$tipoProceso}\" eliminado exitosamente.",
            'datos_eliminados' => $summary,
        ]);
    }
}

