<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Configuración principal de la aplicación.
 *
 * Este archivo inicializa el contenedor de la aplicación,
 * define las rutas disponibles, agrupa los middlewares 
 * por contexto (web/api) y registra el manejo centralizado 
 * de excepciones.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Archivo de rutas web
        web: __DIR__ . '/../routes/web.php',

        // Archivo de rutas API (endpoints públicos y privados)
        api: __DIR__ . '/../routes/api.php',

        // Definición de comandos de consola
        commands: __DIR__ . '/../routes/console.php',

        // Ruta de verificación de estado (health check)
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * Grupo de middlewares para peticiones web.
         *
         * Incluye manejo de sesiones, cookies, CSRF y errores compartidos en vistas.
         */
        $middleware->group('web', [
            // \App\Http\Middleware\EncryptCookies::class, // pendiente hasta implementar autenticación
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /**
         * Grupo de middlewares para peticiones API.
         *
         * Por defecto no incluye autenticación, solo enlaces de rutas (bindings).
         * La autenticación se debe agregar en una fase posterior.
         */
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /**
         * Configuración centralizada de excepciones.
         *
         * Aquí se pueden registrar manejadores personalizados
         * para errores específicos de la aplicación.
         */
    })
    ->create();
