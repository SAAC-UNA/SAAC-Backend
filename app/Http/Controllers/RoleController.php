<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use App\Services\AuditLogService;

/**
 * Controlador que gestiona las operaciones relacionadas con los Roles.
 * Proporciona los endpoints para listar, crear, mostrar, actualizar y eliminar roles.
 */
class RoleController extends Controller
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Lista todos los roles junto con sus permisos asociados.
     *
     * @return JsonResponse
     */
    public function listRoles(): JsonResponse
    {
        $roles = $this->roleService->listRoles();
        return response()->json(['data' => RoleResource::collection($roles)], 200);
    }

    /**
     * Crea un nuevo rol junto con sus permisos.
     *
     * @param RoleRequest 
     * @return JsonResponse
     */
    public function createRole(RoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole($request->validated());
        // Registrar en bitácora la creación del rol
        AuditLogService::log(
            'crear',
            "Rol creado: {$role->name} (ID: {$role->id})",
            'Roles'
        );
        return response()->json([
            'message' => 'Rol creado con éxito',
            'data'    => new RoleResource($role),
        ], 201);
    }

    /**
     *  Muestra la información de un rol específico según su ID.
     * @param int 
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
     * Actualiza un rol existente.
     * Compara los datos originales con los nuevos para detectar si hubo cambios.
     *
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

          // Estado original antes de la actualización
        $original = [
            'name'        => $role->name,
            'description' => $role->description,
            'permissions' => $role->permissions->pluck('name')->sort()->values()->toArray(),
        ];

        $updatedRole = $this->roleService->updateRole($role, $request->validated());

         // Estado nuevo después de la actualización
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
        // Registrar en bitácora los cambios realizados
        AuditLogService::log(
    'editar',
        "Rol actualizado: {$updatedRole->name} (ID: {$updatedRole->id})",
        'Roles'
        );

        return response()->json([
            'message' => 'Rol actualizado con éxito',
            'data'    => new RoleResource($updatedRole),
        ], 200);
    }

    /**
     *Lista todos los permisos disponibles.
     *
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
        // Verificar si el rol existe
        $role = $this->roleService->getRole($id);

        if (!$role) {
        return response()->json([
            'error'   => 'Not Found',
            'message' => 'Rol no encontrado',
        ], 404);
    }

    $result = $this->roleService->deleteRole($id);

    if ($result) {
        AuditLogService::log(
            'eliminar',
            "Rol eliminado: {$role->name} (ID: {$role->id})",
            'Roles'
        );
    }


        return response()->json(['message' => 'Rol eliminado con éxito'], 200);
    }
}
