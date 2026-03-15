<?php

namespace App\Http\Controllers;

use App\Models\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ciclo_acreditacion_id' => 'required|exists:CICLO_ACREDITACION,ciclo_acreditacion_id',
            'tipo_proceso' => 'required|string|max:50',
            'modelo_estructura_id' => 'required|exists:MODELO_ESTRUCTURA,modelo_estructura_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proceso = Process::create($request->only([
            'ciclo_acreditacion_id',
            'tipo_proceso',
            'modelo_estructura_id'
        ]));
        
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
    public function update(Request $request, $id)
    {
        $proceso = Process::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'ciclo_acreditacion_id' => 'sometimes|exists:CICLO_ACREDITACION,ciclo_acreditacion_id',
            'tipo_proceso' => 'sometimes|string|max:50',
            'modelo_estructura_id' => 'sometimes|exists:MODELO_ESTRUCTURA,modelo_estructura_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proceso->update($request->only([
            'ciclo_acreditacion_id',
            'tipo_proceso',
            'modelo_estructura_id'
        ]));
        
        return response()->json([
            'message' => 'Proceso actualizado exitosamente.',
            'data' => $proceso->load(['accreditationCycle', 'modeloEstructura'])
        ]);
    }

    /**
     * Eliminar un proceso.
     */
    public function destroy($id)
    {
        $proceso = Process::findOrFail($id);
        $proceso->delete();
        
        return response()->json([
            'message' => 'Proceso eliminado exitosamente.'
        ]);
    }
}

