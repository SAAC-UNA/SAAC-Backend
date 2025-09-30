<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Model;

/**
 * Servicio que gestiona la lógica de negocio relacionada con Roles.
 * Incluye operaciones para listar, obtener, crear, actualizar y eliminar roles,
 * así como la gestión de permisos asociados.
 */
class RoleService
{
    public function __construct()
    {
        // Preparado para futuras dependencias (ej: logger, auditoría, etc.)
    }

    /**
     * Listar todos los roles con sus permisos asociados.
     *
     * @return \Illuminate\Support\Collection Colección de roles con permisos.
     */
    public function listRoles()
    {
        return Role::with('permissions:id,name')->get();
    }

    /**
     * Obtener un rol específico por ID con sus permisos.
     *
     * @param int $id Identificador único del rol.
     * @return Role|null Retorna el rol o null si no existe.
     */
    public function getRole(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * Crear un nuevo rol con sus permisos.
     * Se usa transacción para garantizar atomicidad en el proceso.
     *
     * @param array<string,mixed> $data Datos validados del rol.
     * @return Role Rol recién creado.
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'guard_name'  => 'api',
            ]);

            // Si se especifican permisos, se asignan al rol
            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role->refresh();
        });
    }

    /**
     * Actualizar un rol existente.
     * Solo se ejecuta la actualización si hay cambios reales.
     *
     * @param Role $role Rol a actualizar.
     * @param array<string,mixed> $data Nuevos datos del rol.
     * @return Role Rol actualizado.
     */
    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $original = [
                'name'        => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name')->sort()->values()->toArray(),
            ];

            $newData = [
                'name'        => $data['name'] ?? $role->name,
                'description' => $data['description'] ?? null,
                'permissions' => collect($data['permissions'] ?? $original['permissions'])
                                    ->sort()->values()->toArray(),
            ];

            // Se evita actualizar si los datos son idénticos
            if ($original != $newData) {
                $role->update([
                    'name'        => $newData['name'],
                    'description' => $newData['description'],
                ]);

                $role->syncPermissions($newData['permissions']);
            }

            return $role->refresh();
        });
    }

    /**
     * Listar todos los permisos disponibles en el sistema.
     *
     * @return \Illuminate\Support\Collection
     */
    public function listPermissions()
    {
        return Permission::all()->pluck('name');
    }

    /**
     * Eliminar un rol existente por su ID.
     * Se desasocian permisos antes de la eliminación y se manejan eventos.
     *
     * @param int $id Identificador del rol a eliminar.
     * @return Role|null Rol eliminado o null si no existe.
     */
    public function deleteRole(int $id): ?Role
    {
        return DB::transaction(function () use ($id) {
            $role = Role::find($id);

            if (!$role) {
                return null;
            }

            // Se desactiva el despachador de eventos para evitar conflictos con Spatie
            Model::unsetEventDispatcher();

            $role->permissions()->detach();
            $role->delete();

            // Se restaura el despachador de eventos
            Model::setEventDispatcher(app('events'));

            return $role;
        });
    }
}
