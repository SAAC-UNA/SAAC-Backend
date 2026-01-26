# RF-15: Implementación de Seguridad y Autenticación

## 📋 Estándares Aplicados (IS-011-V01)

Este documento detalla la implementación de los estándares de seguridad del proyecto SAAC en el RF-15 (Solicitud de Ampliación de Tiempo para Profesores).

---

## 🔐 1. AUTENTICACIÓN (PL-10)

### **Middleware Implementado:**

```php
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])
```

### **Componentes:**

- **`auth:sanctum`**: Valida token de autenticación en cada petición
- **`refresh.session`**: Renueva el TTL de sesión en Redis (30 min)
- **`throttle:60,1`**: Rate limiting de 60 peticiones/minuto

### **Flujo de Autenticación:**

1. Usuario inicia sesión con cédula + password → `/api/auth/login`
2. Sistema valida credenciales contra **LDAP** (dc=saac,dc=una,dc=cr)
3. Si es válido, sincroniza usuario en BD local
4. Genera token **Sanctum** con expiración de 24 horas
5. Guarda sesión en **Redis** con TTL de 30 minutos
6. Retorna token + datos del usuario

### **Código Crítico:**

```php
// Controlador: Siempre usar Auth::id(), NUNCA hardcoded
$userId = Auth::id();

if (!$userId) {
    return response()->json(['message' => 'Usuario no autenticado'], 401);
}
```

### **❌ ANTI-PATRÓN (Corregido):**

```php
// ANTES (INCORRECTO):
$userId = Auth::id() ?? 1; // ❌ Hardcoded - violación de seguridad

// AHORA (CORRECTO):
$userId = Auth::id(); // ✅ Usuario autenticado real
if (!$userId) {
    return response()->json(['message' => 'Usuario no autenticado'], 401);
}
```

---

## 🛡️ 2. AUTORIZACIÓN (PL-10)

### **Policy Implementada: `ExtensionTimeRequestPolicy`**

#### **Reglas de Negocio:**

| Acción | Profesor | Encargado Acreditación | Admin/Superusuario |
|--------|----------|------------------------|-------------------|
| **viewAny** (listar) | ✅ Solo SUS solicitudes | ✅ Todas | ✅ Todas |
| **view** (detalle) | ✅ Solo si es creador | ✅ Todas | ✅ Todas |
| **create** | ✅ Sí | ✅ Sí | ✅ Sí |
| **update** | ✅ Solo SUS solicitudes PENDIENTES | ❌ No | ✅ Sí |
| **delete** | ✅ Solo SUS solicitudes PENDIENTES | ❌ No | ✅ Sí |

#### **Implementación:**

```php
// En cada método del controlador:
$this->authorize('view', $extensionRequest);
```

#### **Filtro Automático para Profesores:**

```php
// Si el usuario es Profesor (no admin/encargado), forzar filtro por su usuario_id
if ($user->hasRole('Profesor') && 
    !$user->hasRole(['Encargado de Acreditación', 'Administrador', 'Superusuario'])) {
    $filters['usuario_id'] = $user->usuario_id;
}
```

**Esto garantiza que:**
- Un profesor SOLO ve sus propias solicitudes
- Un encargado (que puede también ser profesor) ve TODAS las solicitudes
- Se previene acceso no autorizado a datos de otros usuarios

---

## 🚦 3. RATE LIMITING (PL-10)

### **Configuración:**

```php
// Rutas generales: 60 peticiones/minuto
Route::middleware(['throttle:60,1'])

// Crear solicitudes: 10 peticiones/minuto (más estricto)
Route::post('/', [ExtensionTimeRequestController::class, 'store'])
    ->middleware('throttle:10,1');
```

### **Propósito:**

- Previene ataques de fuerza bruta
- Protege contra abuso de endpoints
- Limita creación masiva de solicitudes

---

## ✅ 4. VALIDACIÓN Y SANITIZACIÓN (PL-10)

