<?php

namespace App\Http\Controllers;

use App\Models\ModeloEstructura;
// use App\Http\Requests\ModeloEstructuraRequest; // Ya no se usa - no se permite crear/editar modelos
use App\Services\ModeloEstructuraService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;

class ModeloEstructuraController extends Controller
{
    protected ModeloEstructuraService $service;

    public function __construct(ModeloEstructuraService $service)
    {
        $this->service = $service;
    }
    /**
     * Obtener todos los modelos de estructura
     */
    public function index(): JsonResponse
    {
        $models = $this->service->getAll();
        return response()->json($models);
    }

    /**
     * Obtener solo modelos activos
     */
    public function activos(): JsonResponse
    {
        $models = $this->service->getActive();
        return response()->json($models);
    }

    /**
     * Obtener un modelo específico
     */
    public function show(int $id): JsonResponse
    {
        $model = $this->service->findById($id);
        
        if (!$model) {
            return response()->json([
                'message' => 'Modelo de estructura no encontrado.'
            ], 404);
        }
        
        return response()->json($model);
    }

    /**
     * COMENTADO: No se permite crear modelos - ya están predefinidos en migración
     * (SINAES 2018 tradicional y SINAES 2026 jerarquia_flexible)
     */
    // public function store(ModeloEstructuraRequest $request): JsonResponse
    // {
    //     $model = $this->service->create($request->validated());

    //     AuditLogService::log(
    //         'crear',
    //         "Se creó el modelo de estructura \"{$model->nombre}\" (Tipo: {$model->tipo}).",
    //         'Modelo Estructura'
    //     );

    //     return response()->json([
    //         'message' => 'Modelo de estructura creado exitosamente.',
    //         'data' => $model
    //     ], 201);
    // }

    /**
     * COMENTADO: No se permite editar modelos - son predefinidos del sistema
     * Para cambios usar toggleActivo() o modificar directamente en BD si es necesario
     */
    // public function update(ModeloEstructuraRequest $request, int $id): JsonResponse
    // {
    //     $model = ModeloEstructura::find($id);

    //     if (!$model) {
    //         return response()->json([
    //             'message' => 'Modelo de estructura no encontrado.'
    //         ], 404);
    //     }

    //     $updated = $this->service->update($model, $request->validated());

    //     AuditLogService::log(
    //         'editar',
    //         "Se actualizó el modelo de estructura ID {$updated->modelo_estructura_id} (Nombre: {$updated->nombre}).",
    //         'Modelo Estructura'
    //     );

    //     return response()->json([
    //         'message' => 'Modelo de estructura actualizado exitosamente.',
    //         'data' => $updated
    //     ]);
    // }

    /**
     * Activar/Desactivar modelo (no eliminar para mantener integridad con PROCESO)
     */
    public function toggleActivo(int $id): JsonResponse
    {
        $model = ModeloEstructura::find($id);

        if (!$model) {
            return response()->json([
                'message' => 'Modelo de estructura no encontrado.'
            ], 404);
        }

        $updated = $this->service->toggleActive($model);
        $statusText = $updated->activo ? 'activado' : 'desactivado';

        AuditLogService::log(
            'editar',
            "Se {$statusText} el modelo de estructura ID {$updated->modelo_estructura_id} (Nombre: {$updated->nombre}).",
            'Modelo Estructura'
        );

        return response()->json([
            'message' => $updated->activo 
                ? 'Modelo activado exitosamente.' 
                : 'Modelo desactivado exitosamente.',
            'data' => $updated
        ]);
    }
}
