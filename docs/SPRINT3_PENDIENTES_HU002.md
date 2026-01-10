# 📋 TODOs Sprint 3 - UserController (HU-002)

**Archivo**: `app/Http/Controllers/UserController.php`  
**Estado HU-002**: Backend 100% funcional | Sprint 3 = mejoras de seguridad  
**Fecha**: 9 Enero 2026

---

## ✅ Estado Actual HU-002

**El backend del HU-002 está 100% FUNCIONAL**:
- ✅ Todos los endpoints funcionan correctamente
- ✅ Bitácora implementada en las 4 acciones (activate, deactivate, assignRole, assignPermissions)
- ✅ Validaciones funcionando
- ✅ Business logic con regla de mínimos implementada

**Los TODOs de "Sprint 3" son MEJORAS OPCIONALES** para cuando ya tengan autenticación LDAP completa en producción.

---

## 🔍 Análisis de los 5 TODOs Sprint 3

### 1. Gate de Autorización (Línea 10, 139-141)

**Código actual**:
```php
//use Illuminate\Support\Facades\Gate; 3 sprint  // ← COMENTADO línea 10

public function assignRole(AssignRoleRequest $request, User $user)
{
    // Verificar autorización (solo usuarios con permiso pueden asignar roles)
    // DEV: simula usuario que realiza la acción (quien "administra")
    //$acting = User::where('email', 'admin@saacuna.local')->first(); 
    // Gate::forUser($acting)->authorize('usuarios.edit'); 
    // TODO Sprint 3: quitar forUser y usar usuario autenticado (LDAP)
    // o: if (\Gate::denies('usuarios.edit')) abort(403, 'No tiene permiso para editar usuarios');
```

**¿Qué significa?**
- En desarrollo simulaban un usuario admin para probar autorización
- Ahora que tienen LDAP funcionando, pueden usar el usuario REAL autenticado

**¿Qué hacer en Sprint 3?**

1. **Descomentar** línea 10:
```php
use Illuminate\Support\Facades\Gate;
```

2. **Agregar autorización real** en los 3 métodos (assignRole, activate, deactivate):

**Opción A - Más estricta** (requiere permiso específico):
```php
public function assignRole(AssignRoleRequest $request, User $user)
{
    // Verificar que el usuario autenticado tenga permiso usuarios.edit
    Gate::authorize('usuarios.edit');
    
    $roleName = $request->string('role')->trim();
    $this->userAdmin->assignRole($user, $roleName);
    // ...resto del código
}
```

**Opción B - Más flexible** (por rol):
```php
public function assignRole(AssignRoleRequest $request, User $user)
{
    // Solo Superusuarios y Administradores pueden asignar roles
    if (!$request->user()->hasAnyRole(['Superusuario', 'Administrador'])) {
        abort(403, 'No tiene permiso para asignar roles');
    }
    
    $roleName = $request->string('role')->trim();
    $this->userAdmin->assignRole($user, $roleName);
    // ...resto del código
}
```

**¿Está relacionado con HU-002?**
- ✅ Sí, forma parte de la **seguridad** del HU-002
- ⚠️ Pero **NO es bloqueante** para funcionalidad básica
- 🔒 Es una mejora de **producción** (actualmente cualquier usuario autenticado puede asignar roles)

---

### 2. Events de Auditoría (Líneas 109, 132, 155, 177)

**Código actual**:
```php
public function activate(User $user): JsonResponse
{
    // ...lógica de activación...
    
    AuditLogService::log(
        'activar',
        "Usuario activado: {$user->nombre} (ID: {$user->usuario_id})",
        'Usuarios'
    );
    
    // TODO Sprint 3: event(new UserAdminActionPerformed(... 'activate' ...));
    return response()->json(['message' => 'Usuario activado'], 200);
}
```

**¿Qué significa?**
- Actualmente usan `AuditLogService::log()` para registrar en bitácora ✅ **YA FUNCIONA**
- Los eventos (events) son un patrón adicional de Laravel para **reaccionar** a acciones

**¿Qué hace un evento?**
Permite que múltiples partes del sistema reaccionen a una acción:
- Enviar notificación por email al usuario activado
- Enviar webhook a sistema externo
- Actualizar cache de usuarios activos
- Registrar en sistema de analytics
- Enviar notificación real-time (WebSockets)

**Ejemplo de implementación**:

1. **Crear evento** `app/Events/UserAdminActionPerformed.php`:
```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAdminActionPerformed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,           // Usuario afectado
        public string $action,       // 'activate', 'deactivate', 'assign_role', etc.
        public User $performedBy,    // Quién realizó la acción
        public array $metadata = []  // Datos extra (rol asignado, permisos, etc.)
    ) {}
}
```

