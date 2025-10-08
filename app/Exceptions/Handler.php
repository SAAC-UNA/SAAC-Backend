<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Manejador global de excepciones del sistema SAAC-UNA.
 * 
 * Captura y transforma las excepciones en respuestas JSON coherentes y en español.
 * Esto garantiza una salida uniforme para el frontend y facilita el registro de errores.
 */
class Handler extends ExceptionHandler
{
    /**
     * Tipos de excepciones que NO deben reportarse en el log.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $exceptionesNoReportadas = [
        //
    ];

    /**
     * Campos sensibles que nunca deben mostrarse en errores de validación.
     *
     * @var array<int, string>
     */
    protected $camposNoMostrar = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Registra los callbacks para excepciones que pueden ser reportadas.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $excepcion) {
            // Aquí podrías enviar el error a logs, correo o bitácora
        });
    }

    /**
     * Intercepta los errores y devuelve respuestas JSON estructuradas.
     *
     * @param \Illuminate\Http\Request $solicitud
     * @param \Throwable $excepcion
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($solicitud, Throwable $excepcion)
    {
        // 🔹 Error de validación de datos (FormRequest)
        if ($excepcion instanceof ValidationException) {
            return response()->json([
                'error'  => 'Error de validación',
                'detalle_errores' => $excepcion->errors(),
            ], 422);
        }

        // 🔹 Modelo no encontrado (ej. findOrFail falló)
        if ($excepcion instanceof ModelNotFoundException) {
            return response()->json([
                'error'   => 'No encontrado',
                'mensaje' => 'El recurso solicitado no existe o fue eliminado.',
            ], 404);
        }

        // 🔹 Error relacionado con la base de datos
        if ($excepcion instanceof QueryException) {
            return response()->json([
                'error'   => 'Error de base de datos',
                'mensaje' => 'Ocurrió un problema al acceder o modificar datos en la base de datos.',
            ], 500);
        }

        // 🔹 Cualquier otro tipo de error no controlado
        return response()->json([
            'error'   => 'Error interno del servidor',
            'mensaje' => 'Ha ocurrido un error inesperado. Contacte al administrador.',
            // Solo se muestra en modo debug (APP_DEBUG=true)
            'detalle_debug' => config('app.debug') ? $excepcion->getMessage() : null,
        ], 500);
    }
}
