<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Asegura que exista admin.super
        Permission::firstOrCreate(['name' => 'admin.super', 'guard_name' => 'api']);

        // Crear permisos de HU-016 (Solicitudes de Ampliación)
        Permission::firstOrCreate(['name' => 'solicitudes_ampliacion.view', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'solicitudes_ampliacion.create', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'solicitudes_ampliacion.approve', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'solicitudes_ampliacion.reject', 'guard_name' => 'api']);

        // Crear roles del sistema
        $superusuario = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);
        $superusuario->givePermissionTo('admin.super');

        $administrador = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'api']);
        $profesor = Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'api']);
        $encargado = Role::firstOrCreate(['name' => 'Encargado de Acreditación', 'guard_name' => 'api']);

        // Asignar permisos de HU-016 al rol "Encargado de Acreditación"
        $encargado->givePermissionTo([
            'solicitudes_ampliacion.view',
            'solicitudes_ampliacion.create',
            'solicitudes_ampliacion.approve',
            'solicitudes_ampliacion.reject',
        ]);

        // Usuario admin de prueba con múltiples roles para testing
        $admin = User::firstOrCreate(
            ['cedula' => '0001', 'email' => 'admin@saacuna.local'],
            ['nombre' => 'Super Admin', 'status' => 'active']
        );
        // Asignar roles: Superusuario Y Encargado de Acreditación para poder recibir notificaciones
        $admin->syncRoles(['Superusuario', 'Encargado de Acreditación']);
    }
}
