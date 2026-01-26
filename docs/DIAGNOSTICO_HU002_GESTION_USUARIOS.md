# 📊 Diagnóstico HU-002: Gestión de Usuarios del Sistema

**Historia de Usuario**: HU-002 - Gestión de Usuarios  
**Fecha Diagnóstico**: 9 de Enero 2026  
**Estado**: En Desarrollo - Funcionalidad Parcial  
**Sprint Actual**: Sprint 2

---

## 📝 Historia de Usuario (Requisitos)

### Descripción
Como superusuario, quiero activar, desactivar o asignar roles a los usuarios registrados, para controlar sus permisos y accesos dentro del sistema.

### Entradas Esperadas
1. ✅ Selección de usuario
2. ✅ Acción a realizar (activar, desactivar, modificar rol)
3. ⚠️ Selección de privilegios por módulo
   - Módulo de evidencias (ver, crear, editar, eliminar)
   - Módulo de reportes (generar reportes)
   - Módulo de usuarios (ver, crear, editar, eliminar usuarios)
   - Módulo de ciclos (ver, crear, editar, eliminar ciclos)
4. ✅ Rol a asignar (si aplica)

### Procesos Requeridos
1. ✅ Validar que el usuario exista
2. ✅ Validar que la acción sea válida
3. ⚠️ **PARCIAL**: Permitir asignar privilegios específicos por módulo
4. ✅ Guardar cambios en BD
5. ✅ Registrar en bitácora

### Salidas Esperadas
1. ✅ Confirmación de operación
2. ✅ Mensajes de error/validación
3. ✅ Registro en bitácora

---

## ✅ Funcionalidades IMPLEMENTADAS

### 1. Backend - Controlador y Rutas

**Archivo**: `app/Http/Controllers/UserController.php`

#### ✅ Listar Usuarios
```php
// Línea 36-44
public function index()
{
    $users = User::with(['roles', 'permissions'])
        ->orderBy('created_at', 'desc')
        ->get();
    return UserResource::collection($users);
}
```
- ✅ Carga usuarios con roles y permisos
- ✅ Evita problema N+1
- ✅ Retorna recursos transformados

#### ✅ Activar Usuario
```php
// Línea 94-110
public function activate(User $user): JsonResponse
{
    if ($user->status === User::STATUS_ACTIVE) {
        return response()->json(['message' => 'El usuario ya estaba activo'], 409);
    }
    $this->userAdmin->activate($user);
    AuditLogService::log('activar', "Usuario activado: {$user->nombre}", 'Usuarios');
    return response()->json(['message' => 'Usuario activado'], 200);
}
```
- ✅ Validación de estado previo (409 si ya activo)
- ✅ Delegación al servicio
- ✅ **Registro en bitácora**
- ✅ Respuesta JSON estándar

#### ✅ Desactivar Usuario
```php
// Línea 112-128
public function deactivate(User $user): JsonResponse
{
    if ($user->status === User::STATUS_INACTIVE) {
        return response()->json(['message' => 'El usuario ya estaba inactivo'], 409);
    }
    $this->userAdmin->deactivate($user);
    AuditLogService::log('desactivar', "Usuario desactivado: {$user->nombre}", 'Usuarios');
    return response()->json(['message' => 'Usuario desactivado'], 200);
}
```
- ✅ Validación de estado previo
- ✅ **Registro en bitácora**

#### ✅ Asignar Rol
```php
// Línea 130-159
public function assignRole(AssignRoleRequest $request, User $user)
{
    $roleName = $request->string('role')->trim();
    $this->userAdmin->assignRole($user, $roleName);
    
    AuditLogService::log(
        'asignar_rol',
        "Rol '{$roleName}' asignado a: {$user->nombre}",
        'Usuarios'
    );
    
    return response()->json([
        'message' => 'Rol asignado correctamente',
        'user_id' => $user->usuario_id,
        'role' => $roleName,
    ], 200);
}
```
- ✅ Validación con FormRequest
- ✅ **Registro en bitácora**
- ✅ Respuesta con datos del cambio

