<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User; // Para asignar el rol al usuario admin

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1️ Permiso maestro (HU-02)
        Permission::firstOrCreate(['name' => 'admin.super', 'guard_name' => 'api']);

        // 2️ Módulos y acciones atómicas (solo para los que usarás en HU-02)
        $modules = [
            'usuarios'   => ['view','create','edit','delete'],
            'evidencias' => ['view','create','edit','delete'],
            'reportes'   => ['generate'],
            'ciclos'     => ['view','create','edit','delete'],
            // 'programas' solo alias gestion_ (si el FE lo usa, lo mantenemos)
            // 'roles'     si el FE usa gestion_roles, lo mantenemos como alias
        ];

        // 3️ Aliases que el FE ya usa (no se cambian)
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

        // 4️ Crear permisos atómicos con "modulo.accion"
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$module}.{$action}",
                    'guard_name' => 'api',
                ]);
            }
        }

        // 5️ Crear el rol "Superusuario" (si no existe)
        $superRole = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);

        // 6️ Asignar todos los permisos al rol Superusuario
        $superRole->syncPermissions(Permission::all());

        // 7️ Asignar el rol al usuario admin (si ya existe)
        $admin = User::where('email', 'admin@saacuna.local')->first();
        if ($admin) {
            $admin->assignRole($superRole);
        }
    }
}
