# Autenticación RF-15: Solicitudes de Ampliación de Tiempo

Documentación de la implementación de autenticación y autorización para el RF-15.

## 📋 Resumen

El RF-15 permite a **profesores** solicitar ampliaciones de tiempo para subir evidencias cuando tienen atrasos justificados. Los **encargados de acreditación** revisan y aprueban/rechazan estas solicitudes.

### Caso Especial
Un **encargado de acreditación** puede tener rol de **profesor** simultáneamente, lo que significa que puede:
1. **Solicitar ampliaciones** para sus propias evidencias (actúa como profesor)
2. **Aprobar/rechazar solicitudes** de otros profesores (actúa como encargado)

## 🔐 Arquitectura de Seguridad

### 1. Capa de Autenticación (Middleware)

**Archivo:** `routes/api.php`

```php
Route::middleware(['auth:sanctum', 'refresh.session'])
    ->prefix('solicitudes-ampliacion-tiempo')
    ->group(function () {
        // Todas las rutas requieren token Sanctum válido
    });
```

**Funcionamiento:**
- `auth:sanctum`: Valida token JWT del usuario autenticado
- `refresh.session`: Renueva TTL de sesión en Redis (30 minutos)
- **Respuesta sin token:** `401 Unauthorized`

### 2. Capa de Autorización (Policy)

**Archivo:** `app/Policies/ExtensionTimeRequestPolicy.php`

| Acción | Profesor | Encargado | Admin | Validación Adicional |
|--------|----------|-----------|-------|---------------------|
| `viewAny` (listar) | ✅ Solo suyas | ✅ Todas | ✅ Todas | Filtro forzado en controller |
| `view` (ver detalle) | ✅ Solo suya | ✅ Cualquiera | ✅ Cualquiera | - |
| `create` | ✅ | ✅ | ✅ | Evidencia asignada al usuario |
| `update` | ✅ Solo suya + pendiente | ❌ | ✅ | Solo estado PENDIENTE |
| `delete` | ✅ Solo suya + pendiente | ❌ | ✅ | Solo estado PENDIENTE |
| `approve` | ❌ | ✅ | ✅ | Solo estado PENDIENTE |
| `reject` | ❌ | ✅ | ✅ | Solo estado PENDIENTE |

**Ejemplo de uso en controller:**
```php
$this->authorize('create', ExtensionRequest::class);
$this->authorize('view', $extensionRequest);
```

### 3. Capa de Lógica de Negocio (Service)

**Archivo:** `app/Services/ExtensionTimeRequestService.php`

**Validaciones implementadas:**
1. **Crear solicitud:**
   - ✅ Evidencia existe
   - ✅ Evidencia está asignada al usuario solicitante
   - ✅ Plazo de evidencia está vencido o próximo a vencer
   - ✅ Fecha sugerida no excede 30 días desde hoy
   - ✅ No existe solicitud pendiente para la misma evidencia

2. **Actualizar solicitud:**
   - ✅ Solicitud existe
   - ✅ Solicitud pertenece al usuario
   - ✅ Estado es PENDIENTE
   - ✅ Fecha sugerida válida

3. **Eliminar solicitud:**
   - ✅ Solicitud existe
   - ✅ Solicitud pertenece al usuario
   - ✅ Estado es PENDIENTE

## 🎯 Casos de Uso

### Caso 1: Profesor solicita ampliación

**Flujo:**
1. Profesor se autentica con LDAP → Obtiene token Sanctum
2. Consulta evidencias próximas a vencer: `GET /evidencias/proximas-vencer`
3. Crea solicitud: `POST /solicitudes-ampliacion-tiempo`
   ```json
   {
     "evidencia_asignacion_id": 5,
     "motivo": "Estuve enfermo y no pude subir la evidencia",
     "fecha_sugerida": "2026-02-15"
   }
   ```
4. Sistema valida con Policy: `create` → ✅ Profesor puede crear
5. Service valida:
   - Evidencia #5 está asignada al profesor → ✅
   - Plazo vencido/próximo a vencer → ✅
   - No hay solicitud pendiente → ✅
6. Solicitud creada con estado PENDIENTE

### Caso 2: Encargado revisa solicitudes

**Flujo:**
1. Encargado autenticado consulta: `GET /solicitudes-ampliacion-tiempo?estado=pendiente`
2. Policy `viewAny` → ✅ Encargado puede ver todas
3. Obtiene listado completo del sistema
4. Consulta detalle: `GET /solicitudes-ampliacion-tiempo/123`
5. Aprueba: `POST /solicitudes-ampliacion-tiempo/123/aprobar`
6. Policy `approve` → ✅ Encargado puede aprobar

### Caso 3: Encargado que ES profesor solicita para sí mismo

**Flujo:**
1. Usuario autenticado tiene roles: `['Profesor', 'Encargado de Acreditación']`
2. Consulta SUS evidencias: `GET /evidencias/proximas-vencer` → ✅
3. Crea solicitud: `POST /solicitudes-ampliacion-tiempo` → ✅ (actúa como profesor)
4. Luego puede ver TODAS las solicitudes incluyendo la suya → ✅ (actúa como encargado)
5. **NO puede aprobar su propia solicitud** (debe hacerlo otro encargado)

## 🛡️ Medidas de Seguridad Implementadas

