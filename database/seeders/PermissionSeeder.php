<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Seeder que gestiona los permisos oficiales del sistema.
 *
 * - Inserta o actualiza los permisos definidos en config/permissions.php.
 * - Elimina los permisos obsoletos que ya no estén en el archivo de configuración,
 *   siempre y cuando no estén asignados a ningún rol.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Ejecuta la siembra de permisos en la base de datos.
     *
     * @return void
     */
    public function run(): void
    {
        // Permisos oficiales definidos en config/permissions.php
        $permissions = config('permissions.list');

        // Crear o actualizar los permisos definidos en config
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'api'], // criterio de búsqueda
                [] // en el futuro se pueden añadir más columnas aquí
            );
        }

        // Detectar permisos obsoletos (los que ya no están en config)
        $permissionsToDelete = Permission::whereNotIn('name', $permissions)->get();

        foreach ($permissionsToDelete as $permission) {
            if ($permission->roles()->exists()) {
                // El permiso sigue asignado a roles → no se elimina
                logger()->warning(
                    "El permiso [ID: {$permission->id}, NAME: {$permission->name}] no se eliminó porque aún está asignado a roles."
                );
                continue;
            }

            $id = $permission->id;
            $name = $permission->name;
            $permission->delete();

            logger()->info(
                "Permiso eliminado [ID: {$id}, NAME: {$name}] por estar obsoleto y sin uso."
            );
        }
    }
}