2. **Disparar evento** en UserController:
```php
public function activate(User $user): JsonResponse
{
    $this->userAdmin->activate($user);
    
    // Registrar en bitácora (ya existe)
    AuditLogService::log(
        'activar',
        "Usuario activado: {$user->nombre} (ID: {$user->usuario_id})",
        'Usuarios'
    );
    
    // Disparar evento (NUEVO)
    event(new UserAdminActionPerformed(
        user: $user,
        action: 'activate',
        performedBy: $request->user(), // Usuario LDAP autenticado
        metadata: []
    ));
    
    return response()->json(['message' => 'Usuario activado'], 200);
}
```

3. **Crear listener** `app/Listeners/SendUserActivationNotification.php`:
```php
<?php

namespace App\Listeners;

use App\Events\UserAdminActionPerformed;
use Illuminate\Support\Facades\Mail;

class SendUserActivationNotification
{
    public function handle(UserAdminActionPerformed $event): void
    {
        if ($event->action === 'activate') {
            // Enviar email al usuario activado
            Mail::to($event->user->email)->send(
                new \App\Mail\UserActivatedNotification($event->user)
            );
        }
    }
}
```

4. **Registrar listener** en `app/Providers/EventServiceProvider.php`:
```php
protected $listen = [
    UserAdminActionPerformed::class => [
        SendUserActivationNotification::class,
        LogToExternalSystem::class,
        UpdateUserCache::class,
    ],
];
```

**¿Está relacionado con HU-002?**
- ✅ Sí, es una **extensión** del HU-002
- ⚠️ Pero **NO es necesario** para funcionalidad básica
- 🚀 Es una mejora para **notificaciones y reactividad** (Sprint 3+)

**¿Por qué no lo implementaron aún?**
- Bitácora (AuditLogService) ya cumple el requisito básico de auditoría
- Events son para funcionalidades avanzadas (emails, webhooks, notificaciones real-time)
- Pueden agregarlo después sin romper nada

---

## 📊 Resumen: ¿Qué falta del HU-002?

### Backend (Tu responsabilidad)

| Componente | Estado | Bloqueante HU-002 | Sprint |
|------------|--------|-------------------|--------|
| Endpoints CRUD usuarios | ✅ Completo | N/A | Sprint 2 |
| Activar/Desactivar | ✅ Completo | N/A | Sprint 2 |
| Asignar rol | ✅ Completo | N/A | Sprint 2 |
| Asignar permisos por módulo | ✅ Completo | N/A | Sprint 2 |
| Bitácora (AuditLogService) | ✅ Implementado en 4 acciones | N/A | Sprint 2 |
| Validaciones (Requests) | ✅ Completo | N/A | Sprint 2 |
| Business logic (UserAdminService) | ✅ Completo con regla mínimos | N/A | Sprint 2 |
| **Gates de autorización** | ⚠️ Comentado | ❌ No bloqueante | **Sprint 3** |
| **Events de auditoría** | ⚠️ Comentado (TODO) | ❌ No bloqueante | **Sprint 3+** |

### Frontend (No es tu responsabilidad)

| Componente | Estado | Bloqueante HU-002 | Sprint |
|------------|--------|-------------------|--------|
| Listar usuarios | ✅ Completo | N/A | Sprint 2 |
| Ver detalles (modal ojo) | ✅ Completo | N/A | Sprint 2 |
| Activar/Desactivar | ✅ Completo | N/A | Sprint 2 |
| Editar rol (modal lápiz) | ✅ Completo | N/A | Sprint 2 |
| **UI asignar permisos individuales** | ❌ Falta | ✅ **BLOQUEANTE** | **Sprint 2 (pendiente)** |

---

## 🎯 Conclusión para ti (Backend Developer)

### ¿Tu parte del HU-002 está completa?
**✅ SÍ, 100% funcional**

### ¿Qué debes hacer ahora?
**Nada urgente**. Puedes:

1. **Opción A - Esperar Sprint 3**: Dejar los TODOs como están
2. **Opción B - Implementar ahora**: Descomentar Gates y agregar eventos (mejora calidad)

### ¿Los TODOs bloquean el HU-002?
**❌ NO**. Son mejoras de seguridad y extensibilidad para **producción**.

### Prioridad de los TODOs:

#### 🔴 Alta prioridad (antes de producción):
```php
// 1. Autorización real con usuario LDAP
Gate::authorize('usuarios.edit');
```
**Razón**: Sin esto, cualquier usuario autenticado puede manipular usuarios.

#### 🟡 Media prioridad (Sprint 3-4):
```php
// 2. Events para notificaciones
event(new UserAdminActionPerformed(...));
```
**Razón**: Útil si necesitan enviar emails/notificaciones, pero no urgente.

#### 🟢 Baja prioridad (Sprint 3-4):
```php
// 3. Middleware en rutas (alternativa a Gates individuales)
Route::middleware(['role:Superusuario|Administrador'])->group(...)
```
**Razón**: Los Gates ya dan autorización granular, middleware es opcional.

---

## 📝 Código Listo para Sprint 3

