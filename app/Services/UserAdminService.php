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
        return $user->fresh();
    }

    public function deactivate(User $user): User
    {
        $user->deactivate();
        return $user->fresh();
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

    /**
     * Asigna carreras a un usuario con control de alcance por permisos.
     *
     * Reglas:
     * - `usuarios.assign`: puede asignar carreras a usuarios operativos.
     * - `usuarios.approve`: puede asignar carreras a usuarios que también delegan (`usuarios.assign`).
     * - No Superusuario: solo puede asignar carreras que él mismo tiene asociadas.
     */
    public function setCareers(User $actor, User $target, array $careerIds): User
    {
        $canAssign = $actor->can('usuarios.assign') || $actor->can('usuarios.approve');
        if (!$canAssign) {
            abort(response()->json([
                'message' => 'No tiene permisos para asignar carreras.',
            ], 403));
        }

        $targetDelegates = $target->can('usuarios.assign') || $target->can('usuarios.approve');
        if ($targetDelegates && !$actor->can('usuarios.approve')) {
            abort(response()->json([
                'message' => 'Se requiere permiso de delegación para asignar carreras a este usuario.',
            ], 403));
        }

        $careerIds = array_values(array_unique(array_map('intval', $careerIds)));

        if (!$actor->hasRole('Superusuario')) {
            $allowedCareerIds = $actor->careers()
                ->pluck('CARRERA_SEDE.carrera_sede_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $notAllowed = array_values(array_diff($careerIds, $allowedCareerIds));
            if ($notAllowed !== []) {
                abort(response()->json([
                    'message' => 'Solo puede asignar carreras dentro de su alcance.',
                    'not_allowed_careers' => $notAllowed,
                ], 403));
            }
        }

        $target->careers()->sync($careerIds);

        return $target->fresh(['roles', 'permissions', 'careers.career']);
    }
}
