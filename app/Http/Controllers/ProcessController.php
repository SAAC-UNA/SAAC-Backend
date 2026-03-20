<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Http\Requests\ProcessRequest;
use Illuminate\Http\Request;
use App\Services\AuditLogService;

class ProcessController extends Controller
{
    /**
     * Listar todos los procesos con relaciones.
     */
    public function index(Request $request)
    {
        $processes = Process::with([
            'accreditationCycle.careerCampus.career',
            'accreditationCycle.careerCampus.campus',
            'accreditationCycle.modeloEstructura',
        ])->get();

        return response()->json($processes);
    }

    /**
     * Crear un nuevo proceso.
     */
    public function store(ProcessRequest $request)
    {
        $process = Process::create($request->validated());
        
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
    public function show($id)
    {
        $process = Process::findOrFail($id);

        return response()->json($process);
    }

    /**
     * Actualizar un proceso existente.
     */
    public function update(ProcessRequest $request, $id)
    {
        $process = Process::findOrFail($id);
        
        $process->update($request->validated());
        
        AuditLogService::log(
            'editar',
            "Se actualizó el proceso ID {$process->proceso_id} (Tipo: {$process->tipo_proceso}).",
            'Proceso'
        );
        
        return response()->json([
            'message' => 'Proceso actualizado exitosamente.',
            'data'    => $process,
        ]);
    }

    /**
     * Activar/Desactivar un proceso.
     * PATCH /api/estructura/procesos/{id}/active
     */
    public function setActive(Request $request, $id)
    {
        $process = Process::findOrFail($id);
        
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);
        
        $newActiveState = $validated['active'];
        $process->activo = $newActiveState;
        $process->save();
        
        $statusText = $newActiveState ? 'activado' : 'desactivado';
        
        AuditLogService::log(
            'editar',
            "Se {$statusText} el proceso ID {$process->proceso_id} (Tipo: {$process->tipo_proceso}).",
            'Proceso'
        );
        
        return response()->json([
            'message' => "Proceso {$statusText} exitosamente.",
            'active'  => $process->activo,
        ], 200);
    }

    // =====================================================
    // MÉTODO destroy() DESHABILITADO
    // Los procesos NO se eliminan físicamente.
    // Solo se activan/desactivan usando setActive()
    // =====================================================
    /**
     * Eliminar un proceso.
     * DESHABILITADO: Los procesos no se eliminan, solo se activan/desactivan.
     */
    /*
    public function destroy($id)
    {
        $proceso = Process::findOrFail($id);
        $proceso->delete();
        
        return response()->json([
            'message' => 'Proceso eliminado exitosamente.'
        ]);
    }
    */
}

