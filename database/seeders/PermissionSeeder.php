<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;


class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = [
            'Usuarios'   => ['gestion_usuarios'], // duda si poner gestion_usuarios directo sin bloques
            'Evidencias' => ['gestion_evidencias'],
            'Reportes'   => ['gestion_reportes'],
            'Cyclos'     => ['gestion_ciclos'],
            'Programas'  => ['gestion_programas'],
            'roles'      => ['gestion_roles'],
        ];

         foreach ($modulos as $modulo => $listaPermisos) {
            foreach ($listaPermisos as $permiso) {
                Permission::firstOrCreate([
                    'name'       => $permiso,
                    'guard_name' => 'api',
                ]);
            }
        }
    }
}