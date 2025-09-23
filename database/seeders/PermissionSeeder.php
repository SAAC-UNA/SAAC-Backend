<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = config('permissions.list'); // lee desde config/permissions.php

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name'       => $permiso,
                'guard_name' => 'api',
            ]);
        }
    }
}
