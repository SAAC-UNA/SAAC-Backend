# ✅ Checklist HU-002: Lo que FALTA Implementar (Post-LDAP)

**Historia**: HU-002 - Gestión de Usuarios del Sistema  
**Estado**: 80% Completo | 20% Pendiente  
**Fecha**: 9 Enero 2026

---

## ✅ CONFIRMACIÓN: Lo que YA ESTÁ IMPLEMENTADO

### Backend - 100% Completo ✅
- ✅ Endpoint `GET /api/admin/users` - Listar usuarios
- ✅ Endpoint `PATCH /api/admin/users/{user}/activate` - Activar usuario
- ✅ Endpoint `PATCH /api/admin/users/{user}/deactivate` - Desactivar usuario
- ✅ Endpoint `PUT /api/admin/users/{user}/role` - Asignar rol
- ✅ Endpoint `PUT /api/admin/users/{user}/permissions` - Asignar permisos por módulo
- ✅ **Bitácora implementada en TODAS las acciones** (líneas 104-111, 122-129, 148-155, 170-177 de UserController.php)
- ✅ Validación con AssignRoleRequest y AssignPermissionsRequest
- ✅ Business logic en UserAdminService con regla de mínimos
- ✅ Registro detallado: "Rol '{$roleName}' asignado a: {$user->nombre}" y "Permisos actualizados para: {$user->nombre}. Permisos: evidencias.view, evidencias.create..."

### Frontend - Parcialmente Completo (80%)
- ✅ Página `UsersList.tsx` - Lista de usuarios con acciones
- ✅ Componente `UserDetailsModal.tsx` - Ver detalles (ojo)
- ✅ Página `EditUser.tsx` - Editar usuario (lápiz)
- ✅ Componente `EditUserForm.tsx` - Formulario de edición de rol con preview de permisos
- ✅ Hook `UseUsers.ts` - Optimistic updates con rollback
- ✅ Servicio `UserService.ts` - métodos: listUsers, activateUser, deactivateUser, assignUserRole
- ✅ Componente `PermissionsRoleModal.tsx` - Modal para VER permisos de un rol (solo lectura)
- ✅ Activar/Desactivar con confirmación y success modal
- ✅ Asignar rol con preview de permisos que tendrá

---

## 🔴 CRÍTICO - Bloquea completitud del HU

### 1. UI para Asignar Permisos Específicos por Módulo

**Estado**: Backend ✅ | Frontend ❌

**Backend ya tiene**:
- ✅ Endpoint: `PUT /api/admin/users/{user}/permissions`
- ✅ Lógica en `UserAdminService::setModulePermissions()`
- ✅ Validación con `AssignPermissionsRequest`
- ✅ Registro en bitácora

**Lo que FALTA en Frontend**:

#### [ ] 1.1. Crear componente `AssignPermissionsForm.tsx`

**Ubicación**: `SAAC-Frontend/src/Pages/Users/Components/AssignPermissionsForm.tsx`

**Estructura del componente**:
```typescript
interface ModulePermissions {
  evidencias: string[];    // ['view', 'create', 'edit', 'delete']
  reportes: string[];      // ['generate']
  usuarios: string[];      // ['view', 'create', 'edit', 'delete']
  ciclos: string[];        // ['view', 'create', 'edit', 'delete']
}

interface AssignPermissionsFormProps {
  user: User;
  currentPermissions: string[];  // Permisos directos actuales
  onSubmit: (modules: ModulePermissions) => void;
  onCancel: () => void;
}
```

**Elementos UI necesarios**:
- [ ] Card de información del usuario (nombre, email, cédula) - solo lectura
- [ ] Sección "Módulo Evidencias" con 4 checkboxes (ver, crear, editar, eliminar)
- [ ] Sección "Módulo Reportes" con 1 checkbox (generar)
- [ ] Sección "Módulo Usuarios" con 4 checkboxes (ver, crear, editar, eliminar)
- [ ] Sección "Módulo Ciclos" con 4 checkboxes (ver, crear, editar, eliminar)
- [ ] Alert de advertencia si regla de mínimos no se cumple
- [ ] Botones: "Guardar Cambios" y "Cancelar"

