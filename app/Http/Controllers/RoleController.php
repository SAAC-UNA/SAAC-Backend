<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;

/**
 * Controller that manages operations related to Roles.
 * Provides endpoints to list, create, show, update, and delete roles.
 */
class RoleController extends Controller
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * List all roles with their associated permissions.
     *
     * @return JsonResponse
     */
    public function listRoles(): JsonResponse
    {
        $roles = $this->roleService->listRoles();
        return response()->json(['data' => RoleResource::collection($roles)], 200);
    }

    /**
     * Create a new role with its permissions.
     *
     * @param RoleRequest $request Validated data to create the role.
     * @return JsonResponse
     */
    public function createRole(RoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole($request->validated());

        return response()->json([
            'message' => 'Rol creado con éxito',
            'data'    => new RoleResource($role),
        ], 201);
    }

    /**
     * Show a specific role by its ID.
     *
     * @param int $id Unique identifier of the role.
     * @return JsonResponse
     */
    public function showRole(int $id): JsonResponse
    {
        $role = $this->roleService->getRole($id);

        if (!$role) {
            return response()->json([
                'error'   => 'Not Found',
                'message' => 'Rol no encontrado',
            ], 404);
        }

        return response()->json(['data' => new RoleResource($role)], 200);
    }

    /**
     * Update an existing role.
     * Compares the original data with the new one to detect changes.
     *
     * @param RoleRequest $request Validated role data.
     * @param int $id Identifier of the role to update.
     * @return JsonResponse
     */
    public function updateRole(RoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->getRole($id);

        if (!$role) {
            return response()->json([
                'error'   => 'Not Found',
                'message' => 'Rol no encontrado',
            ], 404);
        }

        // Original state before update
        $original = [
            'name'        => $role->name,
            'description' => $role->description,
            'permissions' => $role->permissions->pluck('name')->sort()->values()->toArray(),
        ];

        $updatedRole = $this->roleService->updateRole($role, $request->validated());

        // New state after update
        $newData = [
            'name'        => $updatedRole->name,
            'description' => $updatedRole->description,
            'permissions' => $updatedRole->permissions->pluck('name')->sort()->values()->toArray(),
        ];

        if ($original == $newData) {
            return response()->json([
                'message' => 'No se realizaron cambios en el rol',
                'data'    => new RoleResource($updatedRole),
            ], 200);
        }

        return response()->json([
            'message' => 'Rol actualizado con éxito',
            'data'    => new RoleResource($updatedRole),
        ], 200);
    }

    /**
     * List all available permissions.
     *
     * @return JsonResponse
     */
   public function listPermissions(): \Illuminate\Http\JsonResponse
   {
        // Obtenemos los permisos desde el servicio, ya incluye id, name y label
        $permissions = $this->roleService->listPermissions();

        // El servicio ya retorna los permisos con sus etiquetas legibles
        return response()->json([
            'data' => $permissions
        ], 200);
    }

    /**
     * Delete an existing role by its ID.
     *
     * @param int $id Unique identifier of the role to delete.
     * @return JsonResponse
     */
    public function deleteRole(int $id): JsonResponse
    {
        $result = $this->roleService->deleteRole($id);

        if (!$result) {
            return response()->json([
                'error'   => 'Not Found',
                'message' => 'Rol no encontrado',
            ], 404);
        }

        return response()->json(['message' => 'Rol eliminado con éxito'], 200);
    }
}
