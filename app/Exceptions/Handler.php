<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * Registra los manejadores de excepciones de la aplicación.
     */
    public function register(): void
    {
        //  404 - Ruta o recurso no encontrado
        $this->renderable(function (NotFoundHttpException $exception, $request) {
            if ($request->expectsJson()) {
                return $this->jsonError(
                    'Ruta no encontrada. Verifique la URL o el recurso solicitado.',
                    404
                );
            }
        });

        // 404 - Modelo no encontrado (por ejemplo, findOrFail())
        $this->renderable(function (ModelNotFoundException $exception, $request) {
            if ($request->expectsJson()) {
                $model = $exception->getModel()
                    ? class_basename($exception->getModel())
                    : 'Desconocido';

                return $this->jsonError(
                    'Recurso no encontrado en la base de datos.',
                    404,
                    ['model' => $model]
                );
            }
        });

        //  400 - Solicitud mal formada o JSON inválido
        $this->renderable(function (BadRequestHttpException $exception, $request) {
            if ($request->expectsJson()) {
                return $this->jsonError(
                    'Solicitud inválida. Verifique el formato o los datos enviados.',
                    400
                );
            }
        });

        //  401 - No autenticado
        $this->renderable(function (AuthenticationException $exception, $request) {
            if ($request->expectsJson()) {
                return $this->jsonError(
                    'No autenticado. Debe iniciar sesión para acceder a este recurso.',
                    401
                );
            }
        });

        // 403 - No autorizado (falta de permisos)
        $this->renderable(function (AuthorizationException $exception, $request) {
            if ($request->expectsJson()) {
                return $this->jsonError(
                    $exception->getMessage() ?: 'Acción no autorizada. No tiene permisos suficientes.',
                    403
                );
            }
        });

        // 500 - Error interno del servidor
        $this->renderable(function (Throwable $exception, $request) {
            if ($request->expectsJson()) {
                $debug = config('app.debug');

                return $this->jsonError(
                    $debug
                        ? ($exception->getMessage() ?: 'Error interno del servidor.')
                        : 'Error interno del servidor. Contacte al administrador del sistema.',
                    500,
                    $debug ? [
                        'type' => class_basename($exception),
                        'trace' => $exception->getTraceAsString(),
                    ] : null
                );
            }
        });
    }

    /**
     * Estructura de respuesta JSON estandarizada para errores.
     */
    private function jsonError(string $message, int $status, ?array $details = null)
    {
        return response()->json([
            'status'    => 'error',
            'code'      => $status,
            'message'   => $message,
            'details'   => $details,
            'timestamp' => now()->toDateTimeString(),
        ], $status);
    }
}
