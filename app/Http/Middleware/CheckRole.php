<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Verifica que el usuario autenticado tenga el rol especificado (Spatie Permissions).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roleName  Nombre del rol requerido (ej: 'Superusuario')
     */
    public function handle(Request $request, Closure $next, string $roleName): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return response()->json([
                'error' => 'No autenticado. Debe iniciar sesión para acceder a este recurso.'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Verificar que el usuario tenga el rol requerido (Spatie hasRole)
        if (!$user->hasRole($roleName)) {
            $roles = $user->getRoleNames()->toArray();
            return response()->json([
                'error' => 'Acceso denegado. Requiere rol: ' . $roleName,
                'roles_actuales' => empty($roles) ? ['Sin rol asignado'] : $roles
            ], 403);
        }

        // Todo correcto, continuar con la petición
        return $next($request);
    }
}
