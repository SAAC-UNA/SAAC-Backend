<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RefreshSessionMiddleware
{
    /**
    * Handle an incoming request.
    *
    * Renueva expiración del token Sanctum y su cookie httpOnly
    * en cada petición autenticada (sliding expiration).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // No renovar durante logout: el endpoint elimina explícitamente el token.
        if ($request->is('api/auth/logout')) {
            return $response;
        }

        $token = $request->user()?->currentAccessToken();
        if (!$token instanceof PersonalAccessToken) {
            return $response;
        }

        $sessionLifetimeMinutes = (int) config('session.lifetime', 120);
        $newExpiration = now()->addMinutes($sessionLifetimeMinutes);

        $token->forceFill([
            'expires_at' => $newExpiration,
        ])->save();

        $plainTextToken = $request->bearerToken();
        if (!$plainTextToken) {
            return $response;
        }

        $cookie = cookie(
            name: 'auth_token',
            value: $plainTextToken,
            minutes: $sessionLifetimeMinutes,
            path: '/',
            domain: '',
            secure: false,
            httpOnly: true,
            raw: false,
            sameSite: 'lax'
        );

        $response->headers->setCookie($cookie);

        return $response;
    }
}