#### ✅ Asignar Permisos por Módulo
```php
// Línea 161-182
public function assignPermissions(AssignPermissionsRequest $request, User $user): JsonResponse
{
    $modules = $request->input('modules', []);
    $this->userAdmin->setModulePermissions($user, $modules);
    
    $permisosAsignados = $user->getDirectPermissions()->pluck('name')->values()->toArray();
    AuditLogService::log(
        'asignar_permisos',
        "Permisos actualizados para: {$user->nombre}. Permisos: " . implode(', ', $permisosAsignados),
        'Usuarios'
    );
    
    return response()->json([
        'message' => 'Permisos actualizados correctamente',
        'user_id' => $user->usuario_id,
        'granted' => $permisosAsignados,
    ], 200);
}
```
- ✅ Recibe estructura por módulos
- ✅ **Registro detallado en bitácora** (incluye lista de permisos)
- ✅ Respuesta con permisos otorgados

### 2. Backend - Servicio de Administración

**Archivo**: `app/Services/UserAdminService.php`

#### ✅ Asignar Rol con Validación
```php
// Línea 9-24
public function assignRole(User $user, string $roleName): User
{
    if ($user->hasRole($roleName)) {
        abort(response()->json([
            'message' => 'El usuario ya tiene ese rol asignado',
            'user_id' => $user->usuario_id,
            'role' => $roleName,
        ], 409));
    }
    $user->syncRoles([$roleName]);
    return $user;
}
```
- ✅ Previene asignación duplicada (409 Conflict)
- ✅ Usa `syncRoles` (reemplaza roles anteriores)

#### ✅ Activar/Desactivar
```php
// Línea 26-35
public function activate(User $user): User
{
    $user->activate();
    return $user;
}

public function deactivate(User $user): User
{
    $user->deactivate();
    return $user;
}
```
- ✅ Delegación limpia al modelo

#### ✅ Asignar Permisos por Módulo (Arquitectura Granular)
```php
// Línea 37-96
public function setModulePermissions(User $user, array $modules): User
{
    $allowed = [
        'evidencias' => ['view','create','edit','delete'],
        'reportes'   => ['generate'],
        'usuarios'   => ['view','create','edit','delete'],
        'ciclos'     => ['view','create','edit','delete'],
    ];

    $final = [];

    foreach ($modules as $module => $actions) {
        if (!isset($allowed[$module])) {
            continue;
        }

        $actions = array_values(array_intersect($actions, $allowed[$module]));
        if (empty($actions)) {
            continue;
        }

        // Regla de mínimos: si hay create/edit/delete, debe incluir view
        if (array_intersect($actions, ['create','edit','delete']) && !in_array('view', $actions, true)) {
            $actions[] = 'view';
        }

        // Generar permisos atómicos "modulo.accion"
        foreach ($actions as $a) {
            $final[] = "{$module}.{$a}";
        }

        // Compatibilidad: si cubre todas las acciones, añade gestion_{modulo}
        $all = $allowed[$module]; sort($all);
        $tmp = $actions;          sort($tmp);

        if ($tmp === $all) {
            $final[] = "gestion_{$module}";
        }
    }

    $user->syncPermissions($final);
    return $user;
}
```

**Características**:
- ✅ Arquitectura modular (evidencias, reportes, usuarios, ciclos)
- ✅ **Regla de mínimos**: Si tiene create/edit/delete → automáticamente agrega view
- ✅ Validación contra lista blanca (`$allowed`)
- ✅ Genera permisos atómicos: `evidencias.view`, `usuarios.create`, etc.
- ✅ **Compatibilidad retroactiva**: Si cubre todas las acciones → agrega `gestion_modulo`
- ✅ `syncPermissions` (reemplaza permisos directos, no toca roles)

### 3. Backend - Rutas API

**Archivo**: `routes/api.php` (líneas 136-149)

```php
Route::prefix('admin/users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    
    // HU-002: Activar/Desactivar
    Route::patch('{user}/activate',   [UserController::class, 'activate'])
        ->missing(fn() => response()->json(['error' => 'Usuario no encontrado'], 404));
    Route::patch('{user}/deactivate', [UserController::class, 'deactivate'])
        ->missing(fn() => response()->json(['error' => 'Usuario no encontrado'], 404));
    
    // HU-002: Asignar Rol
    Route::put('{user}/role', [UserController::class, 'assignRole'])
        ->missing(fn() => response()->json(['error' => 'Usuario no encontrado'], 404));
    
    // HU-002: Asignar Permisos
    Route::put('{user}/permissions', [UserController::class, 'assignPermissions'])
        ->missing(fn() => response()->json(['error' => 'Usuario no encontrado'], 404));
});
```

