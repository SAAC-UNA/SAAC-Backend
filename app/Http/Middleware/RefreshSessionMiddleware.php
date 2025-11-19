<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RefreshSessionMiddleware
{
    /**
     * Handle an incoming request.
     * Renueva el TTL de la sesión en Redis cada vez que el usuario hace una petición
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo si el usuario está autenticado
        if ($request->user()) {
            $sessionKey = "session:user:{$request->user()->usuario_id}";
            
            // Verificar si existe la sesión en Redis
            if (Redis::exists($sessionKey)) {
                // Renovar TTL a 30 minutos (1800 segundos)
                Redis::expire($sessionKey, 1800);
            }
        }

        return $next($request);
    }
}
