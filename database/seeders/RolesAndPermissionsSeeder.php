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
        // Asegura que exista admin.super (por si corren solo este seeder)
        Permission::firstOrCreate(['name' => 'admin.super', 'guard_name' => 'api']);

        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'api']);
        $superadmin->givePermissionTo('admin.super');

        // Usuario admin de prueba
        $admin = User::firstOrCreate(
            ['cedula' => '0001', 'email' => 'admin@saacuna.local'],
            ['nombre' => 'Super Admin', 'status' => 'active']
        );
        $admin->assignRole('superadmin');
    }
}