- ✅ Rutas RESTful
- ✅ Manejo de 404 personalizado
- ✅ Agrupación lógica bajo `/admin/users`

### 4. Frontend - Servicio de Usuarios

**Archivo**: `SAAC-Frontend/src/Services/UserService.ts`

#### ✅ Listar Usuarios
```typescript
// Línea 72-92
async listUsers(): Promise<BackendUser[]> {
  const response = await fetch(this.baseURL, {
    method: 'GET',
    headers: { 'Accept': 'application/json' },
  });
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  const result = await response.json();
  return Array.isArray(result) ? result : result.data || [];
}
```

#### ✅ Activar Usuario
```typescript
// Línea 98-113
async activateUser(userId: number): Promise<ApiResponse<User>> {
  const response = await fetch(`${this.baseURL}/${userId}/activate`, {
    method: 'PATCH',
    headers: { 'Accept': 'application/json' },
  });
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  return await response.json();
}
```

#### ✅ Desactivar Usuario
```typescript
// Línea 118-133
async deactivateUser(userId: number): Promise<ApiResponse<User>> {
  const response = await fetch(`${this.baseURL}/${userId}/deactivate`, {
    method: 'PATCH',
    headers: { 'Accept': 'application/json' },
  });
  
  return await response.json();
}
```

#### ✅ Asignar Rol
```typescript
// Línea 138-157
async assignUserRole(userId: number, roleName: string): Promise<ApiResponse> {
  const response = await fetch(`${this.baseURL}/${userId}/role`, {
    method: 'PUT',
    headers: { 
      'Content-Type': 'application/json',
      'Accept': 'application/json' 
    },
    body: JSON.stringify({ role: roleName }),
  });
  
  return await response.json();
}
```

### 5. Frontend - Componentes UI

**Archivos Clave**:

#### ✅ Lista de Usuarios
- `SAAC-Frontend/src/Pages/Users/UsersList.tsx`
- ✅ Tabla con usuarios
- ✅ Botones: Ver detalles (ojo), Editar (lápiz), Activar/Desactivar (switch)
- ✅ Modales de confirmación

#### ✅ Editar Usuario
- `SAAC-Frontend/src/Pages/Users/EditUser.tsx`
- `SAAC-Frontend/src/Pages/Users/Components/EditUserForm.tsx`
- ✅ Formulario con información del usuario (solo lectura)
- ✅ **CustomSelect para seleccionar rol único**
- ✅ Vista previa de permisos del rol seleccionado
- ✅ Modal de confirmación antes de guardar
- ✅ Modal de éxito después de guardar

#### ✅ Detalle de Usuario
- `SAAC-Frontend/src/Components/Features/Users/UserDetailsModal.tsx`
- ✅ Muestra información completa del usuario
- ✅ Roles asignados
- ✅ **Permisos directos y efectivos** con etiquetas del backend

### 6. Frontend - Hook de Gestión

**Archivo**: `SAAC-Frontend/src/Hooks/UseUsers.ts`

```typescript
export const useUsers = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Cargar usuarios
  const loadUsers = useCallback(async () => {
    // ...transformación de BackendUser a User
  }, []);

  // Activar con actualización optimista
  const activarUsuario = useCallback(async (userId: number) => {
    setUsers(prevUsers =>
      prevUsers.map(user =>
        user.id === userId ? { ...user, status: 'active' as const } : user
      )
    );
    
    try {
      await userService.activateUser(userId);
    } catch (err) {
      // Revertir en caso de error
      setUsers(prevUsers =>
        prevUsers.map(user =>
          user.id === userId ? { ...user, status: 'inactive' as const } : user
        )
      );
      throw err;
    }
  }, []);

  // Desactivar con actualización optimista
  const desactivarUsuario = useCallback(async (userId: number) => {
    // Lógica similar
  }, []);

  return { users, isLoading, error, loadUsers, activarUsuario, desactivarUsuario };
};
```

- ✅ **Actualización optimista**: UI se actualiza antes de confirmar con backend
- ✅ **Reversión automática**: Si falla el backend, vuelve al estado anterior
- ✅ Manejo de errores centralizado

---

## ⚠️ Funcionalidades FALTANTES o PARCIALES

