<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;

class RoleController extends Controller
{
    private RoleService $rolServicio;

    public function __construct(RoleService $rolServicio)
    {
        $this->rolServicio = $rolServicio;
    }

    /**
     * Listar todos los roles con sus permisos asociados.
     */
    public function listarRoles(): JsonResponse
    {
        $roles = $this->rolServicio->listarRoles();
        return response()->json(['datos' => RoleResource::collection($roles)], 200);
    }

    /**
     * Crear un nuevo rol con sus permisos.
     */
    public function crearRol(RoleRequest $request): JsonResponse
    {
        $rol = $this->rolServicio->crearRol($request->validated());
        return response()->json([
            'mensaje' => 'Rol creado con éxito',
            'datos'   => new RoleResource($rol),
        ], 201);
    }

    /**
     * Mostrar un rol específico.
     */
    public function mostrarRol(int $id): JsonResponse
    {
        $rol = $this->rolServicio->obtenerRol($id);

        if (!$rol) {
            return response()->json(['mensajeError' => 'Rol no encontrado'], 404);
        }

        return response()->json(['datos' => new RoleResource($rol)], 200);
    }

    /**
     * Actualizar un rol existente.
     */
    public function actualizarRol(RoleRequest $request, int $id): JsonResponse
    {
        $rol = $this->rolServicio->obtenerRol($id);

        if (!$rol) {
            return response()->json(['mensajeError' => 'Rol no encontrado'], 404);
        }

        $original = [
            'name'        => $rol->name,
            'description' => $rol->description,
            'permissions' => $rol->permissions->pluck('name')->sort()->values()->toArray(),
        ];

        $rolActualizado = $this->rolServicio->actualizarRol($rol, $request->validated());

        $nuevo = [
            'name'        => $rolActualizado->name,
            'description' => $rolActualizado->description,
            'permissions' => $rolActualizado->permissions->pluck('name')->sort()->values()->toArray(),
        ];

        if ($original == $nuevo) {
            return response()->json([
                'mensaje' => 'No se realizaron cambios en el rol',
                'datos'   => new RoleResource($rolActualizado),
            ], 200);
        }

        return response()->json([
            'mensaje' => 'Rol actualizado con éxito',
            'datos'   => new RoleResource($rolActualizado),
        ], 200);
    }

    /**
     * Listar todos los permisos disponibles.
     */
    public function listarPermisos(): JsonResponse
    {
        $permisos = $this->rolServicio->listarPermisos();
        return response()->json(['datos' => $permisos], 200);
    }

    /**
     * Eliminar un rol existente.
     */
    public function eliminarRol(int $id): JsonResponse
    {
        $resultado = $this->rolServicio->eliminarRol($id);

        if (!$resultado) {
            return response()->json(['mensajeError' => 'Rol no encontrado'], 404);
        }

        return response()->json(['mensaje' => 'Rol eliminado con éxito'], 200);
    }
}

