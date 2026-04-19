<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Middleware que sanitiza respuestas de error para prevenir exposición de información de BD.
 * Se ejecuta DESPUÉS de que el controlador genere la respuesta.
 */
class SanitizeErrorResponses
{
    /**
     * Patrones peligrosos que indican información de BD o técnica.
     */
    private const DANGEROUS_PATTERNS = [
        // SQL y DB errors
        'SQLSTATE',
        'SQL:',
        'PDOException',
        'QueryException',
        'Illuminate\\Database',
        'Doctrine\\DBAL',
        
        // Paths y archivos (pueden revelar estructura)
        'vendor/',
        'app/',
        'database/',
        'migrations/',
        'storage/',
        '.php:',
        'on line',
        'thrown in',
        
        // SQL Keywords y constraints
        'CONSTRAINT',
        'FOREIGN KEY',
        'PRIMARY KEY',
        'UNIQUE KEY',
        'INSERT INTO',
        'UPDATE ',
        'DELETE FROM',
        'SELECT ',
        'DROP TABLE',
        'ALTER TABLE',
        'CREATE TABLE',
        
        // DB schema info
        'Table',
        'Column',
        'Field ',
        'Index',
        "doesn't have a default value",
        'Duplicate entry',
        'Unknown column',
        'Unknown database',
        'Unknown table',
        'Syntax error',
        
        // DB connections
        'Connection:',
        'Connection refused',
        'Access denied',
        'mysql',
        'pgsql',
        'sqlsrv',
        'localhost',
        '127.0.0.1',
        '::1',
        
        // Error codes y debugging
        'Errno:',
        'Error Code:',
        'PDO::',
        'Call Stack',
        'Stack trace',
        '#0 ',
        '#1 ',
        '#2 ',
        '#3 ',
        
        // Framework internals
        'Whoops\\',
        'Symfony\\Component\\',
        'Laravel\\',
        'Illuminate\\',
        
        // Generic DB errors que los controladores usan
        'Database Error',
        'DB Error',
        'Query Error',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Solo sanitizar respuestas JSON con códigos de error
        if ($response instanceof JsonResponse && $response->getStatusCode() >= 400) {
            $data = $response->getData(true);
            
            $sanitized = $this->sanitizeData($data);
            
            // Si se sanitizó algo, actualizar la respuesta
            if ($sanitized !== $data) {
                $response->setData($sanitized);
                
                // Log de sanitización (para debugging)
                \Log::warning('Response sanitized by middleware', [
                    'url' => $request->fullUrl(),
                    'status' => $response->getStatusCode(),
                    'original_keys' => array_keys($data),
                ]);
            }
        }

        return $response;
    }

    /**
     * Sanitiza recursivamente un array de datos.
     */
    private function sanitizeData($data): array
    {
        if (!is_array($data)) {
            return $data;
        }

        // Eliminar campos que típicamente contienen info peligrosa
        $dangerousKeys = ['details', 'trace', 'exception', 'sql', 'bindings'];
        foreach ($dangerousKeys as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }

        // Sanitizar el resto de campos
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            }
        }

        return $data;
    }

    /**
     * Sanitiza un string individual.
     */
    private function sanitizeString(string $text): string
    {
        // CÓDIGOS DE NEGOCIO: Siempre preservar (sin guiones bajos, mayúsculas)
        // Ejemplos: FK_CONSTRAINT, DUPLICATE_ENTRY, VALIDATION_ERROR, UNAUTHORIZED
        if ($this->isBusinessCode($text)) {
            return $text;
        }

        // Preservar mensajes cortos y códigos de negocio (como "FK_CONSTRAINT")
        // que no contienen información técnica peligrosa
        if (strlen($text) < 50 && !$this->containsSqlKeywords($text)) {
            // Solo verificar patrones peligrosos específicos en mensajes cortos
            $criticalPatterns = ['SQLSTATE', 'PDOException', 'QueryException', '.php:', 'vendor/'];
            foreach ($criticalPatterns as $pattern) {
                if (stripos($text, $pattern) !== false) {
                    return 'Error al procesar la solicitud. Contacte al administrador del sistema.';
                }
            }
            return $text;  // Preservar el mensaje original si es seguro
        }

        // Para mensajes largos o con SQL keywords, verificar todos los patrones
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (stripos($text, $pattern) !== false) {
                return 'Error al procesar la solicitud. Contacte al administrador del sistema.';
            }
        }

        // Si es muy largo (probablemente stack trace), sanitizar
        if (strlen($text) > 300) {
            return 'Error al procesar la solicitud. Contacte al administrador del sistema.';
        }

        return $text;
    }

    /**
     * Verifica si el texto es un código de negocio válido (ej: FK_CONSTRAINT, DUPLICATE_ENTRY)
     */
    private function isBusinessCode(string $text): bool
    {
        // Códigos de negocio típicos:
        // - Solo letras, números y guiones bajos
        // - Generalmente en mayúsculas
        // - Cortos (< 50 caracteres)
        return strlen($text) < 50 && preg_match('/^[A-Z0-9_]+$/', $text);
    }

    /**
     * Verifica si el texto contiene keywords SQL reales (no solo la palabra "table" o "column")
     */
    private function containsSqlKeywords(string $text): bool
    {
        // EXCEPCIÓN: "Duplicate Entry" cuando está solo (sin contexto SQL) es un código de error
        // El keyword SQL peligroso es: "Duplicate entry 'value' for key 'index_name'"
        if (trim($text) === 'Duplicate Entry') {
            return false;  // No es SQL, es un código de error del controlador
        }

        $sqlPatterns = [
            'INSERT INTO',
            'UPDATE .*SET',
            'DELETE FROM',
            'SELECT .*FROM',
            'SQLSTATE',
            'Duplicate entry .*for key',  // SQL real tiene contexto
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $text)) {
                return true;
            }
        }

        return false;
    }
}
