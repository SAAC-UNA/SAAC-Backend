# Mejoras al Manejo de Errores — HU-026

**Rama:** `Hu_028_Publicación-de-informe-de-acreditación-aprobado`  
**Archivos modificados/creados:**
- `app/Exceptions/Handler.php` _(modificado)_
- `app/Helpers/ErrorHelper.php` _(nuevo)_

---

## ¿Qué había antes?

El `Handler.php` ya tenía todos los tipos de excepción cubiertos (400, 401, 403, 404, 422, 500), pero tenía dos problemas:

1. **Los errores `QueryException` y `Throwable` no se logueaban.** Si un error de BD o un error interno ocurría, se respondía genérico al frontend pero no quedaba ningún rastro en los logs del servidor — sin SQL, sin usuario, sin URL. Era imposible hacer debug.

2. **El `Throwable` handler exponía `$exception->getMessage()` al frontend cuando `APP_DEBUG=true`.** En un ambiente de staging con debug activado, un error de BD podía filtrar el query SQL completo al cliente.

---

## ¿Qué se cambió en `Handler.php`?

### 1. `QueryException` — se agregó logging con contexto completo

```php
// ANTES: respondía genérico sin dejar rastro
$this->renderable(function (QueryException $exception, $request) {
    if ($request->expectsJson()) {
        return $this->jsonError('Error al procesar la solicitud...', 500);
    }
});

// AHORA: loguea contexto completo, luego responde genérico
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
        return $this->jsonError('Error al procesar la solicitud...', 500);
    }
});
```

El mensaje que ve el frontend **no cambia** — sigue siendo genérico. Solo se agrega el log interno.

---

### 2. `Throwable` — se reemplazó la lógica de `$debug` por logging + `getSafeErrorMessage()`

```php
// ANTES: en debug=true, exponía getMessage() directamente al frontend
$this->renderable(function (Throwable $exception, $request) {
    if ($request->expectsJson()) {
        $debug = config('app.debug');
        return $this->jsonError(
            $debug
                ? ($exception->getMessage() ?: 'Error interno...')
                : 'Error interno del servidor...',
            500,
            $debug ? ['type' => ..., 'trace' => ...] : null
        );
    }
});

// AHORA: loguea siempre, responde con mensaje filtrado
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
        return $this->jsonError($this->getSafeErrorMessage($exception), 500);
    }
});
```

---

### 3. Nuevo método privado `getSafeErrorMessage()`

Filtra el mensaje antes de enviarlo al frontend. Si el mensaje contiene palabras como `SQLSTATE`, `INSERT INTO`, `vendor/`, `Duplicate entry`, etc., devuelve el mensaje genérico seguro en su lugar.

```php
private function getSafeErrorMessage(Throwable $exception): string
{
    $message = $exception->getMessage();

    $dangerousPatterns = [
        'SQLSTATE', 'SQL:', 'PDOException', 'QueryException',
        'Illuminate\\Database', 'vendor/', 'app/', 'database/',
        'CONSTRAINT', 'FOREIGN KEY', 'INSERT INTO', 'DELETE FROM',
        'Duplicate entry', 'Unknown column', 'Unknown database', ...
    ];

    foreach ($dangerousPatterns as $pattern) {
        if (stripos($message, $pattern) !== false) {
            return 'Error interno del servidor. Contacte al administrador del sistema.';
        }
    }

    // Si el mensaje es muy largo, también lo filtramos (protección extra)
    if (strlen($message) > 200) {
        return 'Error interno del servidor. Contacte al administrador del sistema.';
    }

    return $message ?: 'Error interno del servidor. Contacte al administrador del sistema.';
}
```

---

## ¿Qué se creó en `app/Helpers/ErrorHelper.php`?

Un helper estático reutilizable para usar en controladores. Centraliza el logging y el sanitizado de mensajes para que no se repita la misma lógica en cada `catch`.

### Métodos disponibles

#### `ErrorHelper::logError(string $context, Throwable $e, array $extra)`
Loguea el error con contexto (tipo, mensaje, archivo, línea, trace, user_id). Útil si solo quieres loguear sin responder aún.

#### `ErrorHelper::getSafeMessage(Throwable $e, string $default)`
Retorna el mensaje del error filtrado. Si contiene información sensible de BD, retorna el mensaje por defecto.

#### `ErrorHelper::jsonResponse(Throwable $e, string $logContext, string $userMessage, int $statusCode)`
Combinación de los dos anteriores: loguea el error y retorna directamente una respuesta JSON segura. Ideal para bloques `catch` en controladores.

**Ejemplo de uso en un controlador:**

```php
use App\Helpers\ErrorHelper;

public function store(Request $request): JsonResponse
{
    try {
        // ... lógica ...
    } catch (Throwable $e) {
        return ErrorHelper::jsonResponse(
            $e,
            'Error al crear reporte de acreditación',
            'No se pudo guardar el reporte. Intente nuevamente.'
        );
    }
}
```

---

## ¿Qué se descartó y por qué?

### Middleware `SanitizeErrorResponses`
La propuesta del middleware que intercepta respuestas y filtra texto tiene el riesgo de **falsos positivos**: palabras como `'Table'`, `'Column'`, `'UPDATE '`, `'app/'` pueden aparecer en mensajes de negocio legítimos y el middleware los bloquearía silenciosamente. Por eso se optó por el filtrado explícito en `getSafeErrorMessage()`.

### Exponer nombre del modelo en `ModelNotFoundException`
El handler de `ModelNotFoundException` que expone `class_basename($e->getModel())` en la respuesta fue omitido — revelar el nombre del modelo Eloquent al cliente es información de arquitectura interna.

---

## Impacto en el resto del código

- **No rompe nada.** Solo se agrega logging y se filtra el mensaje de salida.
- **Los controladores existentes no necesitan cambios** — el `Handler.php` global aplica a todas las excepciones no capturadas.
- **Los controladores nuevos pueden usar `ErrorHelper`** para un logging más consistente.
- **Los errores de validación (422), autenticación (401), autorización (403) y negocio (404/400)** no cambiaron — siguen respondiendo como antes.
