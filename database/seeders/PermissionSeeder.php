<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

/**
 * Seeder de Permisos y Roles del Sistema SAAC-UNA
 * 
 * Lee la configuración desde config/permissions.php (fuente única de verdad)
 * y crea todos los permisos y roles con sus asignaciones correspondientes.
 * 
 * IMPORTANTE: Este seeder es IDEMPOTENTE - puede ejecutarse múltiples veces
 * sin duplicar datos (usa firstOrCreate).
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔐 Iniciando seeder de permisos y roles...');

        // Cargar configuración desde archivo
        $config = config('permissions');
        
        // PASO 1: Crear todos los permisos desde la configuración de módulos
        $this->createPermissions($config['modules']);
        
        // PASO 2: Crear roles del sistema
        $this->createRoles($config['roles']);
        
        // PASO 3: Asignar permisos a cada rol según configuración
        $this->assignPermissionsToRoles($config['roles']);
        
        // PASO 4: Asignar rol Superusuario a usuarios específicos
        $this->assignSuperuserRole();
        
        $this->command->info('✅ Seeder de permisos completado exitosamente');
    }

    /**
     * Crea todos los permisos basados en módulos y acciones.
     * Genera permisos en formato: modulo.accion
     */
    private function createPermissions(array $modules): void
    {
        $this->command->info('📝 Creando permisos...');
        
        $totalPermissions = 0;
        
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$module}.{$action}";
                
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'api',
                ]);
                
                $totalPermissions++;
            }
        }
        
        $this->command->info("  ✅ {$totalPermissions} permisos creados/verificados");
    }

    /**
     * Crea los roles del sistema.
     */
    private function createRoles(array $rolesConfig): void
    {
        $this->command->info('👥 Creando roles...');
        
        foreach (array_keys($rolesConfig) as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api',
            ]);
            
            $this->command->info("  ✅ Rol: {$roleName}");
        }
    }

    /**
     * Asigna permisos a roles según la configuración.
     */
    private function assignPermissionsToRoles(array $rolesConfig): void
    {
        $this->command->info('🔗 Asignando permisos a roles...');
        
        foreach ($rolesConfig as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $this->command->warn("  ⚠️  Rol no encontrado: {$roleName}");
                continue;
            }
            
            // Si el rol tiene 'all_permissions' => true, asignar todos
            if (isset($permissions['all_permissions']) && $permissions['all_permissions'] === true) {
                $role->syncPermissions(Permission::all());
                $this->command->info("  ✅ {$roleName}: TODOS los permisos asignados");
                continue;
            }
            
            // Asignar permisos específicos del array
            $role->syncPermissions($permissions);
            $permissionCount = count($permissions);
            $this->command->info("  ✅ {$roleName}: {$permissionCount} permisos asignados");
        }
    }

    /**
     * Asigna rol Superusuario a usuarios específicos del sistema.
     */
    private function assignSuperuserRole(): void
    {
        $this->command->info('👤 Asignando roles a usuarios...');
        
        $superRole = Role::where('name', 'Superusuario')->first();
        
        if (!$superRole) {
            $this->command->warn('  ⚠️  Rol Superusuario no encontrado');
            return;
        }
        
        // Lista de usuarios que deben tener rol Superusuario
        $superusers = [
            'nayidelin.jiron.castellon@est.una.ac.cr',
        ];
        
        foreach ($superusers as $email) {
            $user = User::where('email', $email)->first();
            
            if ($user) {
                $user->syncRoles(['Superusuario']);
                $this->command->info("  ✅ {$user->nombre}: Superusuario asignado");
            } else {
                $this->command->warn("  ⚠️  Usuario no encontrado: {$email}");
            }
        }
    }
}
