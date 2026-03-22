<?php

namespace App\Http\Controllers;

use App\Models\StructureModel;
use App\Http\Requests\StructureModelRequest;
use Illuminate\Http\Request;
use App\Services\StructureModelService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;

class StructureModelController extends Controller
{
    protected StructureModelService $service;

    public function __construct(StructureModelService $service)
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
     * Crear nuevo modelo de estructura
     */
    public function store(StructureModelRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Doble verificación de campos críticos
        if (empty($data['nombre']) || empty($data['tipo'])) {
            return response()->json([
                'message' => 'Los campos nombre y tipo son obligatorios.',
            ], 422);
        }

        $model = $this->service->create($data);

        if (!$model) {
            return response()->json([
                'message' => 'No se pudo crear el modelo de estructura.',
            ], 500);
        }

        AuditLogService::log(
            'crear',
            "Se creó el modelo de estructura \"{$model->nombre}\" (Tipo: {$model->tipo}).",
            'Modelo Estructura'
        );

        return response()->json([
            'message' => 'Modelo de estructura creado exitosamente.',
            'data'    => $model,
        ], 201);
    }

    /**
     * Editar metadata del modelo (nombre, descripcion, version).
     * tipo y activo NO son editables aquí.
     */
    public function update(StructureModelRequest $request, int $id): JsonResponse
    {
        $model = $this->service->findById($id);

        if (!$model) {
            return response()->json([
                'message' => 'Modelo de estructura no encontrado.',
            ], 404);
        }

        $data = $request->validated();

        if (empty($data)) {
            return response()->json([
                'message' => 'No se enviaron campos válidos para actualizar.',
            ], 422);
        }

        $updated = $this->service->update($model, $data);

        if (!$updated) {
            return response()->json([
                'message' => 'No se pudo actualizar el modelo de estructura.',
            ], 500);
        }

        AuditLogService::log(
            'editar',
            "Se actualizó el modelo de estructura ID {$updated->modelo_estructura_id} (Nombre: {$updated->nombre}).",
            'Modelo Estructura'
        );

        return response()->json([
            'message' => 'Modelo de estructura actualizado exitosamente.',
        ]);
    }

    /**
     * Activar/Desactivar modelo (no eliminar para mantener integridad con PROCESO)
     */
    public function setActive(Request $request, int $id): JsonResponse
    {
        $model = StructureModel::find($id);

        if (!$model) {
            return response()->json([
                'message' => 'Modelo de estructura no encontrado.'
            ], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        if ($model->activo !== $validated['active']) {
            $this->service->toggleActive($model);
            $model->refresh();
        }

        $statusText = $model->activo ? 'activado' : 'desactivado';

        AuditLogService::log(
            'editar',
            "Se {$statusText} el modelo de estructura ID {$model->modelo_estructura_id} (Nombre: {$model->nombre}).",
            'Modelo Estructura'
        );

        return response()->json([
            'message' => "Modelo de estructura {$statusText} exitosamente.",
            'active'  => $model->activo,
        ]);
    }
}
