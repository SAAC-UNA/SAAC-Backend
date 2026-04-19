<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class Handler extends ExceptionHandler
{
    /**
     * Registra los manejadores de excepciones de la aplicación.
     */
    public function register(): void
    {
        // 404 - Recurso no encontrado por lógica de negocio (servicio)
        $this->renderable(function (\InvalidArgumentException $exception, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->jsonError($exception->getMessage(), 404);
            }
        });

        // 422 - Regla de negocio violada (servicio)
        $this->renderable(function (\LogicException $exception, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->jsonError($exception->getMessage(), 422);
            }
        });

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
                return $this->jsonError(
                    'Recurso no encontrado.',
                    404
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
                    'Acción no autorizada. No tiene permisos suficientes.',
                    403
                );
            }
        });

        // 500 - Error de base de datos: nunca exponer SQL ni esquema, sin importar APP_DEBUG
        $this->renderable(function (QueryException $exception, $request) {
            if ($request->expectsJson()) {
                Log::error('Database Error', [
                    'message'  => $exception->getMessage(),
                    'sql'      => $exception->getSql() ?? 'N/A',
                    'bindings' => $exception->getBindings() ?? [],
                    'code'     => $exception->getCode(),
                    'file'     => $exception->getFile(),
                    'line'     => $exception->getLine(),
                    'user_id'  => Auth::check() ? Auth::id() : null,
                    'url'      => $request->fullUrl(),
                    'ip'       => $request->ip(),
                ]);

                return $this->jsonError(
                    'Error al procesar la solicitud. Contacte al administrador del sistema.',
                    500
                );
            }
        });

        // 500 - Error interno del servidor
        $this->renderable(function (Throwable $exception, $request) {
            if ($request->expectsJson()) {
                Log::error('Internal Server Error', [
                    'type'    => get_class($exception),
                    'message' => $exception->getMessage(),
                    'code'    => $exception->getCode(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTraceAsString(),
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'url'     => $request->fullUrl(),
                    'ip'      => $request->ip(),
                ]);

                return $this->jsonError(
                    $this->getSafeErrorMessage($exception),
                    500
                );
            }
        });
    }

    /**
     * Obtiene un mensaje de error seguro para mostrar al frontend.
     * Filtra información sensible como SQL, rutas de archivos, nombres de tablas.
     */
    private function getSafeErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        $dangerousPatterns = [
            'SQLSTATE', 'SQL:', 'PDOException', 'QueryException',
            'Illuminate\\Database', 'vendor/', 'app/', 'database/',
            'CONSTRAINT', 'FOREIGN KEY', 'INSERT INTO', 'DELETE FROM',
            'doesn\'t have a default value', 'Duplicate entry',
            'Unknown column', 'Unknown database',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return 'Error interno del servidor. Contacte al administrador del sistema.';
            }
        }

        if (strlen($message) > 200) {
            return 'Error interno del servidor. Contacte al administrador del sistema.';
        }

        return $message ?: 'Error interno del servidor. Contacte al administrador del sistema.';
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
