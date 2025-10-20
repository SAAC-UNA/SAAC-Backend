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

        // 5️⃣ Crear roles del sistema
        $roles = [
            'Superusuario',
            'Administrador',
            'Profesor',
            'Encargado de Acreditación',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }

        // 6️⃣ Asignar permisos por rol
        
        // Superusuario: todos los permisos
        $superRole = Role::where('name', 'Superusuario')->first();
        if ($superRole) {
            $superRole->syncPermissions(Permission::all());
        }

        // Administrador: gestión completa de su carrera
        $adminRole = Role::where('name', 'Administrador')->first();
        if ($adminRole) {
            $adminRole->syncPermissions([
                'gestion_usuarios',
                'usuarios.view',
                'usuarios.create',
                'usuarios.edit',
                'usuarios.delete',
                'gestion_evidencias',
                'evidencias.view',
                'evidencias.create',
                'evidencias.edit',
                'evidencias.delete',
                'gestion_reportes',
                'reportes.generate',
                'gestion_ciclos',
                'ciclos.view',
                'ciclos.create',
                'ciclos.edit',
                'ciclos.delete',
                'gestion_programas',
            ]);
        }

        // Profesor: gestión de evidencias y visualización
        $profesorRole = Role::where('name', 'Profesor')->first();
        if ($profesorRole) {
            $profesorRole->syncPermissions([
                'gestion_evidencias',
                'evidencias.view',
                'evidencias.create',
                'evidencias.edit',
                'reportes.generate',
                'gestion_reportes',
            ]);
        }

        // Encargado de Acreditación: evaluación de evidencias y reportes
        $encargadoRole = Role::where('name', 'Encargado de Acreditación')->first();
        if ($encargadoRole) {
            $encargadoRole->syncPermissions([
                'gestion_evidencias',
                'evidencias.view',
                'evidencias.edit',
                'gestion_reportes',
                'reportes.generate',
                'gestion_ciclos',
                'ciclos.view',
            ]);
        }

        // 7️⃣ Asignar el rol al usuario admin (si ya existe)
        $admin = User::where('email', 'admin@saacuna.local')->first();
        if ($admin && $superRole) {
            $admin->assignRole($superRole);
        }
    }
}