### 1. ❌ Frontend: UI para Asignar Permisos Específicos por Módulo

**Estado**: Backend implementado ✅ | Frontend NO implementado ❌

**Lo que existe**:
- Backend tiene endpoint: `PUT /api/admin/users/{user}/permissions`
- Backend valida estructura por módulos (evidencias, reportes, usuarios, ciclos)
- Backend aplica regla de mínimos (si create/edit/delete → agrega view)

**Lo que falta**:
1. **Interfaz de Usuario**:
   - Componente para seleccionar permisos granulares
   - UI tipo checklist agrupada por módulos:
     ```
     □ Módulo Evidencias
       □ Ver evidencias
       □ Crear evidencias
       □ Editar evidencias
       □ Eliminar evidencias
     
     □ Módulo Reportes
       □ Generar reportes
     
     □ Módulo Usuarios
       □ Ver usuarios
       □ Crear usuarios
       □ Editar usuarios
       □ Eliminar usuarios
     
     □ Módulo Ciclos
       □ Ver ciclos
       □ Crear ciclos
       □ Editar ciclos
       □ Eliminar ciclos
     ```

2. **Método en UserService**:
   ```typescript
   // FALTA IMPLEMENTAR
   async assignUserPermissions(
     userId: number, 
     modules: Record<string, string[]>
   ): Promise<ApiResponse> {
     const response = await fetch(`${this.baseURL}/${userId}/permissions`, {
       method: 'PUT',
       headers: { 
         'Content-Type': 'application/json',
         'Accept': 'application/json' 
       },
       body: JSON.stringify({ modules }),
     });
     return await response.json();
   }
   ```

3. **Página/Modal de Asignación de Permisos**:
   - Ruta: `/usuarios/:id/permisos` o modal en EditUser
   - Componente similar a `EditUserForm` pero enfocado en permisos

4. **Validación Frontend**:
   - Validar que si selecciona create/edit/delete, view esté marcado
   - Mostrar advertencias si no cumple regla de mínimos

**Payload esperado por el backend**:
```json
{
  "modules": {
    "evidencias": ["view", "create", "edit"],
    "reportes": ["generate"],
    "usuarios": ["view"],
    "ciclos": ["view", "create", "edit", "delete"]
  }
}
```

### 2. ⚠️ Autorización (Middleware/Gates)

**Estado**: Comentado para Sprint 3

**Código existente pero comentado** (UserController.php línea 130):
```php
// DEV: simula usuario que realiza la acción
// $acting = User::where('email', 'admin@saacuna.local')->first();
// Gate::forUser($acting)->authorize('usuarios.edit');
// TODO Sprint 3: quitar forUser y usar usuario autenticado (LDAP)
```

**Lo que falta**:
1. ✅ Gates ya definidos (probablemente en `AuthServiceProvider`)
2. ❌ **Descomentar autorización en Sprint 3**
3. ❌ **Usar usuario autenticado real** (actualmente simula)
4. ❌ **Aplicar middleware a rutas**:
   ```php
   Route::prefix('admin/users')
     ->middleware(['role:Superusuario|Administrador']) // FALTA
     ->group(function () { ... });
   ```

### 3. ⚠️ Validación de Permisos Mínimos

**Estado**: Backend implementado ✅ | UI no refleja ❌

El backend **automáticamente agrega `view`** si detecta create/edit/delete, pero:
- ❌ Frontend no muestra advertencia al usuario
- ❌ Frontend no deshabilita checkboxes dependientes
- ❌ No hay feedback visual de la regla

**Solución sugerida**:
```typescript
// Validación en el componente de permisos
const validateMinimumPermissions = (modules: Record<string, string[]>) => {
  const warnings: string[] = [];
  
  for (const [module, actions] of Object.entries(modules)) {
    const hasModifyActions = actions.some(a => 
      ['create', 'edit', 'delete'].includes(a)
    );
    
    if (hasModifyActions && !actions.includes('view')) {
      warnings.push(
        `El módulo ${module} requiere el permiso "Ver" para usar crear/editar/eliminar`
      );
    }
  }
  
  return warnings;
};
```

### 4. ⚠️ UI: Modal vs Página para Editar Usuario

**Estado actual**:
- Editar rol: Se hace en **página separada** (`/usuarios/editar/:id`)
- Ver detalles: Se hace en **modal**

