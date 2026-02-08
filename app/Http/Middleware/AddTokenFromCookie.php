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
        // Debug: Ver qué cookies llegan
        \Log::info('AddTokenFromCookie - Cookies:', $request->cookies->all());
        
        // Si ya tiene token en Authorization, no hacer nada
        if ($request->bearerToken()) {
            \Log::info('AddTokenFromCookie - Ya tiene Bearer token');
            return $next($request);
        }

        // Extraer token de la cookie
        $token = $request->cookie('auth_token');

        if ($token) {
            \Log::info('AddTokenFromCookie - Token encontrado en cookie, agregando a header');
            // Agregar el token al header Authorization
            $request->headers->set('Authorization', 'Bearer ' . $token);
        } else {
            \Log::info('AddTokenFromCookie - NO se encontró token en cookie');
        }

        return $next($request);
    }
}
