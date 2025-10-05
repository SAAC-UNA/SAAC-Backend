<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Permiso maestro (HU-02)
        Permission::firstOrCreate(['name' => 'admin.super', 'guard_name' => 'api']);

        // 2) Módulos y acciones atómicas (solo para los que usarás en HU-02)
        $modules = [
            'usuarios'   => ['view','create','edit','delete'],
            'evidencias' => ['view','create','edit','delete'],
            'reportes'   => ['generate'],
            'ciclos'     => ['view','create','edit','delete'],
            // 'programas' solo alias gestion_ (si el FE lo usa, lo mantenemos)
            // 'roles'     si el FE usa gestion_roles, lo mantenemos como alias
        ];

        // 3) Aliases que el FE ya usa (no se cambiorán)
        $aliases = [
            'gestion_usuarios',
            'gestion_evidencias',
            'gestion_reportes',
            'gestion_ciclos',
            'gestion_programas',
            'gestion_roles',
        ];

        foreach ($aliases as $alias) {
            Permission::firstOrCreate(['name' => $alias, 'guard_name' => 'api']);
        }

        // 4) Crear atómicos correctamente con "modulo.accion"
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$module}.{$action}",
                    'guard_name' => 'api',
                ]);
            }
        }
    }
}
