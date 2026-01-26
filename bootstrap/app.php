<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',   // AGREGAR ESTA LÍNEA
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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

        // Configurar respuestas JSON para rutas API cuando falla autenticación
        $middleware->redirectGuestsTo(fn () => throw new \Illuminate\Auth\AuthenticationException());
    })
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
