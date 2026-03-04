<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para extraer el token de la cookie httpOnly
 * y agregarlo al header Authorization para que Sanctum lo reconozca
 */
class AddTokenFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si ya tiene token en Authorization, no hacer nada
        if ($request->bearerToken()) {
            return $next($request);
        }

        // Extraer token de la cookie y agregarlo al header Authorization
        $token = $request->cookie('auth_token');

        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }

}
