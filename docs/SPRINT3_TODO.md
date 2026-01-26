# 📋 TODO - Sprint 3: Activar Autenticación LDAP

## ⚠️ Cambios Temporales en HU-016 que Deben Revertirse

Estos cambios se hicieron para permitir pruebas sin autenticación. **DEBEN modificarse cuando se active LDAP en Sprint 3.**

---

## 🔧 Cambios Requeridos

### 1️⃣ **ExtensionRequestController.php**
**Ubicación:** `app/Http/Controllers/ExtensionRequestController.php`

#### **Línea 107-108: Descomentar autorización**
```php
// ❌ ESTADO ACTUAL (TEMPORAL):
// TEMPORAL: Comentado para probar sin autorización
// $this->authorize('create', ExtensionRequest::class);

// ✅ CAMBIAR A:
$this->authorize('create', ExtensionRequest::class);
```

#### **Línea 111-113: Eliminar fallback de usuario**
```php
// ❌ ESTADO ACTUAL (TEMPORAL):
// TEMPORAL: Mientras no haya autenticación, usar usuario hardcodeado
// Cambiar este valor por un usuario_id que exista en tu BD
$usuarioId = Auth::id() ?? 1; // Si no hay auth, usar usuario ID 1

// ✅ CAMBIAR A:
$usuarioId = Auth::id();
```

---

### 2️⃣ **ExtensionRequestService.php**
**Ubicación:** `app/Services/ExtensionRequestService.php`

#### **Líneas 90-96: Descomentar validación de usuario asignado**
```php
// ❌ ESTADO ACTUAL (TEMPORAL):
// TEMPORAL: Comentado para pruebas sin autenticación
// Verificar que el usuario es el asignado
// if ($asignacion->usuario_id !== $usuarioId) {
//     throw new \Exception('Solo el usuario asignado puede solicitar ampliación.');
// }

// ✅ CAMBIAR A (descomentar):
// Verificar que el usuario es el asignado
if ($asignacion->usuario_id !== $usuarioId) {
    throw new \Exception('Solo el usuario asignado puede solicitar ampliación de esta evidencia.');
}
```

---

### 3️⃣ **routes/api.php**
**Ubicación:** `routes/api.php`

#### **Líneas 59-67: Agregar middleware de autenticación**
```php
// ❌ ESTADO ACTUAL (SIN AUTENTICACIÓN):
Route::prefix('solicitudes-ampliacion')->group(function () {
    Route::get('/', [ExtensionRequestController::class, 'index']);
    Route::get('/pendientes', [ExtensionRequestController::class, 'pending']);
    Route::get('/mis-solicitudes', [ExtensionRequestController::class, 'mySolicitudes']);
    Route::get('/{id}', [ExtensionRequestController::class, 'show']);
    Route::post('/', [ExtensionRequestController::class, 'store']);
    Route::post('/{id}/aprobar', [ExtensionRequestController::class, 'approve']);
    Route::post('/{id}/rechazar', [ExtensionRequestController::class, 'reject']);
});

// ✅ CAMBIAR A (con middleware):
Route::middleware(['auth:sanctum'])->prefix('solicitudes-ampliacion')->group(function () {
    Route::get('/', [ExtensionRequestController::class, 'index']);
    Route::get('/pendientes', [ExtensionRequestController::class, 'pending']);
    Route::get('/mis-solicitudes', [ExtensionRequestController::class, 'mySolicitudes']);
    Route::get('/{id}', [ExtensionRequestController::class, 'show']);
    Route::post('/', [ExtensionRequestController::class, 'store']);
    Route::post('/{id}/aprobar', [ExtensionRequestController::class, 'approve']);
    Route::post('/{id}/rechazar', [ExtensionRequestController::class, 'reject']);
});
```

---

## ✅ Checklist de Activación

- [ ] **Verificar** que LDAP esté configurado y funcionando correctamente
- [ ] **Descomentar** autorización en `ExtensionRequestController.php` (línea 108)
- [ ] **Eliminar** fallback `?? 1` en `ExtensionRequestController.php` (línea 113)
- [ ] **Descomentar** validación de usuario asignado en `ExtensionRequestService.php` (líneas 92-95)
- [ ] **Agregar** middleware `auth:sanctum` a rutas en `routes/api.php` (línea 59)
- [ ] **Probar** que las rutas requieran autenticación (deben retornar 401 sin token)
- [ ] **Probar** que solo el usuario asignado pueda crear solicitudes para su evidencia
- [ ] **Verificar** que las políticas (Policy) funcionen correctamente
- [ ] **Ejecutar** pruebas unitarias si existen

---

## 🧪 Pruebas de Validación Post-Activación

### Test 1: Autenticación requerida
```bash
# Sin token - debe retornar 401 Unauthorized
curl -X POST http://localhost:8000/api/solicitudes-ampliacion \
  -H "Content-Type: application/json" \
  -d '{"motivo":"test","evidencia_asignacion_id":1,"fecha_sugerida":"2025-12-20"}'
```

### Test 2: Usuario no asignado - debe retornar error
```bash
# Con token de usuario NO asignado a la evidencia - debe retornar error
# "Solo el usuario asignado puede solicitar ampliación de esta evidencia."
```

### Test 3: Usuario asignado - debe crear solicitud
```bash
# Con token de usuario SÍ asignado - debe retornar 201 Created
```

---

## 📝 Notas Adicionales

### Otros TODOs encontrados en el código:
- `UserController.php` línea 100: Event `UserAdminActionPerformed` para activar usuario
- `UserController.php` línea 115: Event `UserAdminActionPerformed` para desactivar usuario
- `UserController.php` línea 124: Cambiar `forUser()` por usuario autenticado vía LDAP
- `UserController.php` línea 131: Event `UserAdminActionPerformed` para asignar rol
- `UserController.php` línea 144: Event `UserAdminActionPerformed` para asignar permisos

---

## 🔍 Buscar Cambios Temporales

Para encontrar todos los cambios temporales en el proyecto:
```bash
# Buscar comentarios TEMPORAL
grep -r "TEMPORAL" app/

# Buscar TODOs relacionados con Sprint 3
grep -r "TODO.*Sprint 3" app/

# Buscar TODOs relacionados con LDAP
grep -r "TODO.*LDAP" app/
```

---

**Última actualización:** 2025-12-09  
**Sprint actual:** Sprint 2 (HU-016 completada con autenticación deshabilitada)  
**Próxima acción:** Sprint 3 - Implementar y activar LDAP
