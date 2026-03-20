<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',   // AGREGAR ESTA LÍNEA
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configurar redirección de invitados - NO redirigir en API
        $middleware->redirectGuestsTo(fn (Request $request) => null);
        
        // Grupo web
        $middleware->group('web', [
            // \App\Http\Middleware\EncryptCookies::class,  hasta que ya este la autenticacion
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Grupo api (sin auth por defecto, solo bindings)
        $middleware->group('api', [
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, // NECESARIO para enviar cookies
            \App\Http\Middleware\AddTokenFromCookie::class, // Extraer token de cookie httpOnly
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Alias de middlewares personalizados
        // Habilitar CORS para desarrollo
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Middleware para renovar sesión en Redis en cada petición autenticada
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'refresh.session' => \App\Http\Middleware\RefreshSessionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Forzar respuestas JSON para todas las rutas /api/*
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }
            throw $e;
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        });

        // Spatie lanza su propia excepción (no la de Laravel) — hay que capturarla aparte
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Recurso no encontrado.'], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Datos inválidos.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });
    })->create();
