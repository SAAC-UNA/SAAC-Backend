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
        // =====================================================================
        // PASO 1: Crear todos los permisos desde config/permissions.php
        // =====================================================================
        // CAMBIO: antes los permisos se creaban manualmente uno a uno aquí.
        // Problema: cada vez que se agregaba un módulo nuevo en config/permissions.php
        // (ej: 'modelos', 'elemento', 'ciclos') había que recordar venir aquí y
        // crear el permiso a mano — fácil de olvidar y causa errores 403 en producción.
        //
        // SOLUCIÓN: leer 'modules' del config y generar todos los permisos
        // automáticamente. Si se agrega un módulo nuevo al config, el seeder
        // lo crea en BD sin cambio adicional.
        $modulesConfig = config('permissions.modules', []);
        $allPermissionNames = [];
        foreach ($modulesConfig as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
                $allPermissionNames[] = $name;
            }
        }

        // Permisos especiales que no siguen el patrón modulo.accion
        // y por eso no están en 'modules' del config.
        Permission::firstOrCreate(['name' => 'admin.super', 'guard_name' => 'api']);
        $allPermissionNames[] = 'admin.super';

        // Crear permisos de HU-016 (Solicitudes de Ampliación)
        // NOTA: estos ya están en config/permissions.php bajo 'solicitudes_ampliacion',
        // pero se dejan aquí explícitos para documentar que pertenecen a HU-016.
        foreach (['view','create','approve','reject'] as $action) {
            $name = "solicitudes_ampliacion.{$action}";
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
            $allPermissionNames[] = $name;
        }

        // =====================================================================
        // PASO 2: Crear roles y asignar permisos
        // =====================================================================
        $superusuario = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);
        // CAMBIO: antes solo se asignaba 'admin.super' al Superusuario.
        // Problema: las rutas usan middleware 'permission:modelos.view', 'permission:elemento.create',
        // etc. — Spatie verifica el permiso exacto, no si el usuario es "super".
        // Por eso Superusuario recibía 403 en rutas que no tenían 'admin.super'.
        //
        // SOLUCIÓN: syncPermissions con TODOS los permisos del sistema.
        // Esto cumple 'all_permissions = true' definido en config/permissions.php → roles → Superusuario.
        // Si el equipo prefiere otro enfoque, alternativas son:
        //   - Gate::before() en AuthServiceProvider (bypasea todos los checks sin permisos en BD)
        //   - Asignar permisos manualmente por rol (más granular pero más mantenimiento)
        $superusuario->syncPermissions($allPermissionNames);

        $administrador = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'api']);
        $profesor = Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'api']);
        $encargado = Role::firstOrCreate(['name' => 'Encargado de Acreditación', 'guard_name' => 'api']);
        $asistente = Role::firstOrCreate(['name' => 'Asistente de Acreditación', 'guard_name' => 'api']);

        // Asignar permisos al rol "Administrador"
        $administrador->givePermissionTo([
            // Informes de Acreditación (HU-027)
            'informes_acreditacion.view',
            'informes_acreditacion.publish',
            'informes_acreditacion.download',
        ]);

        // Asignar permisos al rol "Profesor"
        $profesor->givePermissionTo([
            // Informes de Acreditación (HU-027): solo lectura y descarga
            'informes_acreditacion.view',
            'informes_acreditacion.download',
        ]);

        // Asignar permisos de HU-016 al rol "Encargado de Acreditación"
        $encargado->givePermissionTo([
            'solicitudes_ampliacion.view',
            'solicitudes_ampliacion.create',
            'solicitudes_ampliacion.approve',
            'solicitudes_ampliacion.reject',
            // Informes de Acreditación (HU-027)
            'informes_acreditacion.view',
            'informes_acreditacion.publish',
            'informes_acreditacion.download',
        ]);

        // Perfil operativo similar para asistente de acreditación
        $asistente->givePermissionTo([
            'solicitudes_ampliacion.view',
            'solicitudes_ampliacion.approve',
            'solicitudes_ampliacion.reject',
            'asignaciones.view',
            'asignaciones.create',
            'asignaciones.edit',
            'evidencias.view',
            'evidencias.assign',
            'aprobaciones.view',
            'reportes.generate',
            'reportes.export',
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
