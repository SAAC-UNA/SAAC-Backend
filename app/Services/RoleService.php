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
 * Eliminar un rol existente.
 *
 * @param Role $rol
 * @return bool
 */
   public function eliminarRol(int $id): ?Role
   {
        return DB::transaction(function () use ($id) {
            $rol = Role::find($id);

            if (!$rol) {
                return null;
            }

       Model::unsetEventDispatcher();// Evitar que Spatie intente resolver users()

        $rol->permissions()->detach();
        $rol->delete();
        Model::setEventDispatcher(app('events')); // restaurar eventos

        return $rol;
      });
   }


}