**Validaciones necesarias**:
- [ ] Si selecciona `create`, `edit` o `delete` → auto-marcar `view` (o mostrar warning)
- [ ] Deshabilitar guardado si no se cumple regla de mínimos
- [ ] Resaltar visualmente checkboxes que dependen de otros

**Ejemplo visual esperado**:
```
┌─────────────────────────────────────────────────┐
│ Información del Usuario                         │
│ Nombre: Pablo Castillo Quesada                  │
│ Email: pablo.castillo.quesada@una.cr            │
│ Cédula: 203849675                               │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ 📄 Módulo: Evidencias                          │
│ ☑ Ver evidencias                                │
│ ☑ Crear evidencias                              │
│ ☑ Editar evidencias                             │
│ □ Eliminar evidencias                           │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ 📊 Módulo: Reportes                            │
│ ☑ Generar reportes                              │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ 👥 Módulo: Usuarios                            │
│ ☑ Ver usuarios                                  │
│ □ Crear usuarios                                │
│ □ Editar usuarios                               │
│ □ Eliminar usuarios                             │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ 🔄 Módulo: Ciclos                              │
│ ☑ Ver ciclos                                    │
│ ☑ Crear ciclos                                  │
│ ☑ Editar ciclos                                 │
│ ☑ Eliminar ciclos                               │
└─────────────────────────────────────────────────┘

⚠️ Advertencia: El permiso "Crear evidencias" 
   requiere "Ver evidencias"

[Guardar Cambios]  [Cancelar]
```

#### [ ] 1.2. Crear página `AssignPermissions.tsx`

**Ubicación**: `SAAC-Frontend/src/Pages/Users/AssignPermissions.tsx`

**Responsabilidades**:
- [ ] Cargar datos del usuario desde la URL (`:id`)
- [ ] Obtener permisos directos actuales
- [ ] Renderizar `AssignPermissionsForm`
- [ ] Manejar envío del formulario
- [ ] Mostrar modales de confirmación y éxito
- [ ] Redirigir a `/usuarios/listar` después del éxito

**Estructura**:
```typescript
export const AssignPermissionsPage: React.FC = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [confirmModalState, setConfirmModalState] = useState({...});
  const [successModalState, setSuccessModalState] = useState({...});

  // Cargar usuario
  useEffect(() => {
    loadUserData(parseInt(id!));
  }, [id]);

  // Manejar confirmación
  const handleFormSubmit = (modules: ModulePermissions) => {
    setConfirmModalState({ isOpen: true, modules });
  };

  // Confirmar y enviar al backend
  const confirmAssignPermissions = async () => {
    await userService.assignUserPermissions(userId, modules);
    setSuccessModalState({ isOpen: true });
  };

  return (
    <ScreenContainer title="Asignar Permisos">
      {isLoading ? (
        <LoadingSpinner />
      ) : user ? (
        <AssignPermissionsForm
          user={user}
          currentPermissions={user.directPermissions}
          onSubmit={handleFormSubmit}
          onCancel={() => navigate('/usuarios/listar')}
        />
      ) : (
        <ErrorMessage message="Usuario no encontrado" />
      )}
      
      <ConfirmModal {...} />
      <SuccessModal {...} />
    </ScreenContainer>
  );
};
```

#### [ ] 1.3. Agregar método `assignUserPermissions` en UserService

**Ubicación**: `SAAC-Frontend/src/Services/UserService.ts`

**Código a agregar después de `assignUserRole`** (línea ~158):

```typescript
/**
 * Asignar permisos específicos por módulo a un usuario
 */
async assignUserPermissions(
  userId: number, 
  modules: Record<string, string[]>
): Promise<ApiResponse> {
  try {
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
  } catch (error) {
    console.error('Error asignando permisos:', error);
    throw error;
  }
}
```

**Payload de ejemplo**:
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

#### [ ] 1.4. Agregar ruta en el router

**Ubicación**: `SAAC-Frontend/src/Navigation.ts` o archivo de rutas

**Código a agregar**:
```typescript
{
  path: '/usuarios/permisos/:id',
  element: <AssignPermissionsPage />,
  // Opcional: guard para Superusuario
}
```

#### [ ] 1.5. Agregar botón "Permisos" en UsersList

**Ubicación**: `SAAC-Frontend/src/Pages/Users/UsersList.tsx`

**Agregar en la tabla UsersTable** (junto a botones existentes):