Si decides implementar los Gates ahora, aquí está el código completo:

### 1. Descomentar import (línea 10):
```php
use Illuminate\Support\Facades\Gate;
```

### 2. Agregar autorización en assignRole (después línea 136):
```php
public function assignRole(AssignRoleRequest $request, User $user)
{
    // Autorizar que el usuario autenticado tenga permiso usuarios.edit
    Gate::authorize('usuarios.edit');
    
    $roleName = $request->string('role')->trim();
    $this->userAdmin->assignRole($user, $roleName);
    
    // Registrar en bitácora
    AuditLogService::log(
        'asignar_rol',
        "Rol '{$roleName}' asignado a: {$user->nombre} (ID: {$user->usuario_id})",
        'Usuarios'
    );
    
    return response()->json([
        'message' => 'Rol asignado correctamente',
        'user_id' => $user->usuario_id,
        'role'    => $roleName,
    ], 200);
}
```

### 3. Agregar autorización en assignPermissions (después línea 161):
```php
public function assignPermissions(AssignPermissionsRequest $request, User $user): JsonResponse
{
    // Autorizar que el usuario autenticado tenga permiso usuarios.edit
    Gate::authorize('usuarios.edit');
    
    $modules = $request->input('modules', []);
    $this->userAdmin->setModulePermissions($user, $modules);
    
    // Registrar en bitácora
    $permisosAsignados = $user->getDirectPermissions()->pluck('name')->values()->toArray();
    AuditLogService::log(
        'asignar_permisos',
        "Permisos actualizados para: {$user->nombre} (ID: {$user->usuario_id}). Permisos: " . implode(', ', $permisosAsignados),
        'Usuarios'
    );
    
    return response()->json([
        'message' => 'Permisos actualizados correctamente',
        'user_id' => $user->usuario_id,
        'granted' => $permisosAsignados, 
    ], 200);
}
```

### 4. Agregar autorización en activate/deactivate (opcional, si lo requieren):
```php
public function activate(User $user): JsonResponse
{
    Gate::authorize('usuarios.edit');
    
    if ($user->status === User::STATUS_ACTIVE) {
        return response()->json([
            'message' => 'El usuario ya estaba activo',
            'user_id' => $user->usuario_id,
        ], 409);
    }

    $this->userAdmin->activate($user);
    
    AuditLogService::log(
        'activar',
        "Usuario activado: {$user->nombre} (ID: {$user->usuario_id})",
        'Usuarios'
    );
    
    return response()->json(['message' => 'Usuario activado'], 200);
}
```

---

## 🧪 Cómo Probar los Gates

Una vez implementados:

```bash
# 1. Crear token con usuario que NO tiene permiso usuarios.edit
POST /api/auth/login
{
  "cedula": "usuario_sin_permisos",
  "password": "xxx"
}

# 2. Intentar asignar rol (debe fallar 403)
PUT /api/admin/users/5/role
Authorization: Bearer {token_usuario_sin_permiso}
{
  "role": "Coordinador"
}

# Respuesta esperada:
{
  "message": "This action is unauthorized."
}

# 3. Crear token con Superusuario
POST /api/auth/login
{
  "cedula": "203849675",  # Pablo (Superusuario)
  "password": "una2024"
}

# 4. Intentar asignar rol (debe funcionar 200)
PUT /api/admin/users/5/role
Authorization: Bearer {token_superusuario}
{
  "role": "Coordinador"
}

# Respuesta esperada:
{
  "message": "Rol asignado correctamente",
  "user_id": 5,
  "role": "Coordinador"
}
```

---

## ❓ FAQ

### ¿Debo implementar los TODOs ahora?
**Depende**:
- Si están en **desarrollo/pruebas**: No es urgente
- Si van a **producción pronto**: Sí, implementar autorización (Gates)
- Events pueden esperar hasta tener requisito de notificaciones

### ¿Puedo eliminar los comentarios TODO?
**No todavía**. Déjalos hasta Sprint 3 o hasta que se implementen.

### ¿El HU-002 está completo sin los TODOs?
**Sí**, funcionalmente está completo. Los TODOs son **mejoras de calidad** para producción.

### ¿Qué pasa si no implemento Gates?
Cualquier usuario autenticado (aunque sea un rol básico) podrá:
- Activar/desactivar usuarios
- Asignar roles
- Asignar permisos

Es un **riesgo de seguridad** en producción.

### ¿Qué pasa si no implemento Events?
Nada. El sistema funciona igual. Solo que si después quieren:
- Enviar emails cuando activen usuarios
- Notificaciones push
- Webhooks a sistemas externos

Tendrán que agregarlo después.

---

**Resumen ejecutivo**: Tu parte del backend del HU-002 está 100% completa y funcional. Los TODOs de Sprint 3 son mejoras de seguridad (Gates) y extensibilidad (Events) para producción, pero NO bloquean la funcionalidad actual.