### 1. Segregación de Datos por Rol
```php
// En ExtensionTimeRequestController::index()
if ($user->hasRole('Profesor') && 
    !$user->hasRole(['Encargado de Acreditación', 'Administrador'])) {
    // FORZAR filtro por usuario_id para profesores
    $filters['usuario_id'] = $user->usuario_id;
}
```

**Protección:** Un profesor NUNCA puede ver solicitudes de otros profesores manipulando el query string.

### 2. Validación de Propiedad
```php
// En Service::createRequest()
$assignment = EvidenceAssignment::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
    ->where('usuario_id', $userId)
    ->first();

if (!$assignment) {
    throw ValidationException::withMessages([
        'evidencia_asignacion_id' => 'La evidencia no está asignada a este usuario.'
    ]);
}
```

**Protección:** Un profesor no puede crear solicitudes para evidencias de otros.

### 3. Doble Capa de Autorización
```php
// Controller
$this->authorize('update', $extensionRequest); // Policy valida rol + estado

// Service
if ($extensionRequest->estado !== 'Pendiente') {
    throw ValidationException::withMessages(...); // Validación adicional
}
```

**Protección:** Defensa en profundidad (defense in depth).

### 4. Auditoría
```php
// TODO: Implementar en versión final
AuditLogService::log('solicitud_creada', "Usuario {$user->nombre} creó solicitud #{$id}", 'RF-15');
```

## 🧪 Pruebas de Autenticación

### Test 1: Sin token → 401 Unauthorized
```bash
curl -X GET http://localhost:8000/api/solicitudes-ampliacion-tiempo
# Respuesta: 401 Unauthenticated
```

### Test 2: Profesor ve solo sus solicitudes
```bash
# Login como profesor (cedula: 208330811)
TOKEN=$(curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"cedula":"208330811","password":"password123"}' \
  | jq -r '.token')

# Listar solicitudes
curl -X GET http://localhost:8000/api/solicitudes-ampliacion-tiempo \
  -H "Authorization: Bearer $TOKEN"
  
# Resultado: Solo solicitudes del usuario 208330811
```

### Test 3: Encargado ve todas las solicitudes
```bash
# Login como encargado (cedula: 203849675)
TOKEN=$(curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"cedula":"203849675","password":"password123"}' \
  | jq -r '.token')

# Listar solicitudes
curl -X GET http://localhost:8000/api/solicitudes-ampliacion-tiempo \
  -H "Authorization: Bearer $TOKEN"
  
# Resultado: TODAS las solicitudes del sistema
```

### Test 4: Profesor intenta ver solicitud ajena → 403 Forbidden
```bash
# Profesor intenta ver solicitud de otro profesor
curl -X GET http://localhost:8000/api/solicitudes-ampliacion-tiempo/999 \
  -H "Authorization: Bearer $TOKEN_PROFESOR"
  
# Respuesta: 403 This action is unauthorized
```

## 📝 Endpoints Protegidos

| Método | Endpoint | Autenticación | Autorización | Descripción |
|--------|----------|---------------|--------------|-------------|
| GET | `/solicitudes-ampliacion-tiempo` | Sanctum | Policy: viewAny | Listar solicitudes (filtradas por rol) |
| GET | `/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer` | Sanctum | - | Evidencias del usuario próximas a vencer |
| GET | `/solicitudes-ampliacion-tiempo/{id}` | Sanctum | Policy: view | Detalle de solicitud |
| POST | `/solicitudes-ampliacion-tiempo` | Sanctum | Policy: create | Crear solicitud |
| PUT | `/solicitudes-ampliacion-tiempo/{id}` | Sanctum | Policy: update | Actualizar solicitud pendiente |
| DELETE | `/solicitudes-ampliacion-tiempo/{id}` | Sanctum | Policy: delete | Eliminar solicitud pendiente |

## 🔄 Integración con Sistema de Autenticación

### 1. Login LDAP
```bash
POST /api/auth/login
{
  "cedula": "203948609",
  "password": "password123"
}
```

**Respuesta:**
```json
{
  "user": {
    "id": 2,
    "cedula": "203948609",
    "nombre": "Cristopher Montero Jimenez",
    "roles": [{"name": "Administrador"}]
  },
  "token": "1|abc123..."
}
```

### 2. Uso del Token
```bash
# Todas las peticiones subsiguientes incluyen el token
Authorization: Bearer 1|abc123...
```

### 3. Renovación de Sesión
El middleware `refresh.session` renueva automáticamente el TTL de Redis en cada petición:
- **TTL:** 30 minutos
- **Renovación:** Automática en cada request exitoso
- **Expiración:** Usuario debe hacer login nuevamente

## 🚀 Próximos Pasos

1. **Implementar endpoints de aprobación/rechazo** (actualmente en ExtensionRequestController)
2. **Agregar auditoría** con `AuditLogService`
3. **Notificaciones** cuando solicitud es aprobada/rechazada
4. **Rate limiting** específico para creación de solicitudes
5. **Tests unitarios** de Policies
6. **Tests de integración** con Postman/PHPUnit

## 📚 Referencias

- [Documentación LDAP](./AUTENTICACION_LDAP_REDIS.md)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Laravel Policies](https://laravel.com/docs/authorization#creating-policies)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