```typescript
<Button 
  onClick={() => navigate(`/usuarios/permisos/${user.id}`)}
  icon={<SystemIcons.ShieldIcon />}
  variant="secondary"
  size="small"
  title="Asignar permisos específicos"
>
  Permisos
</Button>
```

O agregar al menú dropdown si ya existe.

---

## 🟠 IMPORTANTE - Mejora UX y validación

### 2. Validación Frontend de Regla de Mínimos

**Estado**: Backend implementa ✅ | Frontend no valida ❌

**Problema actual**:
- Backend automáticamente agrega `view` si falta
- Usuario NO ve advertencia
- Puede ser confuso ver un permiso que no seleccionó

**Lo que FALTA**:

#### [ ] 2.1. Función de validación en el componente

```typescript
// En AssignPermissionsForm.tsx

const validateMinimumPermissions = (
  modules: ModulePermissions
): string[] => {
  const warnings: string[] = [];
  
  for (const [module, actions] of Object.entries(modules)) {
    const hasModifyActions = actions.some(a => 
      ['create', 'edit', 'delete'].includes(a)
    );
    
    if (hasModifyActions && !actions.includes('view')) {
      const moduleLabel = MODULE_LABELS[module] || module;
      warnings.push(
        `El módulo "${moduleLabel}" requiere el permiso "Ver" para usar crear/editar/eliminar`
      );
    }
  }
  
  return warnings;
};
```

#### [ ] 2.2. Auto-marcar `view` cuando se seleccione `create`, `edit` o `delete`

```typescript
const handleCheckboxChange = (module: string, action: string, checked: boolean) => {
  const newModules = { ...selectedModules };
  
  if (checked) {
    // Agregar acción
    newModules[module] = [...(newModules[module] || []), action];
    
    // Si es create/edit/delete, auto-agregar view
    if (['create', 'edit', 'delete'].includes(action)) {
      if (!newModules[module].includes('view')) {
        newModules[module] = ['view', ...newModules[module]];
        // Mostrar toast: "Se agregó automáticamente el permiso 'Ver'"
      }
    }
  } else {
    // Remover acción
    newModules[module] = newModules[module].filter(a => a !== action);
  }
  
  setSelectedModules(newModules);
};
```

#### [ ] 2.3. Mostrar advertencias visuales

```typescript
// En el JSX del componente

{warnings.length > 0 && (
  <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
    <div className="flex items-start">
      <SystemIcons.WarningIcon className="text-yellow-600 mt-0.5" />
      <div className="ml-3">
        <h3 className="text-sm font-medium text-yellow-800">
          Advertencias de Permisos
        </h3>
        <ul className="mt-2 text-sm text-yellow-700 list-disc list-inside">
          {warnings.map((warning, idx) => (
            <li key={idx}>{warning}</li>
          ))}
        </ul>
      </div>
    </div>
  </div>
)}
```

---

## 🟡 DESEABLE - Testing

### 3. Tests de Integración

**Lo que FALTA**:

#### [ ] 3.1. Test E2E del flujo completo

**Ubicación**: `SAAC-Frontend/src/Pages/Users/__tests__/AssignPermissions.e2e.test.tsx`

**Casos a probar**:
```typescript
describe('Asignar Permisos - Flujo E2E', () => {
  it('debe asignar permisos correctamente', async () => {
    // 1. Navegar a lista de usuarios
    // 2. Click en botón "Permisos" de un usuario
    // 3. Verificar que se carga el formulario
    // 4. Marcar checkboxes de permisos
    // 5. Click en "Guardar Cambios"
    // 6. Verificar modal de confirmación
    // 7. Confirmar
    // 8. Verificar llamada al backend
    // 9. Verificar modal de éxito
    // 10. Verificar redirección a lista
  });

  it('debe aplicar regla de mínimos automáticamente', async () => {
    // 1. Marcar "Crear evidencias"
    // 2. Verificar que "Ver evidencias" se marca automáticamente
    // 3. Mostrar toast de notificación
  });

  it('debe mostrar advertencia si regla no se cumple', async () => {
    // 1. Desmarcar "Ver evidencias"
    // 2. Marcar "Editar evidencias"
    // 3. Verificar que aparece advertencia
  });
});
```

#### [ ] 3.2. Test unitario de validación de permisos

