<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ElementoPermissionsSeeder extends Seeder
{
    /**
     * Seed para agregar los permisos de ELEMENTO
     * 
     * Ejecutar con: php artisan db:seed --class=ElementoPermissionsSeeder
     */
    public function run(): void
    {
        // Definir permisos de elemento
        $permissions = [
            ['name' => 'elemento.view', 'guard_name' => 'api'],
            ['name' => 'elemento.create', 'guard_name' => 'api'],
            ['name' => 'elemento.edit', 'guard_name' => 'api'],
            ['name' => 'elemento.delete', 'guard_name' => 'api'],
        ];

        // Crear los permisos
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']]
            );
        }

        $this->command->info('✅ Permisos de elemento creados exitosamente');
        
        // Opcional: Asignar permisos al rol Superusuario
        try {
            $superRole = \Spatie\Permission\Models\Role::where('name', 'Superusuario')->first();
            if ($superRole) {
                $superRole->givePermissionTo(['elemento.view', 'elemento.create', 'elemento.edit', 'elemento.delete']);
                $this->command->info('✅ Permisos asignados al rol Superusuario');
            }
        } catch (\Exception $e) {
            $this->command->warn('⚠️ No se pudieron asignar permisos al Superusuario: ' . $e->getMessage());
        }
    }
}
