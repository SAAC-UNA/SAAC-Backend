<?php

namespace App\Http\Middleware;

use App\Support\AccessResolver;
use Closure;
use Spatie\Permission\Middleware\PermissionMiddleware as SpatiePermissionMiddleware;

class PermissionMiddleware extends SpatiePermissionMiddleware
{
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        $requested = array_filter(array_map('trim', explode('|', (string) $permission)));
        $expanded = AccessResolver::expandPermissions($requested);
        $permissionString = implode('|', $expanded);

        return parent::handle($request, $next, $permissionString, $guard);
    }
}