**Inconsistencia detectada**:
- ¿Asignar permisos será modal o página?
- Recomendación: **Página separada** (más espacio para checklist de módulos)

### 5. ❌ Testing de Integración Backend-Frontend

**Lo que falta**:
1. Tests E2E para flujo completo:
   - Usuario abre lista → edita → asigna rol → confirma → verifica en detalles
2. Tests de edge cases:
   - Asignar rol que ya tiene (debe dar 409)
   - Activar usuario ya activo (debe dar 409)
   - Enviar módulos no permitidos (backend los ignora, ¿frontend valida?)

---

## 📊 Tabla Resumen de Completitud

| Funcionalidad | Backend | Frontend | Integrado | Bitácora | Tests |
|---------------|---------|----------|-----------|----------|-------|
| **Listar usuarios** | ✅ | ✅ | ✅ | N/A | ⚠️ |
| **Ver detalles usuario** | ✅ | ✅ | ✅ | N/A | ⚠️ |
| **Activar usuario** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Desactivar usuario** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Asignar rol** | ✅ | ✅ | ✅ | ✅ | ⚠️ |
| **Asignar permisos por módulo** | ✅ | ❌ | ❌ | ✅ | ❌ |
| **Validar usuario existe** | ✅ | ✅ | ✅ | N/A | ✅ |
| **Validar acción válida** | ✅ | ✅ | ✅ | N/A | ⚠️ |
| **Autorización (Gates)** | ⚠️ | N/A | ⚠️ | N/A | ❌ |
| **Validación permisos mínimos** | ✅ | ❌ | ⚠️ | N/A | ❌ |

**Leyenda**:
- ✅ Completado
- ⚠️ Parcial / Comentado
- ❌ No implementado
- N/A: No aplica

---

## 🎯 Priorización de Trabajo Pendiente

### Alta Prioridad (Bloquea HU-002)

1. **Frontend: UI para asignar permisos por módulo** 🔴
   - Componente de checklist agrupado
   - Método en UserService
   - Integración con backend existente
   - **Estimación**: 8-12 horas

2. **Frontend: Validación de regla de mínimos** 🟠
   - Mostrar advertencias si falta `view`
   - Auto-marcar `view` si selecciona create/edit/delete
   - **Estimación**: 2-3 horas

### Media Prioridad (Mejora de calidad)

3. **Tests de integración** 🟡
   - Tests E2E para flujo de asignación de permisos
   - Tests de casos edge (409, validaciones)
   - **Estimación**: 4-6 horas

4. **UX: Decidir modal vs página** 🟡
   - Analizar con equipo UX
   - Implementar diseño consistente
   - **Estimación**: 1-2 horas (decisión) + implementación

### Baja Prioridad (Sprint 3)

5. **Descomentar autorización** 🟢
   - Activar Gates en controladores
   - Aplicar middleware a rutas
   - Usar usuario autenticado real
   - **Estimación**: 2-3 horas

---

## 🔄 Flujo Actual vs Esperado

### ✅ Flujo IMPLEMENTADO (Asignar Rol)

```
1. Usuario abre lista de usuarios
   ↓
2. Click en botón "Editar" (lápiz)
   ↓
3. Navega a /usuarios/editar/:id
   ↓
4. Frontend carga usuario y roles disponibles
   ↓
5. Usuario selecciona nuevo rol en CustomSelect
   ↓
6. Vista previa muestra permisos del rol
   ↓
7. Click en "Guardar cambios"
   ↓
8. Modal de confirmación: "¿Asignar rol X a usuario Y?"
   ↓
9. Click en "Confirmar"
   ↓
10. Frontend: PUT /api/admin/users/:id/role { role: "Admin" }
   ↓
11. Backend: Valida, asigna rol, registra bitácora
   ↓
12. Frontend: Modal de éxito "Rol asignado correctamente"
   ↓
13. Usuario cierra modal → redirige a /usuarios/listar
```

### ❌ Flujo FALTANTE (Asignar Permisos por Módulo)

