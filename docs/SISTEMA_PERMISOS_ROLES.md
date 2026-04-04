# Sistema de Permisos y Roles - SAAC UNA

## 📋 Índice

1. [Visión General](#visión-general)
2. [Roles del Sistema](#roles-del-sistema)
3. [Permisos por Módulo](#permisos-por-módulo)
4. [Configuración](#configuración)
5. [Uso en Backend](#uso-en-backend)
6. [Uso en Frontend](#uso-en-frontend)
7. [Ejemplos Prácticos](#ejemplos-prácticos)

---

## 🎯 Visión General

El sistema de permisos de SAAC-UNA está basado en **roles y permisos granulares** utilizando el paquete **Spatie Laravel Permission**.

### Características Principales

✅ **Permisos hardcodeados** por módulo (definidos en `config/permissions.php`)  
✅ **Roles dinámicos** - El Superusuario puede crear y editar roles personalizados  
✅ **Roles protegidos** - 4 roles del sistema que no se pueden eliminar ni modificar  
✅ **Validación en backend** mediante Policies y Middleware  
✅ **API para frontend** para mostrar/ocultar elementos según permisos  
✅ **Protección de rutas** granular en todas las operaciones CRUD

### Arquitectura

```
Usuario → asignado a → Rol → contiene → Permisos → valida → Rutas/Acciones
```

---

## 🔧 Sistema Dinámico de Roles

### Roles Protegidos vs Roles Personalizados

✅ **Roles protegidos** - Roles base del sistema que no se pueden eliminar ni modificar

- ✅ Superusuario
- ✅ Administrador
- ✅ Encargado de Acreditación
  ✅ **Capacidades funcionales** derivadas para desacoplar UI de nombres técnicos de permisos
- ✅ Profesor

Estos roles **NO** pueden ser eliminados ni renombrados. Sus permisos pueden ser consultados pero están definidos en `config/permissions.php`.

**Roles Personalizados**:

- ✅ Pueden ser creados por el Superusuario
- ✅ Asistente de Acreditación
- ✅ Se les asignan permisos desde la interfaz
- ✅ Pueden ser editados y eliminados (si no tienen usuarios asignados)

Estos roles **NO** pueden ser eliminados ni renombrados. Sus permisos y capacidades se definen en `config/permissions.php` y `config/access.php`.

### API Endpoints para Gestión Dinámica

```php
// Obtener estructura de módulos y permisos para UI
GET /api/roles/modules
// Response:
{
  "usuarios": {
    "label": "Gestión de Usuarios",
    "permissions": [
      { "name": "usuarios.view", "label": "Ver usuarios" },
      { "name": "usuarios.create", "label": "Crear usuarios" },
      // ...
    ]
  },
  // ... otros módulos
}

// Obtener roles agrupados (protegidos vs personalizados)
GET /api/roles/grouped
// Response:
{
  "protected": [

### Contrato de Capacidades

Las pantallas del frontend ya no dependen solo de permisos atómicos. También consumen capacidades funcionales derivadas desde backend.

| Capacidad | Permisos equivalentes | Uso |
|---|---|---|
| `cap.admin.roles.manage` | `roles.create`, `roles.edit`, `roles.delete` | Menú y rutas de roles |
| `cap.admin.users.manage` | `usuarios.create`, `usuarios.edit`, `usuarios.delete` | Menú y rutas de usuarios |
| `cap.audit.view` | `bitacora.view` | Bitácora |
| `cap.evidence.assign` | `evidencias.assign`, `asignaciones.create`, `asignaciones.edit` | Entregables / asignaciones |
| `cap.evidence.view` | `evidencias.view`, `asignaciones.view` | Mis entregas |
| `cap.evidence.upload` | `archivos.upload` | Subida de archivos |
| `cap.extension.manage` | `solicitudes_ampliacion.approve`, `solicitudes_ampliacion.reject` | Gestionar solicitudes |
| `cap.extension.view` | `solicitudes_ampliacion.view` | Mis solicitudes |
| `cap.accreditation.process.view` | `procesos.view` | Procesos de acreditación |
| `cap.accreditation.model.view` | `modelos.view` | Modelos |
| `cap.accreditation.cycle.view` | `ciclos.view` | Ciclos |
| `cap.improvement.access` | `compromisos_mejora.view`, `compromisos_mejora.create`, `compromisos_mejora.edit` | Compromisos de mejora |
| `cap.approvals.view` | `aprobaciones.view` | Aprobaciones |
| `cap.reports.access` | `reportes.generate`, `reportes.export` | Informes |

### Aliases de Permisos

Para tolerar cambios históricos de nombres entre módulos, el backend acepta equivalencias en `config/access.php`.

| Permiso principal | Equivalencias |
|---|---|
| `evidencias.view` | `asignaciones.view` |
| `evidencias.assign` | `asignaciones.create`, `asignaciones.edit` |
    { "id": 1, "name": "Superusuario", "is_protected": true, "users_count": 2 }
  ],
  "custom": [
    { "id": 5, "name": "Auditor", "is_protected": false, "users_count": 0, "can_delete": true }
  ]
}

// Crear rol personalizado
POST /api/roles
{
  "name": "Auditor",
  "permissions": ["usuarios.view", "evidencias.view", "reportes.view"]
}

// Editar rol personalizado
PUT /api/roles/{id}
{
  "name": "Auditor Senior",
  "permissions": ["usuarios.view", "evidencias.view", "evidencias.edit", "reportes.view", "reportes.generate"]
}

// Eliminar rol personalizado (solo si no tiene usuarios asignados)
DELETE /api/roles/{id}
```

- ✅ Visualización de entregables y asignaciones
- ✅ Operación sobre asignaciones de evidencias
- ✅ Visualización/aprobación de solicitudes de ampliación
- ✅ Visualización de aprobaciones
- ✅ Generación y exportación de informes
- ✅ Lectura de estructura académica para contexto operativo
- ❌ No puede administrar usuarios
- ❌ No puede administrar roles
- ❌ No puede gestionar bitácora

### Validaciones del Sistema

1. **Protección de roles del sistema**: Los roles protegidos no pueden ser eliminados.

### 5. **Profesor**

3. **Permisos solo del Superusuario**: Solo el Superusuario tiene permisos `roles.create`, `roles.edit`, `roles.delete`.
4. **El Administrador puede ver roles**: Para asignar roles a usuarios, pero no crearlos/editarlos.

---

## 👥 Roles del Sistema

### 1. **Superusuario**

**Descripción:** Acceso total al sistema sin restricciones.

**Permisos:** TODOS los permisos del sistema.

**Usuarios:** Desarrolladores, administradores del sistema.

---

### 2. **Administrador** (Coordinador de Carrera)

**Descripción:** Gestión completa de su carrera específica.

**Permisos:**

- ✅ Gestión de usuarios de su carrera (crear, editar, eliminar)
- ✅ Gestión completa de evidencias
- ✅ Asignación de evidencias a profesores
- ✅ Aprobación de solicitudes de ampliación
- ✅ Gestión de compromisos de mejora
- ✅ Gestión de ciclos de acreditación
- ✅ Generación de reportes
- ✅ Visualización de bitácora
- ❌ No puede modificar la estructura académica base (universidades, sedes)

---

### 3. **Encargado de Acreditación**

**Descripción:** Evaluación y aprobación de evidencias y criterios.

**Permisos:**

- ✅ Visualización de usuarios
- ✅ Edición de estados de evidencias
- ✅ Asignación de evidencias
- ✅ Aprobación/rechazo de solicitudes de ampliación
- ✅ Aprobación/rechazo de criterios
- ✅ Creación de compromisos de mejora
- ✅ Generación de reportes
- ✅ Hacer archivos públicos (para SINAES)
- ❌ No puede eliminar evidencias
- ❌ No puede gestionar usuarios

---

### 4. **Profesor**

**Descripción:** Gestión de evidencias asignadas.

**Permisos:**

- ✅ Visualización de estructura académica (solo lectura)
- ✅ Edición de evidencias **asignadas a él**
- ✅ Subida y descarga de archivos
- ✅ Creación de solicitudes de ampliación **propias**
- ✅ Visualización de compromisos de mejora asignados
- ✅ Generación de reportes
- ❌ No puede crear nuevas evidencias
- ❌ No puede asignar evidencias
- ❌ No puede aprobar solicitudes

---

## 📊 Permisos por Módulo

### Nomenclatura

Los permisos siguen el formato: `modulo.accion`

**Acciones disponibles:**

- `view` - Ver/listar
- `create` - Crear
- `edit` - Editar
- `delete` - Eliminar
- `assign` - Asignar (evidencias)
- `approve` - Aprobar (solicitudes, criterios)
- `reject` - Rechazar (solicitudes, criterios)
- `generate` - Generar (reportes)
- `export` - Exportar (reportes, bitácora)
- `upload` - Subir (archivos)
- `download` - Descargar (archivos)
- `make_public` - Hacer público (archivos)

### Tabla de Permisos

| Módulo                     | Permisos                                    | Descripción                |
| -------------------------- | ------------------------------------------- | -------------------------- |
| **usuarios**               | view, create, edit, delete                  | Gestión de usuarios        |
| **roles**                  | view, create, edit, delete                  | Gestión de roles           |
| **universidades**          | view, create, edit, delete                  | Estructura académica       |
| **campuses**               | view, create, edit, delete                  | Sedes universitarias       |
| **carreras**               | view, create, edit, delete                  | Carreras académicas        |
| **dimensiones**            | view, create, edit, delete                  | Marco SINAES               |
| **componentes**            | view, create, edit, delete                  | Marco SINAES               |
| **criterios**              | view, create, edit, delete                  | Marco SINAES               |
| **estandares**             | view, create, edit, delete                  | Marco SINAES               |
| **evidencias**             | view, create, edit, delete, assign          | Evidencias de acreditación |
| **asignaciones**           | view, create, edit, delete                  | Asignación de evidencias   |
| **archivos**               | view, upload, download, delete, make_public | Gestión de archivos        |
| **solicitudes_ampliacion** | view, create, edit, delete, approve, reject | Solicitudes RF-15          |
| **aprobaciones**           | view, approve, reject                       | Aprobación de criterios    |
| **compromisos_mejora**     | view, create, edit, delete                  | Compromisos de mejora      |
| **ciclos**                 | view, create, edit, delete                  | Ciclos de acreditación     |
| **reportes**               | view, generate, export                      | Reportes del sistema       |
| **notificaciones**         | view, create, delete                        | Notificaciones             |
| **bitacora**               | view, export                                | Auditoría del sistema      |

---

## ⚙️ Configuración

### Archivo de Configuración

**Ubicación:** `backend/config/permissions.php`

Este archivo es la **fuente única de verdad** para permisos y roles.

```php
return [
    // Módulos y acciones
    'modules' => [
        'usuarios' => ['view', 'create', 'edit', 'delete'],
        'evidencias' => ['view', 'create', 'edit', 'delete', 'assign'],
        // ...
    ],

    // Roles y permisos asignados
    'roles' => [
        'Superusuario' => [
            'all_permissions' => true, // TODOS los permisos
        ],
        'Administrador' => [
            'usuarios.view',
            'usuarios.create',
            // ...
        ],
        // ...
    ],

    // Descripciones para frontend
    'descriptions' => [
        'usuarios.view' => 'Ver usuarios',
        'usuarios.create' => 'Crear usuarios',
        // ...
    ],
];
```

### Seeder

### Estado actual de seeders

Los seeders de seguridad están alineados con el contrato central:

- `PermissionSeeder`: crea permisos y roles desde `config/permissions.php`.
- `UserSeeder`: crea roles desde la config y asigna usuarios LDAP.
- `RolesAndPermissionsSeeder`: se mantiene como alternativa combinada para escenarios de carga completa.

### Asignación actual de usuarios seed

| Usuario                         | Rol                       |
| ------------------------------- | ------------------------- |
| Naydelin Nayeli Jiron Castellon | Superusuario              |
| Jose Andres Jara Arias          | Administrador             |
| Marisol Hidalgo Murillo         | Profesor                  |
| Ian Enmanuel Villegas Jimenez   | Encargado de Acreditación |
| Ana Cristina Zuniga Cardenas    | Profesor                  |

Los permisos se crean automáticamente ejecutando:

```bash
php artisan db:seed --class=PermissionSeeder
```

El seeder:

1. Lee la configuración de `config/permissions.php`
2. Crea todos los permisos en la base de datos
3. Crea los roles
4. Asigna permisos a cada rol
5. Asigna rol Superusuario a usuarios específicos

### Regenerar Permisos

Si modificas `config/permissions.php`:

```bash
php artisan db:seed --class=PermissionSeeder
```

El seeder es **idempotente** (puede ejecutarse múltiples veces sin duplicar datos).

---

## 🔧 Uso en Backend

### 1. Proteger Rutas con Middleware

#### Verificar Permiso Específico

```php
Route::get('/usuarios', [UserController::class, 'index'])
    ->middleware('permission:usuarios.view');
```

#### Verificar Rol

```php
Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])
    ->middleware('role:Superusuario');
```

#### Verificar Rol O Permiso

```php
Route::post('/evidencias', [EvidenceController::class, 'store'])
    ->middleware('role_or_permission:Administrador|evidencias.create');
```

#### Agrupar Rutas

```php
Route::middleware(['auth:sanctum', 'permission:evidencias.view'])->group(function () {
    Route::get('/evidencias', [EvidenceController::class, 'index']);
    Route::get('/evidencias/{id}', [EvidenceController::class, 'show']);
});
```

---

### 2. Usar Policies en Controllers

Las **Policies** realizan autorización granular a nivel de modelo.

#### Authorizar en Controller

```php
public function update(Request $request, Evidence $evidence)
{
    // Lanza excepción 403 si no tiene permiso
    $this->authorize('update', $evidence);

    // Continuar con la lógica...
}
```

#### Verificar Sin Lanzar Excepción

```php
if ($request->user()->can('update', $evidence)) {
    // Usuario tiene permiso
} else {
    // Usuario NO tiene permiso
}
```

#### Verificar en Blade (si usas vistas)

```php
@can('update', $evidence)
    <button>Editar</button>
@endcan
```

---

### 3. Verificar Permisos Manualmente

```php
use Illuminate\Support\Facades\Gate;

// Verificar permiso específico
if (Gate::allows('evidencias.edit')) {
    // Usuario tiene permiso
}

// Verificar si tiene alguno de varios permisos
if (Gate::any(['evidencias.edit', 'evidencias.delete'])) {
    // Usuario tiene al menos uno de los permisos
}

// Verificar si tiene todos los permisos
if (Gate::all(['evidencias.view', 'evidencias.edit'])) {
    // Usuario tiene ambos permisos
}
```

---

### 4. Verificar Roles

```php
// En cualquier parte del código donde tengas acceso al usuario
$user = auth()->user();

// Verificar rol único
if ($user->hasRole('Superusuario')) {
    // Es Superusuario
}

// Verificar múltiples roles (OR)
if ($user->hasAnyRole(['Administrador', 'Encargado de Acreditación'])) {
    // Es Administrador O Encargado
}

// Verificar todos los roles (AND)
if ($user->hasAllRoles(['Administrador', 'Profesor'])) {
    // Es AMBOS Administrador Y Profesor
}
```

---

## 🌐 Uso en Frontend

### Endpoint de Permisos

**URL:** `GET /api/auth/permissions`

**Headers:**

```
Authorization: Bearer {token}
```

**Respuesta:**

```json
{
    "roles": ["Administrador"],
    "permissions": [
        "usuarios.view",
        "usuarios.create",
        "usuarios.edit",
        "usuarios.delete",
        "evidencias.view",
        "evidencias.create",
        "evidencias.edit",
        "evidencias.delete",
        "evidencias.assign"
        // ...
    ],
    "permissions_with_descriptions": {
        "usuarios.view": "Ver usuarios",
        "usuarios.create": "Crear usuarios",
        "evidencias.view": "Ver evidencias"
        // ...
    },
    "direct_permissions": [] // Permisos asignados directamente (sin rol)
}
```

---

### Verificar Permisos en Frontend

#### 1. Guardar Permisos al Iniciar Sesión

```typescript
// services/AuthService.ts
import axios from "axios";

interface PermissionsResponse {
    roles: string[];
    permissions: string[];
    permissions_with_descriptions: Record<string, string>;
    direct_permissions: string[];
}

export class AuthService {
    async getPermissions(): Promise<PermissionsResponse> {
        const response = await axios.get("/api/auth/permissions");
        return response.data;
    }
}
```

#### 2. Context de Permisos (React)

```typescript
// contexts/PermissionsContext.tsx
import { createContext, useContext, useState, useEffect } from 'react';

interface PermissionsContextType {
  permissions: string[];
  roles: string[];
  hasPermission: (permission: string) => boolean;
  hasRole: (role: string) => boolean;
  hasAnyPermission: (permissions: string[]) => boolean;
  hasAllPermissions: (permissions: string[]) => boolean;
}

const PermissionsContext = createContext<PermissionsContextType | null>(null);

export const PermissionsProvider: React.FC = ({ children }) => {
  const [permissions, setPermissions] = useState<string[]>([]);
  const [roles, setRoles] = useState<string[]>([]);

  useEffect(() => {
    // Cargar permisos al montar
    fetchPermissions();
  }, []);

  const fetchPermissions = async () => {
    try {
      const response = await axios.get('/api/auth/permissions');
      setPermissions(response.data.permissions);
      setRoles(response.data.roles);
    } catch (error) {
      console.error('Error al cargar permisos:', error);
    }
  };

  const hasPermission = (permission: string): boolean => {
    return permissions.includes(permission);
  };

  const hasRole = (role: string): boolean => {
    return roles.includes(role);
  };

  const hasAnyPermission = (perms: string[]): boolean => {
    return perms.some(p => permissions.includes(p));
  };

  const hasAllPermissions = (perms: string[]): boolean => {
    return perms.every(p => permissions.includes(p));
  };

  return (
    <PermissionsContext.Provider value={{
      permissions,
      roles,
      hasPermission,
      hasRole,
      hasAnyPermission,
      hasAllPermissions
    }}>
      {children}
    </PermissionsContext.Provider>
  );
};

export const usePermissions = () => {
  const context = useContext(PermissionsContext);
  if (!context) {
    throw new Error('usePermissions debe usarse dentro de PermissionsProvider');
  }
  return context;
};
```

#### 3. Hook Personalizado

```typescript
// hooks/usePermissions.ts
import { usePermissions } from "@/contexts/PermissionsContext";

export const useHasPermission = (permission: string): boolean => {
    const { hasPermission } = usePermissions();
    return hasPermission(permission);
};

export const useHasRole = (role: string): boolean => {
    const { hasRole } = usePermissions();
    return hasRole(role);
};
```

#### 4. Componente de Protección

```typescript
// components/Can.tsx
import { usePermissions } from '@/contexts/PermissionsContext';

interface CanProps {
  permission?: string;
  role?: string;
  anyPermissions?: string[];
  allPermissions?: string[];
  fallback?: React.ReactNode;
  children: React.ReactNode;
}

export const Can: React.FC<CanProps> = ({
  permission,
  role,
  anyPermissions,
  allPermissions,
  fallback = null,
  children
}) => {
  const { hasPermission, hasRole, hasAnyPermission, hasAllPermissions } = usePermissions();

  let hasAccess = false;

  if (permission) {
    hasAccess = hasPermission(permission);
  } else if (role) {
    hasAccess = hasRole(role);
  } else if (anyPermissions) {
    hasAccess = hasAnyPermission(anyPermissions);
  } else if (allPermissions) {
    hasAccess = hasAllPermissions(allPermissions);
  }

  return hasAccess ? <>{children}</> : <>{fallback}</>;
};
```

---

## 💡 Ejemplos Prácticos

### Ejemplo 1: Mostrar Botón Solo Si Tiene Permiso

```tsx
import { Can } from "@/components/Can";

function EvidenciasList() {
    return (
        <div>
            <h1>Evidencias</h1>

            {/* Solo se muestra si tiene permiso evidencias.create */}
            <Can permission="evidencias.create">
                <button onClick={handleCreate}>Nueva Evidencia</button>
            </Can>

            {/* Lista de evidencias... */}
        </div>
    );
}
```

---

### Ejemplo 2: Deshabilitar Botón en Lugar de Ocultarlo

```tsx
import { useHasPermission } from "@/hooks/usePermissions";

function EvidenciaCard({ evidencia }) {
    const canEdit = useHasPermission("evidencias.edit");
    const canDelete = useHasPermission("evidencias.delete");

    return (
        <div className="card">
            <h3>{evidencia.nombre}</h3>

            <button
                onClick={handleEdit}
                disabled={!canEdit}
                title={!canEdit ? "No tienes permiso para editar" : ""}
            >
                Editar
            </button>

            <button
                onClick={handleDelete}
                disabled={!canDelete}
                title={!canDelete ? "No tienes permiso para eliminar" : ""}
            >
                Eliminar
            </button>
        </div>
    );
}
```

---

### Ejemplo 3: Ocultar Menú Según Rol

```tsx
import { usePermissions } from "@/contexts/PermissionsContext";

function Sidebar() {
    const { hasRole, hasPermission } = usePermissions();

    return (
        <nav>
            {/* Todos los usuarios ven esto */}
            <MenuItem to="/dashboard">Dashboard</MenuItem>

            {/* Solo Administrador y Superusuario ven esto */}
            {hasRole("Administrador") ||
                (hasRole("Superusuario") && (
                    <MenuItem to="/usuarios">Gestión de Usuarios</MenuItem>
                ))}

            {/* Solo quienes tengan el permiso ven esto */}
            {hasPermission("bitacora.view") && (
                <MenuItem to="/bitacora">Bitácora del Sistema</MenuItem>
            )}

            {/* Solo Superusuario ve esto */}
            {hasRole("Superusuario") && (
                <MenuItem to="/admin/configuracion">Configuración</MenuItem>
            )}
        </nav>
    );
}
```

---

### Ejemplo 4: Verificar Múltiples Permisos

```tsx
import { Can } from "@/components/Can";

function EvidenciaDetailPage() {
    return (
        <div>
            <h1>Detalle de Evidencia</h1>

            {/* Se muestra si tiene CUALQUIERA de estos permisos (OR) */}
            <Can anyPermissions={["evidencias.edit", "evidencias.delete"]}>
                <div className="actions">
                    <button>Editar</button>
                    <button>Eliminar</button>
                </div>
            </Can>

            {/* Se muestra si tiene TODOS estos permisos (AND) */}
            <Can allPermissions={["archivos.upload", "archivos.make_public"]}>
                <button>Subir y Publicar Archivo</button>
            </Can>
        </div>
    );
}
```

---

### Ejemplo 5: Mostrar Mensaje Alternativo

```tsx
import { Can } from "@/components/Can";

function AdminPanel() {
    return (
        <Can
            role="Administrador"
            fallback={
                <div className="alert alert-warning">
                    No tienes acceso al panel de administración.
                </div>
            }
        >
            <div className="admin-panel">{/* Contenido del panel... */}</div>
        </Can>
    );
}
```

---

## 🔄 Flujo Completo

```
1. Usuario hace login
   ↓
2. Backend autentica con LDAP
   ↓
3. Backend retorna usuario con roles
   ↓
4. Frontend llama a GET /api/auth/permissions
   ↓
5. Frontend guarda permisos en contexto
   ↓
6. Componentes verifican permisos para mostrar/ocultar elementos
   ↓
7. Usuario intenta acción (ej: crear evidencia)
   ↓
8. Frontend verifica permiso antes de enviar request
   ↓
9. Backend valida permiso con middleware/policy
   ↓
10. Se ejecuta la acción O se retorna 403
```

---

## ✅ Checklist de Implementación

### Backend

- [x] Definir permisos en `config/permissions.php`
- [x] Crear PermissionSeeder
- [x] Implementar Policies para modelos críticos
- [x] Proteger rutas con middleware `permission:`
- [x] Crear endpoint `GET /api/auth/permissions`
- [x] Ejecutar seeder para crear permisos

### Frontend

- [ ] Crear PermissionsContext
- [ ] Crear componente `<Can>`
- [ ] Crear hooks `useHasPermission`, `useHasRole`
- [ ] Llamar a `/api/auth/permissions` al iniciar sesión
- [ ] Proteger componentes según permisos
- [ ] Ocultar/deshabilitar botones según permisos
- [ ] Proteger rutas del router según roles

---

## 🎓 Conceptos Clave

### Permission vs Role

- **Permission:** Acción específica (ej: `evidencias.create`)
- **Role:** Conjunto de permisos (ej: `Administrador` tiene `evidencias.create`, `evidencias.edit`, etc.)

### Policy vs Middleware

- **Middleware:** Protección a nivel de ruta (antes de llegar al controller)
- **Policy:** Autorización granular a nivel de modelo (dentro del controller)

### Direct Permission vs Role Permission

- **Direct Permission:** Permiso asignado directamente al usuario (raro)
- **Role Permission:** Permiso heredado del rol del usuario (normal)

---

## 📞 Contacto y Soporte

Para dudas sobre el sistema de permisos:

- Revisar este documento
- Consultar `config/permissions.php`
- Revisar las Policies en `app/Policies/`

---

**Última actualización:** Febrero 2026  
**Versión:** 1.0.0  
**Autor:** Equipo SAAC-UNA
