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

        // Crear roles del sistema
        $superusuario = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);
        $superusuario->givePermissionTo('admin.super');

        $administrador = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'api']);
        $profesor = Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'api']);
        $encargado = Role::firstOrCreate(['name' => 'Encargado de Acreditación', 'guard_name' => 'api']);

        // Usuario admin de prueba
        $admin = User::firstOrCreate(
            ['cedula' => '0001', 'email' => 'admin@saacuna.local'],
            ['nombre' => 'Super Admin', 'status' => 'active']
        );
        $admin->assignRole('Superusuario');
    }
}
