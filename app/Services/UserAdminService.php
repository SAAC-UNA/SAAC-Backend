<?php

namespace App\Services;

use App\Models\User;

class UserAdminService
{
    public function assignRole(User $user, string $roleName): User
    {
        // 1Verifica si ya tiene ese rol asignado
        if ($user->hasRole($roleName)) {
            abort(response()->json([
                'message' => 'El usuario ya tiene ese rol asignado',
                'user_id' => $user->usuario_id,
                'role'    => $roleName,
            ], 409)); // 409 = Conflict
        }

        // Si no lo tiene, se asigna normalmente
        $user->syncRoles([$roleName]);

        return $user;
    }

    public function activate(User $user): User
    {
        $user->activate();
        return $user;
    }

    public function deactivate(User $user): User
    {
        $user->deactivate();
        return $user;
    }

     /**
     * Asigna permisos por módulo (directos al usuario) a partir del payload de módulos/acciones.
     * Mantiene compatibilidad con alias gestion_* si el set cubre todas las acciones del módulo.
     */
    public function setModulePermissions(User $user, array $modules): User
    {
        // 1) Acciones válidas por módulo (ajústar si agregan más)
        $allowed = [
            'evidencias' => ['view','create','edit','delete'],
            'reportes'   => ['generate'],
            'usuarios'   => ['view','create','edit','delete'],
            'ciclos'     => ['view','create','edit','delete'],
            // TODO: agrega aquí más módulos cuando el FE los envíe
        ];

        $final = [];

        foreach ($modules as $module => $actions) {
            if (!isset($allowed[$module])) {
                // Módulo desconocido => ignorar (o se puede acumular para feedback)
                continue;
            }

            // Normalizar acciones permitidas
            $actions = array_values(array_intersect($actions, $allowed[$module]));
            if (empty($actions)) {
                continue;
            }

            // 2) Regla de mínimos: si hay create/edit/delete, debe incluir view
            if (array_intersect($actions, ['create','edit','delete']) && !in_array('view', $actions, true)) {
                $actions[] = 'view';
            }

            // 3) Generar permisos atómicos "modulo.accion"
            foreach ($actions as $a) {
                $final[] = "{$module}.{$a}";
            }

            // 4) Compat con FE: si cubre todas las acciones del módulo, añade gestion_{modulo}
            $all = $allowed[$module]; sort($all);
            $tmp = $actions;          sort($tmp);

            if ($tmp === $all) {
                $final[] = "gestion_{$module}";
            }
        }

        // 5) Sincronizar permisos directos (no toca los roles)
        $user->syncPermissions($final);

        return $user;
    }
}
