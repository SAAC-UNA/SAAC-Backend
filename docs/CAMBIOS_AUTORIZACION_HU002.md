# 🔒 Cambios de Autorización - HU-002

**Fecha**: 9 de Enero 2026  
**Rama**: `HU-002-Gestion-de-Usuarios-del-Sistema`  
**Estado**: Backend 100% Completo | Frontend requiere ajustes

---

## 📋 Resumen de Cambios (Backend)

Se activaron las **autorizaciones de Sprint 3** para gestión de usuarios. Ahora solo usuarios autenticados con permiso `usuarios.edit` pueden gestionar usuarios.

---

## 🔧 Cambios Realizados en Backend

### 1. **routes/api.php** (Línea 136)

**Antes:**
```php
Route::prefix('admin/users')->group(function () {
    // Sin autenticación ni validación de permisos
```

**Ahora:**
```php
Route::prefix('admin/users')
    ->middleware(['auth:sanctum', 'permission:usuarios.edit'])
    ->group(function () {
    // Protegido con autenticación y permisos
```

**Impacto:**
- ✅ Requiere token de autenticación válido (`auth:sanctum`)
- ✅ Requiere permiso `usuarios.edit`
- ❌ Sin token → 401 Unauthorized
- ❌ Sin permiso → 403 Forbidden

---

### 2. **UserController.php**

**Cambios:**
- Descomentado: `use Illuminate\Support\Facades\Gate;` (línea 10)
- Agregado `Gate::authorize('usuarios.edit');` en:
  - `activate()` (línea 97)
  - `deactivate()` (línea 122)
  - `assignRole()` (línea 137)
  - `assignPermissions()` (línea 164)

**Antes (ejemplo de assignRole):**
```php
public function assignRole(AssignRoleRequest $request, User $user)
{
    // Sin validación de autorización
    $roleName = $request->string('role')->trim();
    // ...
}
```

**Ahora:**
```php
public function assignRole(AssignRoleRequest $request, User $user)
{
    // Verificar autorización - solo usuarios con permiso usuarios.edit
    Gate::authorize('usuarios.edit');
    
    $roleName = $request->string('role')->trim();
    // ...
}
```

---

### 3. **UserPolicy.php**

Se implementaron las políticas de autorización:

```php
/**
 * Listar usuarios (GET /api/admin/users)
 */
public function viewAny(User $user): bool
{
    return $user->can('usuarios.view');
}

/**
 * Activar, desactivar, asignar roles y permisos
 */
public function update(User $user, User $model): bool
{
    return $user->can('usuarios.edit');
}
```

---

## 🎯 Endpoints Protegidos

Todos estos endpoints ahora requieren autenticación y permisos:

| Método | Endpoint | Permiso Requerido | Respuesta sin permiso |
|--------|----------|-------------------|----------------------|
| GET | `/api/admin/users` | `usuarios.view` o `usuarios.edit` | 403 Forbidden |
| PATCH | `/api/admin/users/{id}/activate` | `usuarios.edit` | 403 Forbidden |
| PATCH | `/api/admin/users/{id}/deactivate` | `usuarios.edit` | 403 Forbidden |
| PUT | `/api/admin/users/{id}/role` | `usuarios.edit` | 403 Forbidden |
| PUT | `/api/admin/users/{id}/permissions` | `usuarios.edit` | 403 Forbidden |

---

## ⚠️ IMPORTANTE para Frontend

### 🔴 Cambios que DEBEN hacerse en Frontend:

#### 1. **Agregar Header de Autenticación en todas las peticiones**

Todas las peticiones a `/api/admin/users/*` DEBEN incluir el token:

```typescript
// En UserService.ts
headers: {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'Authorization': `Bearer ${token}` // ← CRÍTICO: Agregar token
}
```

#### 2. **Manejar errores 401 y 403**

```typescript
// Nuevo manejo de errores
if (response.status === 401) {
  // Token inválido o expirado - redirigir a login
  redirectToLogin();
}

if (response.status === 403) {
  // Usuario sin permisos - mostrar mensaje
  showError('No tienes permisos para realizar esta acción');
}
```

#### 3. **Validar permisos antes de mostrar botones**

```typescript
// En componentes de usuarios
const canManageUsers = currentUser?.permissions?.includes('usuarios.edit');

// Mostrar botones solo si tiene permisos
{canManageUsers && (
  <>
    <button onClick={handleActivate}>Activar</button>
    <button onClick={handleAssignRole}>Asignar Rol</button>
  </>
)}
```

---

## 📦 Estructura de Token

El token JWT debe obtenerse del endpoint de login y guardarse:

```typescript
// Después del login
const response = await login(cedula, password);
const token = response.token;

// Guardar en localStorage o Context
localStorage.setItem('authToken', token);

// Usar en todas las peticiones
const token = localStorage.getItem('authToken');
```

---

## 🧪 Cómo Probar

### Usuarios de prueba (según seeders):

1. **Superusuario** (tiene todos los permisos):
   - Email: `admin@saacuna.local`
   - Puede: Ver, crear, editar, eliminar usuarios

2. **Evaluador** (sin permisos de usuarios):
   - Email: `evaluador@saacuna.local`
   - NO puede: Gestionar usuarios (403)

### Pruebas recomendadas:

```bash
# 1. Sin token (debe dar 401)
curl http://localhost:8000/api/admin/users

# 2. Con token de Evaluador (debe dar 403)
curl -H "Authorization: Bearer TOKEN_EVALUADOR" \
     http://localhost:8000/api/admin/users

# 3. Con token de Superusuario (debe funcionar)
curl -H "Authorization: Bearer TOKEN_SUPERUSUARIO" \
     http://localhost:8000/api/admin/users
```

---

## ✅ Checklist para Frontend

- [ ] Agregar header `Authorization: Bearer {token}` en UserService
- [ ] Implementar manejo de errores 401 (redirigir a login)
- [ ] Implementar manejo de errores 403 (mostrar mensaje)
- [ ] Ocultar/deshabilitar botones según permisos del usuario
- [ ] Probar con usuario sin permisos
- [ ] Probar con usuario con permisos
- [ ] Probar con token expirado

---

## 🔴 PENDIENTE: UI de Asignar Permisos Específicos

**Estado**: Backend ✅ | Frontend ❌

El backend YA soporta asignar permisos específicos por módulo:

```typescript
// Endpoint: PUT /api/admin/users/{id}/permissions
// Body:
{
  "modules": {
    "evidencias": ["view", "create", "edit"],
    "reportes": ["generate"],
    "usuarios": ["view"],
    "ciclos": ["view", "create", "edit", "delete"]
  }
}
```

**Falta implementar en Frontend:**
1. Componente `AssignPermissionsForm.tsx` con checkboxes por módulo
2. Página `AssignPermissions.tsx`
3. Método `assignUserPermissions()` en `UserService.ts`
4. Botón "Permisos" en lista de usuarios

Ver detalles en: `docs/CHECKLIST_HU002_PENDIENTE.md`

---

## 📞 Contacto

Si tienes dudas sobre los cambios o necesitas ayuda con la integración, consulta:
- `docs/DIAGNOSTICO_HU002_GESTION_USUARIOS.md` - Diagnóstico completo
- `docs/CHECKLIST_HU002_PENDIENTE.md` - Lo que falta implementar

---

**Creado por**: Backend Team  
**Última actualización**: 9 Enero 2026
