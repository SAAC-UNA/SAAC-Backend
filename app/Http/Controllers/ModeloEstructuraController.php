<?php

namespace App\Http\Controllers;

use App\Models\ModeloEstructura;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ModeloEstructuraController extends Controller
{
    /**
     * Obtener todos los modelos de estructura
     */
    public function index(Request $request): JsonResponse
    {
        $rows = DB::select('CALL SP_OBTENER_MODELOS_ESTRUCTURA()');
        $modelos = ModeloEstructura::hydrate(array_map(fn($r) => (array) $r, $rows));

        return response()->json($modelos);
    }

    /**
     * Obtener solo modelos activos
     */
    public function activos(): JsonResponse
    {
        $rows = DB::select('CALL SP_OBTENER_MODELOS_ACTIVOS()');
        $modelos = ModeloEstructura::hydrate(array_map(fn($r) => (array) $r, $rows));

        return response()->json($modelos);
    }

    /**
     * Obtener un modelo específico
     */
    public function show(int $id): JsonResponse
    {
        $rows = DB::select('CALL SP_BUSCAR_MODELO_ESTRUCTURA(?)', [$id]);
        
        if (empty($rows)) {
            return response()->json([
                'message' => 'Modelo de estructura no encontrado.'
            ], 404);
        }

        $modelo = ModeloEstructura::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
        
        return response()->json($modelo);
    }

    /**
     * Crear un nuevo modelo (admin only - raro)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|string|max:30|in:tradicional,jerarquia_flexible,hibrido,custom',
            'version' => 'nullable|string|max:20',
            'activo' => 'boolean',
        ]);

        $modelo = ModeloEstructura::create($validated);

        return response()->json([
            'message' => 'Modelo de estructura creado exitosamente.',
            'data' => $modelo
        ], 201);
    }

    /**
     * Actualizar modelo existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $modelo = ModeloEstructura::find($id);

        if (!$modelo) {
            return response()->json([
                'message' => 'Modelo de estructura no encontrado.'
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
            'tipo' => 'sometimes|string|max:30|in:tradicional,jerarquia_flexible,hibrido,custom',
            'version' => 'nullable|string|max:20',
            'activo' => 'boolean',
        ]);

        $modelo->update($validated);

        return response()->json([
            'message' => 'Modelo de estructura actualizado exitosamente.',
            'data' => $modelo
        ]);
    }

    /**
     * Activar/Desactivar modelo (no eliminar para mantener integridad con PROCESO)
     */
    public function toggleActivo(int $id): JsonResponse
    {
        $modelo = ModeloEstructura::find($id);

        if (!$modelo) {
            return response()->json([
                'message' => 'Modelo de estructura no encontrado.'
            ], 404);
        }

        $modelo->activo = !$modelo->activo;
        $modelo->save();

        return response()->json([
            'message' => $modelo->activo 
                ? 'Modelo activado exitosamente.' 
                : 'Modelo desactivado exitosamente.',
            'data' => $modelo
        ]);
    }
}
