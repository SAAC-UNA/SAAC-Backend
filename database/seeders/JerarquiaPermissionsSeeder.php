<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class JerarquiaPermissionsSeeder extends Seeder
{
    /**
     * Seed para agregar los permisos de JERARQUIA
     * 
     * Ejecutar con: php artisan db:seed --class=JerarquiaPermissionsSeeder
     */
    public function run(): void
    {
        // Definir permisos de jerarquía
        $permissions = [
            ['name' => 'jerarquia.view', 'guard_name' => 'api'],
            ['name' => 'jerarquia.create', 'guard_name' => 'api'],
            ['name' => 'jerarquia.edit', 'guard_name' => 'api'],
            ['name' => 'jerarquia.delete', 'guard_name' => 'api'],
        ];

        // Crear los permisos
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']]
            );
        }

        $this->command->info('✅ Permisos de jerarquía creados exitosamente');
        
        // Opcional: Asignar permisos al rol Superusuario
        try {
            $superRole = \Spatie\Permission\Models\Role::where('name', 'Superusuario')->first();
            if ($superRole) {
                $superRole->givePermissionTo(['jerarquia.view', 'jerarquia.create', 'jerarquia.edit', 'jerarquia.delete']);
                $this->command->info('✅ Permisos asignados al rol Superusuario');
            }
        } catch (\Exception $e) {
            $this->command->warn('⚠️ No se pudieron asignar permisos al Superusuario: ' . $e->getMessage());
        }
    }
}
