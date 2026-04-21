<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Helper para manejo seguro de errores.
 * Previene exposición de información sensible de base de datos al frontend.
 */
class ErrorHelper
{
    /**
     * Patrones peligrosos que indican información de BD que no debe exponerse.
     */
    private const DANGEROUS_PATTERNS = [
        'SQLSTATE', 'SQL:', 'PDOException', 'QueryException',
        'Illuminate\\Database', 'vendor/', 'app/', 'database/',
        'CONSTRAINT', 'FOREIGN KEY', 'INSERT INTO', 'DELETE FROM',
        'doesn\'t have a default value', 'Duplicate entry',
        'Unknown column', 'Unknown database',
        'Connection refused', 'Access denied', 'mysql', 'pgsql', 'sqlsrv',
    ];

    /**
     * Obtiene un mensaje de error seguro para el frontend.
     */
    public static function getSafeMessage(Throwable $exception, string $defaultMessage = 'Error al procesar la solicitud.'): string
    {
        $message = $exception->getMessage();

        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return $defaultMessage;
            }
        }

        if (strlen($message) > 200) {
            return $defaultMessage;
        }

        return $message ?: $defaultMessage;
    }

    /**
     * Loguea el error completo para debug interno sin exponerlo al frontend.
     */
    public static function logError(string $context, Throwable $exception, array $extraData = []): void
    {
        Log::error($context, array_merge([
            'type'    => get_class($exception),
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $exception->getTraceAsString(),
            'user_id' => Auth::check() ? Auth::id() : null,
        ], $extraData));
    }

    /**
     * Loguea el error y retorna una respuesta JSON segura.
     * Uso típico en controladores: return ErrorHelper::jsonResponse($e, 'Error al crear usuario');
     */
    public static function jsonResponse(
        Throwable $exception,
        string $logContext,
        string $userMessage = 'Error al procesar la solicitud.',
        int $statusCode = 500,
        array $extraLogData = []
    ) {
        self::logError($logContext, $exception, $extraLogData);

        $safeMessage = self::getSafeMessage($exception, $userMessage);

        return response()->json([
            'message' => $safeMessage,
        ], $statusCode);
    }
}