```typescript
describe('validateMinimumPermissions', () => {
  it('debe detectar falta de view con create', () => {
    const modules = {
      evidencias: ['create', 'edit']  // Falta 'view'
    };
    
    const warnings = validateMinimumPermissions(modules);
    expect(warnings).toHaveLength(1);
    expect(warnings[0]).toContain('requiere el permiso "Ver"');
  });

  it('no debe advertir si view está presente', () => {
    const modules = {
      evidencias: ['view', 'create', 'edit']
    };
    
    const warnings = validateMinimumPermissions(modules);
    expect(warnings).toHaveLength(0);
  });
});
```

#### [ ] 3.3. Test del método en UserService

```typescript
describe('UserService.assignUserPermissions', () => {
  it('debe enviar payload correcto al backend', async () => {
    const modules = {
      evidencias: ['view', 'create'],
      reportes: ['generate']
    };
    
    await userService.assignUserPermissions(1, modules);
    
    expect(fetch).toHaveBeenCalledWith(
      'http://127.0.0.1:8000/api/admin/users/1/permissions',
      expect.objectContaining({
        method: 'PUT',
        body: JSON.stringify({ modules })
      })
    );
  });
});
```

---

## 🟢 FUTURO - Sprint 3

### 4. Autorización (Gates/Middleware)

**Estado**: Código existe pero comentado ⚠️

**Lo que FALTA**:

#### [ ] 4.1. Descomentar autorización en UserController

**Ubicación**: `SAAC-Backend/app/Http/Controllers/UserController.php` (línea 130)

**Cambiar de**:
```php
// DEV: simula usuario que realiza la acción
// $acting = User::where('email', 'admin@saacuna.local')->first();
// Gate::forUser($acting)->authorize('usuarios.edit');
// TODO Sprint 3: quitar forUser y usar usuario autenticado (LDAP)
```

**A**:
```php
// Autorizar que el usuario autenticado tenga permiso usuarios.edit
Gate::authorize('usuarios.edit');
```

#### [ ] 4.2. Aplicar middleware a rutas

**Ubicación**: `SAAC-Backend/routes/api.php` (línea 136)

**Cambiar de**:
```php
Route::prefix('admin/users')->group(function () {
    // ...
});
```

**A**:
```php
Route::prefix('admin/users')
    ->middleware(['role:Superusuario|Administrador'])
    ->group(function () {
        // ...
    });
```

#### [ ] 4.3. Usar usuario autenticado real (LDAP)

**Actualmente**: Simula usuario con `User::where('email', '...')`  
**Necesario**: `$request->user()` (usuario autenticado vía Sanctum + LDAP)

---

## 🎯 Resumen de Tareas Pendientes

### Por Prioridad

| # | Tarea | Archivo | Estimación | Prioridad |
|---|-------|---------|------------|-----------|
| 1 | Crear `AssignPermissionsForm.tsx` | `src/Pages/Users/Components/` | 6-8h | 🔴 Crítica |
| 2 | Crear `AssignPermissionsPage.tsx` | `src/Pages/Users/` | 2-3h | 🔴 Crítica |
| 3 | Agregar método en `UserService.ts` | `src/Services/UserService.ts` | 30min | 🔴 Crítica |
| 4 | Agregar ruta en router | `src/Navigation.ts` | 15min | 🔴 Crítica |
| 5 | Agregar botón en `UsersList.tsx` | `src/Pages/Users/UsersList.tsx` | 30min | 🔴 Crítica |
| 6 | Validación regla de mínimos | `AssignPermissionsForm.tsx` | 2-3h | 🟠 Alta |
| 7 | Tests E2E | `__tests__/` | 3-4h | 🟡 Media |
| 8 | Tests unitarios | `__tests__/` | 1-2h | 🟡 Media |
| 9 | Descomentar autorización | `UserController.php` | 1h | 🟢 Baja (Sprint 3) |
| 10 | Middleware en rutas | `routes/api.php` | 30min | 🟢 Baja (Sprint 3) |

**Total estimado crítico (1-5)**: 10-13 horas  
**Total con validación y tests (1-8)**: 17-24 horas  
**Total incluyendo Sprint 3 (1-10)**: 18.5-26.5 horas

---

## ✅ Checklist Final para HU-002 Completo
 (UserController@index)
