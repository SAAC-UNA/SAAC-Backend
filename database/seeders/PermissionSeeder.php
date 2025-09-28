<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permisos oficiales definidos en config/permissions.php
        $permisos = config('permissions.list');

        // Crear o actualizar los permisos definidos en config
        foreach ($permisos as $permiso) {
            Permission::updateOrCreate(
                ['name' => $permiso, 'guard_name' => 'api'], // criterio de búsqueda
                [] // en el futuro podrías añadir más columnas aquí
            );
        }

        // Detectar permisos obsoletos (los que ya no están en config)
        $toDelete = Permission::whereNotIn('name', $permisos)->get();

        foreach ($toDelete as $permiso) {
            if ($permiso->roles()->exists()) {
                //  El permiso sigue asignado a roles → no se elimina
                logger()->warning("El permiso [ID: {$permiso->id}, NAME: {$permiso->name}] no se eliminó porque aún está asignado a roles.");
                continue;
            }

            $id = $permiso->id;
            $name = $permiso->name;
            $permiso->delete();

            logger()->info("Permiso eliminado [ID: {$id}, NAME: {$name}] por estar obsoleto y sin uso.");
        }
    }
}
