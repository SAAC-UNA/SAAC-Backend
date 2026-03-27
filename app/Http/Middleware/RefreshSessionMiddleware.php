<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshSessionMiddleware
{
    /**
    * Handle an incoming request.
    *
    * Middleware no-op: la expiración de autenticación la gestiona Sanctum
    * mediante expires_at del token.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
