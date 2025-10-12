<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * Lista de las excepciones que no deben reportarse.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [];

    /**
     * Registra las devoluciones de llamada para el manejo de excepciones.
     */
    public function register(): void
    {
        //
    }

    /**
     * Manejo global de errores: devuelve siempre JSON en peticiones API.
     */
    public function render($request, Throwable $e)
    {
        // Si la petición espera JSON (Postman, frontend, etc.)
        if ($request->expectsJson()) {

            // Determinar el código de estado HTTP (evita warnings de Intelephense)
            $status = ($e instanceof HttpException)
                ? $e->getStatusCode()
                : 500;

            // 🔹 Registrar el error en los logs
            Log::channel('single')->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // 🔹 También registrarlo en el log personalizado de SAAC
            Log::channel('saac')->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->fullUrl(),
                //'user_id' => auth()->id() ?? 'guest',
            ]);

            // Detectar tipo de error y definir mensaje personalizado
            if ($e instanceof ModelNotFoundException) {
                $status = 404;
                $message = 'El recurso solicitado no existe.';
            } elseif ($e instanceof NotFoundHttpException) {
                $status = 404;
                $message = 'La ruta no fue encontrada.';
            } elseif ($e instanceof MethodNotAllowedHttpException) {
                $status = 405;
                $message = 'Método HTTP no permitido para esta ruta.';
            } elseif ($e instanceof QueryException) {
                $status = 500;
                $message = 'Error de base de datos: ' . $e->getMessage();
            } else {
                $message = $e->getMessage() ?: 'Error interno del servidor.';
            }

            // Respuesta JSON estandarizada
            return response()->json([
                'error' => class_basename($e),
                'message' => $message,
                'status' => $status,
                'timestamp' => now()->toDateTimeString(),
                'path' => $request->path(),
                //'user' => auth()->user()->email ?? 'No autenticado',
            ], $status);
        }

        // Si no es una petición JSON, usar el render normal (HTML)
        return parent::render($request, $e);
    }
}
