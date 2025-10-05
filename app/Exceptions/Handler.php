<?php

namespace App\Exceptions;

use App\Models\User;
use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class Handler extends ExceptionHandler
{
    /**
     * Registra los manejadores de excepciones de la aplicación.
     */
    public function register(): void
    {
        // Manejo personalizado de ModelNotFoundException (404)
        $this->renderable(function (ModelNotFoundException $exception, $request) {
            if ($request->expectsJson()) {
                // Caso particular: usuario no encontrado
                if ($exception->getModel() === User::class) {
                    return response()->json([
                        'error' => 'Usuario no encontrado',
                    ], 404);
                }

                // Caso genérico para otros modelos
                return response()->json([
                    'error' => 'Recurso no encontrado',
                ], 404);
            }
        });

        // 400 - JSON mal formado (cuando el body no es JSON válido)
        $this->renderable(function (BadRequestHttpException $exception, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Bad Request',
                    'message' => 'JSON inválido o mal formado.',
                ], 400);
            }
        });
        

        // 401 - No autenticado (para cuando se activa login/LDAP en Sprint 3)
        $this->renderable(function (AuthenticationException $exception, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Unauthenticated',
                    'message' => 'No autenticado.',
                ], 401);
            }
        });

        // 403 - Prohibido (cuando se usa Gate/Policies)
        $this->renderable(function (AuthorizationException $exception, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Forbidden',
                    'message' => $exception->getMessage() ?: 'Acción no autorizada.',
                ], 403);
            }
        });

        // 500 - Error interno del servidor (fallback para errores inesperados)
        $this->renderable(function (Throwable $exception, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => class_basename($exception),
                    'message' => config('app.debug')
                        ? ($exception->getMessage() ?: 'Error interno del servidor')
                        : 'Error interno del servidor',
                ], 500);
            }
        });


    }
}
