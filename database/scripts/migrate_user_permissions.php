#!/usr/bin/env php
<?php

/**
 * Script de migración para actualizar roles y permisos de usuarios existentes
 * 
 * Este script:
 * 1. Elimina los roles obsoletos (Docente, Evaluador)
 * 2. Migra usuarios con roles obsoletos a los nuevos roles
 * 3. Asigna permisos directos basados en los roles
 * 
 * USO:
 *   php database/scripts/migrate_user_permissions.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

echo "🔄 Iniciando migración de roles y permisos...\n\n";

// Mapeo de roles obsoletos a nuevos
$roleMapping = [
    'Docente' => 'Profesor',
    'Evaluador' => 'Encargado de Acreditación',
];

DB::beginTransaction();

try {
    // 1. Obtener todos los usuarios con roles obsoletos
    echo "📋 Buscando usuarios con roles obsoletos...\n";
    
    $usersToMigrate = User::role(['Docente', 'Evaluador'])->get();
    echo "   Encontrados: " . $usersToMigrate->count() . " usuarios\n\n";
    
    foreach ($usersToMigrate as $user) {
        echo "👤 Procesando: {$user->nombre} ({$user->email})\n";
        
        $currentRoles = $user->getRoleNames()->toArray();
        $newRoles = [];
        
        foreach ($currentRoles as $roleName) {
            if (isset($roleMapping[$roleName])) {
                $newRole = $roleMapping[$roleName];
                $newRoles[] = $newRole;
                echo "   ✅ Cambiando rol: {$roleName} → {$newRole}\n";
            } else {
                $newRoles[] = $roleName;
                echo "   ℹ️  Manteniendo rol: {$roleName}\n";
            }
        }
        
        // Asignar nuevos roles
        $user->syncRoles($newRoles);
        
        // Asignar permisos directos basados en los roles
        $permissions = [];
        foreach ($newRoles as $roleName) {
            $role = Role::where('name', $roleName)
                ->where('guard_name', 'api')
                ->first();
            if ($role) {
                $permissions = array_merge($permissions, $role->permissions->pluck('name')->toArray());
            }
        }
        
        if (!empty($permissions)) {
            $user->syncPermissions(array_unique($permissions));
            echo "   🔐 Permisos asignados: " . count(array_unique($permissions)) . "\n";
        }
        
        echo "\n";
    }
    
    // 2. Eliminar roles obsoletos si no tienen usuarios
    echo "🗑️  Verificando roles obsoletos...\n";
    
    foreach ($roleMapping as $oldRole => $newRole) {
        $role = Role::where('name', $oldRole)->where('guard_name', 'api')->first();
        
        if ($role) {
            $usersCount = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->count();
                
            if ($usersCount === 0) {
                $role->delete();
                echo "   ✅ Rol eliminado: {$oldRole} (sin usuarios)\n";
            } else {
                echo "   ⚠️  Rol {$oldRole} aún tiene {$usersCount} usuario(s) asignados\n";
            }
        }
    }
    
    DB::commit();
    
    echo "\n✨ Migración completada exitosamente!\n";
    echo "📊 Total usuarios actualizados: " . $usersToMigrate->count() . "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error durante la migración: " . $e->getMessage() . "\n";
    echo "   Todos los cambios han sido revertidos.\n";
    exit(1);
}
