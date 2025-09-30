<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Model;

class RoleService
{
    public function __construct()
    {
        // Preparado para futuras dependencias (ej: logger, bitácora, etc.)
    }

    /**
     * Listar todos los roles con sus permisos asociados.
     *
     * @return \Illuminate\Support\Collection
     */
    public function listarRoles()
    {
        return Role::with('permissions:id,name')->get();
    }

    /**
     * Obtener un rol específico por ID con sus permisos.
     *
     * @param int $id
     * @return Role|null
     */
    public function obtenerRol(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * Crear un nuevo rol con sus permisos.
     *
     * @param array<string,mixed> $datos
     * @return Role
     */
    public function crearRol(array $datos): Role
    {
        return DB::transaction(function () use ($datos) {
            $rol = Role::create([
                'name'        => $datos['name'],
                'description' => $datos['description'] ?? null,
                'guard_name'  => 'api',
            ]);

            if (!empty($datos['permissions'])) {
                $rol->syncPermissions($datos['permissions']);
            }

            return $rol->refresh();
        });
    }

    /**
     * Actualizar un rol existente.
     *
     * @param Role $rol
     * @param array<string,mixed> $datos
     * @return Role
     */
    public function actualizarRol(Role $rol, array $datos): Role
    {
        return DB::transaction(function () use ($rol, $datos) {
            $original = [
                'name'        => $rol->name,
                'description' => $rol->description,
                'permissions' => $rol->permissions->pluck('name')->sort()->values()->toArray(),
            ];

            $nuevos = [
                'name'        => $datos['name'] ?? $rol->name,
                'description' => $datos['description'] ?? null,
                'permissions' => collect($datos['permissions'] ?? $original['permissions'])
                                    ->sort()->values()->toArray(),
            ];

            if ($original != $nuevos) {
                $rol->update([
                    'name'        => $nuevos['name'],
                    'description' => $nuevos['description'],
                ]);

                $rol->syncPermissions($nuevos['permissions']);
            }

            return $rol->refresh();
        });
    }

    /**
     * Listar todos los permisos disponibles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function listarPermisos()
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
