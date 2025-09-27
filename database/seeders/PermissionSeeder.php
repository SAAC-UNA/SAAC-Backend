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
        // Permisos definidos en config/permissions.php
        $permisos = config('permissions.list');

        // Crear o actualizar los permisos definidos en config
        foreach ($permisos as $permiso) {
            Permission::updateOrCreate(
                ['name' => $permiso, 'guard_name' => 'api'], // criterio de búsqueda
                [] // aquí podrías agregar 'description' si en el futuro añades esa columna
            );
        }

        // Detectar permisos obsoletos (los que ya no están en config)
        $toDelete = Permission::whereNotIn('name', $permisos)->get(['id', 'name']);

        if ($toDelete->isNotEmpty()) {
            logger()->warning('Se eliminarán permisos obsoletos:', $toDelete->toArray());
        }

        // Eliminar los permisos obsoletos
        Permission::whereNotIn('name', $permisos)->delete();
    }
}
