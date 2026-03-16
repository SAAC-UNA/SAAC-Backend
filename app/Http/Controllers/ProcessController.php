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
        $query = Process::with([
            'accreditationCycle.careerCampus.career', 
            'accreditationCycle.careerCampus.campus',
            'modeloEstructura'
        ]);
        
        // Filtrar por modelo_estructura_id opcional
        if ($request->has('modelo_estructura_id')) {
            $query->where('modelo_estructura_id', $request->modelo_estructura_id);
        }
        
        // Filtrar por ciclo_acreditacion_id opcional
        if ($request->has('ciclo_acreditacion_id')) {
            $query->where('ciclo_acreditacion_id', $request->ciclo_acreditacion_id);
        }
        
        return response()->json($query->get());
    }

    /**
     * Crear un nuevo proceso.
     */
    public function store(ProcessRequest $request)
    {
        $proceso = Process::create($request->validated());
        
        AuditLogService::log(
            'crear',
            "Se creó el proceso ID {$proceso->proceso_id} (Tipo: {$proceso->tipo_proceso}).",
            'Proceso'
        );
        
        return response()->json([
            'message' => 'Proceso creado exitosamente.',
            'data' => $proceso->load(['accreditationCycle', 'modeloEstructura'])
        ], 201);
    }

    /**
     * Mostrar un proceso específico.
     */
    public function show($id)
    {
        $proceso = Process::with([
            'accreditationCycle.careerCampus.career', 
            'accreditationCycle.careerCampus.campus',
            'modeloEstructura'
        ])->findOrFail($id);
        
        return response()->json($proceso);
    }

    /**
     * Actualizar un proceso existente.
     */
    public function update(ProcessRequest $request, $id)
    {
        $proceso = Process::findOrFail($id);
        
        $proceso->update($request->validated());
        
        AuditLogService::log(
            'editar',
            "Se actualizó el proceso ID {$proceso->proceso_id} (Tipo: {$proceso->tipo_proceso}).",
            'Proceso'
        );
        
        return response()->json([
            'message' => 'Proceso actualizado exitosamente.',
            'data' => $proceso->load(['accreditationCycle', 'modeloEstructura'])
        ]);
    }

    /**
     * Activar/Desactivar un proceso.
     * PATCH /api/estructura/procesos/{id}/active
     */
    public function setActive(Request $request, $id)
    {
        $proceso = Process::findOrFail($id);
        
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);
        
        $newActiveState = $validated['active'];
        $proceso->activo = $newActiveState;
        $proceso->save();
        
        $estadoTexto = $newActiveState ? 'activado' : 'desactivado';
        
        AuditLogService::log(
            'editar',
            "Se {$estadoTexto} el proceso ID {$proceso->proceso_id} (Tipo: {$proceso->tipo_proceso}).",
            'Proceso'
        );
        
        return response()->json([
            'message' => "Proceso {$estadoTexto} exitosamente.",
            'data' => $proceso->load(['accreditationCycle', 'modeloEstructura'])
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

