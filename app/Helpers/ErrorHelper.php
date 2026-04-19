<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
        'SQLSTATE',
        'SQL:',
        'PDOException',
        'QueryException',
        'Illuminate\\Database',
        'vendor/',
        'app/',
        'database/',
        'CONSTRAINT',
        'FOREIGN KEY',
        'INSERT INTO',
        'UPDATE',
        'DELETE FROM',
        'SELECT',
        'Table',
        'Column',
        'Field',
        'doesn\'t have a default value',
        'Duplicate entry',
        'Unknown column',
        'Unknown database',
        'Connection',
        'mysql',
        'pgsql',
        'sqlsrv',
    ];

    /**
     * Obtiene un mensaje de error seguro para el frontend.
     * 
     * @param Throwable $exception La excepción original
     * @param string $defaultMessage Mensaje genérico por defecto
     * @return string Mensaje seguro para mostrar al usuario
     */
    public static function getSafeMessage(Throwable $exception, string $defaultMessage = 'Error al procesar la solicitud.'): string
    {
        $message = $exception->getMessage();

        // Si el mensaje contiene información peligrosa, usar mensaje genérico
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return $defaultMessage;
            }
        }

        // Si el mensaje es muy largo (probablemente contiene stack trace)
        if (strlen($message) > 200) {
            return $defaultMessage;
        }

        // Si pasa los filtros, el mensaje es relativamente seguro
        return $message ?: $defaultMessage;
    }

    /**
     * Loguea el error completo para debug sin exponerlo al frontend.
     * 
     * @param string $context Contexto del error (ej: "Error al crear usuario")
     * @param Throwable $exception La excepción
     * @param array $extraData Datos adicionales para el log
     * @return void
     */
    public static function logError(string $context, Throwable $exception, array $extraData = []): void
    {
        Log::error($context, array_merge([
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => Auth::check() ? Auth::id() : null,
        ], $extraData));
    }

    /**
     * Retorna una respuesta JSON de error segura.
     * 
     * @param Throwable $exception La excepción original
     * @param string $logContext Contexto para el log
     * @param string $userMessage Mensaje para el usuario
     * @param int $statusCode Código HTTP
     * @param array $extraLogData Datos extra para logging
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonResponse(
        Throwable $exception,
        string $logContext,
        string $userMessage = 'Error al procesar la solicitud.',
        int $statusCode = 500,
        array $extraLogData = []
    ) {
        // Loguear error completo para debug interno
        self::logError($logContext, $exception, $extraLogData);

        // Mensaje seguro para el usuario (sin detalles técnicos)
        $safeMessage = self::getSafeMessage($exception, $userMessage);

        return response()->json([
            'message' => $safeMessage,
        ], $statusCode);
    }
}