```
1. Usuario abre lista de usuarios
   ↓
2. Click en botón "Permisos" (nuevo botón) ← FALTA
   ↓
3. Navega a /usuarios/permisos/:id ← FALTA
   ↓
4. Frontend carga permisos actuales del usuario ← FALTA
   ↓
5. UI muestra checklist agrupada por módulos ← FALTA
   □ Evidencias
     ☑ Ver
     □ Crear
     ☑ Editar
   □ Reportes
     □ Generar
   ...
   ↓
6. Usuario marca/desmarca checkboxes ← FALTA
   ↓
7. Sistema valida regla de mínimos ← FALTA
   ⚠️ Si marca "Crear" sin "Ver" → Advertencia
   ↓
8. Click en "Guardar cambios"
   ↓
9. Modal de confirmación ← FALTA
   ↓
10. Frontend: PUT /api/admin/users/:id/permissions { modules: {...} } ← FALTA
   ↓
11. Backend: Valida, asigna permisos, registra bitácora ✅ (YA EXISTE)
   ↓
12. Frontend: Modal de éxito ← FALTA
```

---

## 📁 Archivos Clave del HU-002

### Backend (SAAC-Backend)

| Archivo | Estado | Líneas Clave | Notas |
|---------|--------|--------------|-------|
| `app/Http/Controllers/UserController.php` | ✅ | 36-182 | Todos los endpoints implementados |
| `app/Services/UserAdminService.php` | ✅ | 9-96 | Lógica de negocio completa |
| `app/Http/Requests/AssignRoleRequest.php` | ✅ | - | Validación de rol |
| `app/Http/Requests/AssignPermissionsRequest.php` | ✅ | - | Validación de permisos por módulo |
| `routes/api.php` | ✅ | 136-149 | 5 rutas de gestión de usuarios |
| `app/Services/AuditLogService.php` | ✅ | - | Usado en todas las operaciones |

### Frontend (SAAC-Frontend)

| Archivo | Estado | Líneas Clave | Notas |
|---------|--------|--------------|-------|
| `src/Services/UserService.ts` | ⚠️ | 72-157 | Falta método assignUserPermissions |
| `src/Hooks/UseUsers.ts` | ✅ | 28-118 | Hook con actualización optimista |
| `src/Pages/Users/UsersList.tsx` | ✅ | - | Lista con botones de acción |
| `src/Pages/Users/EditUser.tsx` | ✅ | - | Página de edición de rol |
| `src/Pages/Users/Components/EditUserForm.tsx` | ✅ | - | Formulario funcional |
| `src/Components/Features/Users/UserDetailsModal.tsx` | ✅ | - | Modal de detalles |
| **FALTA**: Componente de permisos por módulo | ❌ | - | **Prioridad ALTA** |

---

## 💡 Recomendaciones

### 1. Crear Componente de Permisos (Prioridad ALTA)

**Propuesta de estructura**:

```typescript
// src/Pages/Users/AssignPermissions.tsx
interface ModulePermissions {
  evidencias: string[];
  reportes: string[];
  usuarios: string[];
  ciclos: string[];
}

interface AssignPermissionsProps {
  userId: number;
  currentPermissions: ModulePermissions;
  onSave: (modules: ModulePermissions) => Promise<void>;
  onCancel: () => void;
}

export const AssignPermissionsForm: React.FC<AssignPermissionsProps> = ({...}) => {
  const [selectedModules, setSelectedModules] = useState<ModulePermissions>({});
  const [warnings, setWarnings] = useState<string[]>([]);

  // Validar regla de mínimos
  const validateMinimums = () => { ... };

  // Renderizar checklist por módulo
  const renderModuleSection = (module: string, actions: string[]) => { ... };

  return (
    <form>
      {renderModuleSection('evidencias', ['view', 'create', 'edit', 'delete'])}
      {renderModuleSection('reportes', ['generate'])}
      {renderModuleSection('usuarios', ['view', 'create', 'edit', 'delete'])}
      {renderModuleSection('ciclos', ['view', 'create', 'edit', 'delete'])}
      
      {warnings.length > 0 && <WarningBanner warnings={warnings} />}
      
      <Button onClick={handleSave}>Guardar Permisos</Button>
    </form>
  );
};
```

### 2. Agregar Ruta y Botón en UI

**En `UsersList.tsx`**:
```tsx
<Button 
  onClick={() => navigate(`/usuarios/permisos/${user.id}`)}
  icon={<PermissionsIcon />}
  variant="secondary"
>
  Permisos
</Button>
```

**En `routes/index.ts`**:
```typescript
{
  path: '/usuarios/permisos/:id',
  element: <AssignPermissionsPage />
}
```

