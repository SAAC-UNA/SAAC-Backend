<?php

namespace App\Support;

use App\Models\User;

class AccessResolver
{
    /** @var array<string, array<int, string>>|null */
    private static ?array $aliasIndex = null;

    /**
     * Determina si un ability parece un permiso de Spatie (modulo.accion).
     */
    public static function isPermissionAbility(string $ability): bool
    {
        return str_contains($ability, '.');
    }

    /**
     * Expande permisos con sus alias equivalentes.
     *
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    public static function expandPermissions(array $permissions): array
    {
        $index = self::aliasIndex();
        $expanded = [];

        foreach ($permissions as $permission) {
            if (!is_string($permission) || $permission === '') {
                continue;
            }

            $expanded[] = $permission;
            foreach ($index[$permission] ?? [] as $alias) {
                $expanded[] = $alias;
            }
        }

        return array_values(array_unique($expanded));
    }

    public static function userHasAnyPermission(User $user, array $permissions): bool
    {
        $expanded = self::expandPermissions($permissions);

        foreach ($expanded as $permission) {
            if ($user->hasPermissionTo($permission, 'api')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $permissionNames
     * @return array<int, string>
     */
    public static function resolveCapabilities(array $permissionNames): array
    {
        $capabilityMap = config('access.capabilities', []);
        $expandedPermissions = array_flip(self::expandPermissions($permissionNames));
        $capabilities = [];

        foreach ($capabilityMap as $capability => $requiredPermissions) {
            if (!is_array($requiredPermissions) || $requiredPermissions === []) {
                continue;
            }

            foreach (self::expandPermissions($requiredPermissions) as $requiredPermission) {
                if (isset($expandedPermissions[$requiredPermission])) {
                    $capabilities[] = $capability;
                    break;
                }
            }
        }

        return array_values(array_unique($capabilities));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function aliasIndex(): array
    {
        if (self::$aliasIndex !== null) {
            return self::$aliasIndex;
        }

        $aliases = config('access.permission_aliases', []);
        $index = [];

        foreach ($aliases as $permission => $aliasList) {
            if (!is_string($permission) || !is_array($aliasList)) {
                continue;
            }

            $group = array_values(array_unique(array_filter(array_merge([$permission], $aliasList), 'is_string')));

            foreach ($group as $entry) {
                $index[$entry] = array_values(array_diff($group, [$entry]));
            }
        }

        self::$aliasIndex = $index;

        return self::$aliasIndex;
    }
}