- [x] Backend: Activar usuario (UserController@activate con bitácora)
- [x] Backend: Desactivar usuario (UserController@deactivate con bitácora)
- [x] Backend: Asignar rol (UserController@assignRole con bitácora)
- [x] Backend: Asignar permisos por módulo (UserController@assignPermissions con bitácora detallada)
- [x] Frontend: Listar usuarios (UsersList.tsx + UseUsers hook)
- [x] Frontend: Ver detalles (UserDetailsModal con permisos directos y de rol)
- [x] Frontend: Activar/Desactivar (Con confirmación, optimistic updates y rollback)
- [x] Frontend: Editar rol (EditUser.tsx + EditUserForm con preview de permisos)
- [ ] **Frontend: UI asignar permisos por módulo** ← **BLOQUEANTE** (falta AssignPermissionsForm + página)
- [ ] **Frontend: Método assignUserPermissions** ← **BLOQUEANTE** (falta en UserService.ts línea ~165)
- [ ] **Frontend: UI asignar permisos por módulo** ← **BLOQUEANTE**
- [ ] **Frontend: Método assignUserPermissions** ← **BLOQUEANTE**

### Validaciones
- [x] Backend: Validar usuario existe
- [x] Backend: Validar acción válida
- [x] Backend: Prevenir duplicados (409)
- [x] Backend: Regla de mínimos (auto-add view)
- [ ] **Frontend: Validar regla de mínimos** ← **IMPORTANTE**

### Bitácora - ✅ 100% Implementado
- [x] Registrar activación (línea 104-111 UserController.php): "Usuario activado: {nombre} (ID: {id})"
- [x] Registrar desactivación (línea 122-129): "Usuario desactivado: {nombre} (ID: {id})"  
- [x] Registrar asignación rol (línea 148-155): "Rol '{$roleName}' asignado a: {nombre} (ID: {id})"
- [x] Registrar asignación permisos (línea 170-177): "Permisos actualizados para: {nombre} (ID: {id}). Permisos: evidencias.view, evidencias.create, ..."
- [x] Todas las acciones usan AuditLogService::log() con acción, descripción y módulo 'Usuarios'

### Testing
- [x] Tests UseUsers hook
- [ ] Tests asignación permisos (E2E)
- [ ] Tests validación frontend

### Seguridad (Sprint 3)
- [ ] Descomentar Gates
- [ ] Aplicar middleware
- [ ] Usar usuario LDAP autenticado

---

## 📋 Orden de Implementación Sugerido

1. **Día 1** (8h):
   - [ ] Crear `AssignPermissionsForm.tsx` (componente básico sin validaciones)
   - [ ] Agregar método `assignUserPermissions` en `UserService.ts`
   - [ ] Crear `AssignPermissionsPage.tsx` (página container)

2. **Día 2** (6h):
   - [ ] Agregar ruta en router
   - [ ] Agregar botón "Permisos" en `UsersList.tsx`
   - [ ] Implementar validación de regla de mínimos en el formulario
   - [ ] Probar flujo completo manualmente

3. **Día 3** (4h):
   - [ ] Tests E2E del flujo
   - [ ] Tests unitarios de validación
   - [ ] Ajustes de UX/UI según feedback

4. **Sprint 3** (2h):
   - [ ] Descomentar autorización
   - [ ] Aplicar middleware
   - [ ] Ajustar para usar usuario LDAP autenticado

---

## 🚀 Criterio de Aceptación

El HU-002 se considera **COMPLETO** cuando:

1. ✅ Usuario puede ver lista de usuarios
2. ✅ Usuario puede activar/desactivar usuarios
3. ✅ Usuario puede asignar roles
4. ⚠️ **Usuario puede asignar permisos ESPECÍFICOS por módulo** ← **FALTA**
5. ⚠️ Sistema valida regla de mínimos (view requerido) ← **PARCIAL**
6. ✅ Todas las acciones se registran en bitácora
7. ⚠️ Tests cubren flujos críticos ← **PARCIAL**
8. ⚠️ Solo Superusuarios/Admins pueden gestionar usuarios ← **Sprint 3**

**Estado actual**: 6/8 criterios cumplidos (75% - backend 100%, frontend 80%)

---

**Documento creado**: 9 Enero 2026  
**Próxima revisión**: Después de implementar UI de permisos  
**Responsable**: Equipo de Desarrollo SAAC