### 3. Completar UserService

```typescript
// src/Services/UserService.ts
async assignUserPermissions(
  userId: number, 
  modules: Record<string, string[]>
): Promise<ApiResponse> {
  const response = await fetch(`${this.baseURL}/${userId}/permissions`, {
    method: 'PUT',
    headers: { 
      'Content-Type': 'application/json',
      'Accept': 'application/json' 
    },
    body: JSON.stringify({ modules }),
  });
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  return await response.json();
}
```

### 4. Testing Prioritario

**Tests críticos a agregar**:
1. ✅ Backend: Validar regla de mínimos (si create sin view → agrega view)
2. ✅ Backend: Validar módulos desconocidos son ignorados
3. ❌ Frontend: Validación de formulario de permisos
4. ❌ E2E: Flujo completo de asignación de permisos

---

## 📅 Estimación de Trabajo Restante

| Tarea | Estimación | Prioridad | Sprint Sugerido |
|-------|------------|-----------|-----------------|
| UI asignación de permisos | 8-12h | 🔴 Alta | Sprint 2 |
| Validación regla mínimos (FE) | 2-3h | 🟠 Media | Sprint 2 |
| Tests integración | 4-6h | 🟡 Media | Sprint 2/3 |
| Decisión modal vs página | 1-2h | 🟡 Media | Sprint 2 |
| Descomentar autorización | 2-3h | 🟢 Baja | Sprint 3 |
| **TOTAL** | **17-26h** | | |

---

## ✅ Checklist para Considerar HU-002 Completo

### Funcionalidad Core
- [x] ✅ Backend: Listar usuarios
- [x] ✅ Backend: Activar usuario
- [x] ✅ Backend: Desactivar usuario
- [x] ✅ Backend: Asignar rol
- [x] ✅ Backend: Asignar permisos por módulo
- [x] ✅ Frontend: Listar usuarios
- [x] ✅ Frontend: Ver detalles
- [x] ✅ Frontend: Activar/Desactivar
- [x] ✅ Frontend: Editar rol
- [ ] ❌ **Frontend: Asignar permisos por módulo (UI)**
- [ ] ❌ **Frontend: Método en UserService para permisos**

### Validaciones y Reglas de Negocio
- [x] ✅ Validar usuario existe (backend route binding)
- [x] ✅ Validar acción válida (FormRequests)
- [x] ✅ Prevenir duplicados (rol ya asignado → 409)
- [x] ✅ Regla de mínimos (create/edit/delete → auto-add view)
- [ ] ⚠️ **Validación frontend de regla de mínimos**

### Bitácora
- [x] ✅ Registrar activación
- [x] ✅ Registrar desactivación
- [x] ✅ Registrar asignación de rol
- [x] ✅ Registrar asignación de permisos (con detalle)

### Seguridad (Sprint 3)
- [ ] ⚠️ Descomentar Gates/Middleware
- [ ] ⚠️ Usar usuario autenticado real (no simulado)

### Testing
- [x] ✅ Tests hook UseUsers (activar/desactivar)
- [ ] ⚠️ Tests asignación de rol
- [ ] ❌ Tests asignación de permisos
- [ ] ❌ Tests E2E flujo completo

---

## 🎯 Conclusión

### Estado General: **75% Completado**

**Fortalezas**:
- ✅ Backend robusto y completo
- ✅ Bitácora exhaustiva
- ✅ Arquitectura modular y escalable
- ✅ Validaciones de negocio implementadas
- ✅ UI para activar/desactivar y asignar roles

**Bloqueos**:
- ❌ **UI de asignación de permisos por módulo** (crítico)
- ⚠️ Falta validación frontend de regla de mínimos
- ⚠️ Autorización comentada (Sprint 3)

**Próximos Pasos**:
1. 🔴 **Crear componente AssignPermissionsForm** (8-12h)
2. 🔴 **Agregar método assignUserPermissions en UserService** (1h)
3. 🟠 **Validación de permisos mínimos en UI** (2-3h)
4. 🟡 **Tests de integración** (4-6h)

**¿Listo para producción?**: ❌ No hasta completar UI de permisos

---

**Preparado por**: Sistema SAAC - Equipo de Desarrollo  
**Revisión recomendada**: Antes de crear rama para trabajo pendiente  
**Versión**: 1.0