### **FormRequest: `StoreExtensionTimeRequestRequest`**

#### **Reglas de Validación:**

```php
public function rules(): array
{
    return [
        'evidencia_asignacion_id' => 'required|integer|exists:EVIDENCIA_ASIGNACION,evidencia_asignacion_id',
        'motivo' => [
            'required',
            'string',
            'min:10',
            'max:1000',
        ],
        'fecha_sugerida' => 'required|date|after:today',
    ];
}
```

#### **Sanitización Contra XSS:**

```php
protected function prepareForValidation(): void
{
    if ($this->has('motivo')) {
        $this->merge([
            'motivo' => strip_tags($this->motivo), // Elimina etiquetas HTML
        ]);
    }
}
```

### **Validaciones de Negocio (Service Layer):**

1. **Evidencia asignada al usuario**
2. **Plazo ya vencido** (Criterio de Aceptación #3)
3. **Máximo 30 días de ampliación**
4. **No duplicar solicitudes pendientes**

---

## 📝 5. MANEJO DE ERRORES (PL-09/PL-10)

### **Principio: Mensajes Genéricos + Logs Detallados**

#### **Ejemplo de Implementación:**

```php
try {
    // Lógica del negocio...
    
} catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    // Log del intento no autorizado
    \Log::warning('Intento de acceso no autorizado a solicitud', [
        'solicitud_id' => $id,
        'usuario_id' => Auth::id()
    ]);
    
    // Mensaje genérico al usuario (sin detalles técnicos)
    return response()->json(['message' => 'No autorizado'], 403);
    
} catch (\Exception $e) {
    // Log detallado para debugging
    \Log::error('Error al obtener solicitud de ampliación', [
        'solicitud_id' => $id,
        'usuario_id' => Auth::id(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString() // Solo en logs
    ]);
    
    // Mensaje genérico (sin exponer stacktrace)
    return response()->json(['message' => 'Error al obtener la solicitud'], 500);
}
```

### **Logs de Auditoría (Acciones Críticas):**

```php
// Crear solicitud
\Log::info('Solicitud de ampliación creada', [
    'solicitud_id' => $extensionRequest->solicitud_ampliacion_id,
    'usuario_id' => $userId,
    'evidencia_asignacion_id' => $extensionRequest->evidencia_asignacion_id
]);

// Actualizar solicitud
\Log::info('Solicitud de ampliación actualizada', [
    'solicitud_id' => $id,
    'usuario_id' => $userId
]);

// Eliminar solicitud
\Log::info('Solicitud de ampliación eliminada', [
    'solicitud_id' => $id,
    'usuario_id' => $userId
]);
```

---

## 🔒 6. PROTECCIÓN DE ASIGNACIÓN MASIVA (PL-10)

### **Modelo: `ExtensionRequest`**

```php
protected $fillable = [
    'evidencia_asignacion_id',
    'usuario_id',
    'motivo',
    'fecha_sugerida',
    'estado',
    'fecha_resolucion',
    'usuario_resolutor_id',
    'justificacion'
];
```

**Esto previene:**
- Asignación de campos no autorizados (ej. `solicitud_ampliacion_id`)
- Ataques de "mass assignment"
- Modificación de claves primarias

---

## 🧪 7. TESTING DE AUTENTICACIÓN

### **Prueba de Login:**

```powershell
$body = @{ cedula = "203948609"; password = "password123" } | ConvertTo-Json
$response = Invoke-WebRequest -Uri "http://localhost:8000/api/auth/login" `
    -Method POST -Body $body -ContentType "application/json"
$token = ($response.Content | ConvertFrom-Json).token
```

### **Prueba con Token:**

```powershell
$response = Invoke-WebRequest `
    -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer" `
    -Method GET `
    -Headers @{ 
        Authorization = "Bearer $token"
        Accept = "application/json" 
    }
```

---

## 📊 Resumen de Cumplimiento

| Estándar | Descripción | Estado |
|----------|-------------|--------|
| **PL-10** | Autenticación con Middleware | ✅ Implementado |
| **PL-10** | Autorización con Policies | ✅ Implementado |
| **PL-10** | Rate Limiting | ✅ Implementado (60/min general, 10/min crear) |
| **PL-10** | Validación y Sanitización | ✅ Implementado (FormRequest + XSS) |
| **PL-09/PL-10** | Manejo de Errores | ✅ Mensajes genéricos + logs detallados |
| **PL-10** | Control de Asignación Masiva | ✅ `$fillable` definido |
| **PL-16** | Variables de Entorno | ✅ `.env` para credenciales LDAP/Redis |

---

## 🚀 Beneficios Implementados

### **Para el Usuario:**
- ✅ Autenticación con cédula institucional (LDAP)
- ✅ Sesión persiste 30 minutos sin actividad
- ✅ Token válido por 24 horas
- ✅ Mensajes de error claros y profesionales

### **Para el Sistema:**
- ✅ Trazabilidad completa en logs
- ✅ Protección contra ataques XSS, CSRF, fuerza bruta
- ✅ Autorización granular por rol
- ✅ Escalabilidad con Redis

### **Para el Desarrollador:**
- ✅ Código mantenible con Policies
- ✅ Logs detallados para debugging
- ✅ Validación centralizada en FormRequests
- ✅ Middleware reutilizable

---

## 📚 Archivos Modificados

```
app/
├── Http/
│   ├── Controllers/
│   │   └── ExtensionTimeRequestController.php  [ACTUALIZADO]
│   ├── Requests/
│   │   └── StoreExtensionTimeRequestRequest.php [ACTUALIZADO - Sanitización]
│   └── Middleware/
│       └── RefreshSessionMiddleware.php         [EXISTENTE]
├── Policies/
│   └── ExtensionTimeRequestPolicy.php           [EXISTENTE]
└── Models/
    └── ExtensionRequest.php                     [VERIFICADO - $fillable OK]

routes/
└── api.php                                      [ACTUALIZADO - Rate limiting]

docs/
└── RF15_SEGURIDAD_AUTENTICACION.md              [NUEVO - Este documento]
```

---

## 🎯 Casos de Uso de Seguridad

### **Caso 1: Profesor intenta ver solicitud de otro profesor**

```
1. Profesor A hace GET /solicitudes-ampliacion-tiempo/123
2. Policy::view() verifica: $extensionRequest->usuario_id === $user->usuario_id
3. Si NO coincide → 403 Forbidden
4. Log: "Intento de acceso no autorizado a solicitud"
```

### **Caso 2: Usuario no autenticado intenta crear solicitud**

```
1. Request sin header Authorization
2. Middleware auth:sanctum falla
3. Respuesta: 401 Unauthenticated
```

### **Caso 3: Ataque de fuerza bruta**

```
1. Atacante envía 70 peticiones en 1 minuto
2. Middleware throttle:60,1 bloquea peticiones 61-70
3. Respuesta: 429 Too Many Requests
```

### **Caso 4: XSS en campo 'motivo'**

```
1. Atacante envía: motivo = "<script>alert('xss')</script>"
2. prepareForValidation() aplica strip_tags()
3. Valor guardado: "alertxss" (sin etiquetas)
```

---

## ✅ Checklist de Seguridad

- [x] Autenticación con LDAP + Sanctum
- [x] Middleware en todas las rutas del RF-15
- [x] Policies para autorización granular
- [x] Rate limiting configurado
- [x] Sanitización de inputs (XSS)
- [x] Manejo de errores con logs
- [x] $fillable en modelo
- [x] Validaciones de negocio en Service
- [x] Sessions en Redis con TTL
- [x] Logs de auditoría para acciones críticas

---

**Documento generado:** 16 de enero de 2026  
**Responsable:** Implementación de estándares IS-011-V01  
**Proyecto:** SAAC (Sistema de Autoevaluación y Acreditación de Carreras)
