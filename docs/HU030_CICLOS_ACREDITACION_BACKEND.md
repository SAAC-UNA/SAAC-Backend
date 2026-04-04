# HU-030 — Gestión de Ciclos de Acreditación (Backend)

## Endpoints

Base URL: `http://localhost:8000/api`  
Todos los endpoints requieren `Authorization: Bearer {token}` (Sanctum).

| Método | Endpoint | Permiso requerido | Descripción |
|--------|----------|-------------------|-------------|
| GET | `/estructura/ciclos-acreditacion` | `ciclos.view` | Listar ciclos (paginado, filtrado por rol) |
| GET | `/estructura/ciclos-acreditacion/{id}` | `ciclos.view` | Obtener ciclo por ID |
| POST | `/estructura/ciclos-acreditacion` | `ciclos.create` | Crear nuevo ciclo |
| PATCH | `/estructura/ciclos-acreditacion/{id}` | `ciclos.edit` | Actualizar ciclo (solo si está activo) |
| PUT | `/estructura/ciclos-acreditacion/{id}` | `ciclos.edit` | Actualizar ciclo completo (solo si está activo) |
| DELETE | `/estructura/ciclos-acreditacion/{id}` | `ciclos.delete` | Siempre retorna 403 (eliminación física no permitida) |
| PATCH | `/estructura/ciclos-acreditacion/{id}/reactivar` | `ciclos.reactivar` | Reactivar ciclo inactivo/completado — **solo Superusuario** |

---

## Cuerpos de Request

### POST — Crear ciclo
```json
{
    "carrera_sede_id": 1,
    "modelo_estructura_id": 1,
    "nombre": "Ciclo de Acreditación 2026-2030",
    "estado": "activo"
}
```
- `carrera_sede_id` — requerido, debe existir en `CARRERA_SEDE`
- `modelo_estructura_id` — requerido, debe existir en `MODELO_ESTRUCTURA`
- `nombre` — requerido, string, max 50 chars, único por `carrera_sede_id`
- `estado` — opcional, default `"activo"` (valores: `activo`, `inactivo`, `completado`)

### PATCH — Actualizar ciclo
```json
{
    "nombre": "Nuevo Nombre",
    "estado": "inactivo"
}
```
- Al menos un campo requerido, de lo contrario retorna `422`
- Solo funciona si el ciclo tiene `estado = "activo"`, de lo contrario retorna `403`
- `carrera_sede_id` no es editable en update (se ignora si se envía)

### PATCH — Reactivar ciclo (solo Superusuario)
```
PATCH /estructura/ciclos-acreditacion/{id}/reactivar
Body: (vacío, no se requiere)
```
- No acepta ni procesa ningún campo del body
- Fuerza el estado a `activo` internamente
- Retorna `422` si el ciclo ya está activo
- Retorna `422` si ya existe otro ciclo activo en la misma `carrera_sede_id` (AC-6)
- Retorna `403` si el usuario no tiene el permiso `ciclos.reactivar`

### Query params — GET index
```
GET /estructura/ciclos-acreditacion?per_page=10
```
- `per_page` — opcional, int 1-50, default 15

---

## Respuesta exitosa (200 / 201)

```json
{
    "data": {
        "ciclo_acreditacion_id": 1,
        "carrera_sede_id": 1,
        "modelo_estructura_id": 1,
        "nombre": "Ciclo de Acreditación 2024-2028",
        "estado": "activo",
        "created_at": "2026-03-21T00:00:00.000000Z",
        "updated_at": "2026-03-21T00:00:00.000000Z",
        "modelo_estructura": {
            "modelo_estructura_id": 1,
            "nombre": "SINAES 2018 - Estructura Tradicional",
            "tipo": "tradicional",
            "version": "2018"
        },
        "carrera_sede": {
            "carrera_sede_id": 1,
            "sede_id": 1,
            "carrera_id": 1
        }
    }
}
```

---

## Checklist de Criterios de Aceptación

### AC-1 — Crear ciclo con campos válidos
- [x] `POST` con `carrera_sede_id`, `modelo_estructura_id` y `nombre` retorna `201`
- [x] Response incluye `ciclo_acreditacion_id` generado
- [x] `estado` default es `activo` si no se envía
- [x] `modelo_estructura.tipo` correcto según el modelo asignado

### AC-2 — Validaciones de entrada
- [x] `POST` sin `carrera_sede_id` retorna `422` con mensaje en español
- [x] `POST` sin `modelo_estructura_id` retorna `422` con mensaje en español
- [x] `POST` sin `nombre` retorna `422` con mensaje en español
- [x] `POST` con `modelo_estructura_id` inexistente retorna `422`
- [x] `POST` con `estado` inválido retorna `422`
- [x] `PATCH` vacío `{}` retorna `422` con `errors.general`
- [x] `PATCH` con `nombre` duplicado dentro de la misma `carrera_sede` retorna `422`
- [x] `PATCH` con `nombre` duplicado en **otra** `carrera_sede` retorna `200` (unicidad es per sede)
- [x] `POST` con `estado = "activo"` cuando ya existe un ciclo activo en la misma sede retorna `422`
- [x] `PATCH` cambiando `estado` a `"activo"` cuando ya hay otro activo en la misma sede retorna `422`
- [x] `PATCH` sobre el único ciclo activo de su sede (sin cambiar estado) retorna `200`

### AC-3 — Filtrado automático por rol
- [x] `GET /ciclos-acreditacion` aplica scope `BaseCareer` automáticamente
- [x] Usuarios con rol limitado solo ven ciclos de sus `carrera_sede` asignadas
- [x] Administrador/Superusuario ven todos los ciclos
- [x] Paginación funcional: respuesta incluye `meta.total`, `meta.per_page`, `meta.last_page`

### AC-4 — Bloqueo de edición en ciclos no activos
- [x] `PATCH` sobre ciclo con `estado = "inactivo"` retorna `403`
- [x] `PATCH` sobre ciclo con `estado = "completado"` retorna `403`
- [x] `PATCH` sobre ciclo con `estado = "activo"` retorna `200`
- [x] La validación de Policy se ejecuta **después** de la validación del request

### AC-6 — Máximo un ciclo activo por carrera+sede
- [x] `POST` con `estado = "activo"` y ya existe activo → `422` con error en `carrera_sede_id`
- [x] `PATCH` cambiando a `"activo"` con otro ya activo en la misma sede → `422`
- [x] El ciclo actual se excluye de la verificación (no se bloquea a sí mismo)
- [x] Carreras en sedes distintas son independientes (Química Central ≠ Química Regional)

### AC-5 — Control de permisos
- [x] `GET` sin token retorna `401`
- [x] `GET` con token sin permiso `ciclos.view` retorna `403`
- [x] `POST` con token sin permiso `ciclos.create` retorna `403`
- [x] `PATCH` con token sin permiso `ciclos.edit` retorna `403`
- [x] `DELETE` siempre retorna `403` (eliminación física deshabilitada por Policy)
- [x] `PATCH /reactivar` con token sin permiso `ciclos.reactivar` retorna `403`

### AC-R — Reactivación de ciclos (Superusuario)
- [x] `PATCH /reactivar` sobre ciclo `inactivo` sin conflicto retorna `200` con ciclo `activo`
- [x] `PATCH /reactivar` sobre ciclo `completado` sin conflicto retorna `200` con ciclo `activo`
- [x] `PATCH /reactivar` sobre ciclo ya `activo` retorna `422` `"El ciclo ya está activo."`
- [x] `PATCH /reactivar` cuando ya hay otro activo en la misma `carrera_sede` retorna `422` (AC-6)
- [x] Solo el rol `Superusuario` tiene el permiso `ciclos.reactivar`
- [x] `Administrador` y demás roles reciben `403` al intentar reactivar

---

## Checklist de Implementación Backend

### Capa de Datos
- [x] Migración `create_ciclo_acreditacion_table` — tabla `CICLO_ACREDITACION`
- [x] Modelo `AccreditationCycle` con `$fillable`, constantes de estado y helpers (`isEditable()`)
- [x] Modelo extiende `BaseCareer` para aplicar filtros de rol automáticamente
- [x] Relaciones: `careerCampus()`, `modeloEstructura()`, `processes()`
- [x] Seeder `AccreditationCycleSeeder` con datos de prueba

### Capa de Lógica
- [x] `AccreditationCycleService` — `getAll()`, `findById()`, `create()`, `update()`, `delete()`
- [x] Todas las operaciones del Service cargan `careerCampus` y `modeloEstructura` (eager load)
- [x] `create()` usa `STATUS_ACTIVE` como default de estado

### Capa HTTP
- [x] `AccreditationCycleController` — `index`, `show`, `store`, `update`, `destroy`
- [x] Controller completamente delegado al Service (sin Eloquent directo)
- [x] `AccreditationCycleRequest` — validaciones para GET / POST / PUT / PATCH
  - [x] Campos `required` solo en POST, `sometimes` en GET y PATCH
  - [x] Unique por `(nombre, carrera_sede_id)` con ignore en update
  - [x] `withValidator` exige al menos un campo en update
  - [x] `withValidator` valida máximo 1 ciclo activo por `carrera_sede_id` (AC-6)
- [x] `AccreditationCycleResource` — expone `modelo_estructura` y `carrera_sede` via `whenLoaded()`

### Autorización
- [x] `AccreditationCyclePolicy` registrada en `AuthServiceProvider`
- [x] `viewAny` / `view` → `ciclos.view`
- [x] `create` → `ciclos.create`
- [x] `update` → `isEditable() && ciclos.edit` (AC-4)
- [x] `delete` → siempre `false`
- [x] `reactivate` → `ciclos.reactivar` (bypasea `isEditable()`, solo Superusuario)

### Observabilidad
- [x] `AuditObserver` registrado para `AccreditationCycle` en `AppServiceProvider`
- [x] Auditoría en `created`, `updated`, `deleted` con nombre `"Ciclos de Acreditación"`

### Rutas
- [x] GET protegido con middleware `permission:ciclos.view`
- [x] POST protegido con middleware `permission:ciclos.create`
- [x] PATCH/PUT protegidos con middleware `permission:ciclos.edit`
- [x] DELETE protegido con middleware `permission:ciclos.delete`
- [x] `PATCH /{id}/reactivar` protegido con middleware `permission:ciclos.reactivar`

### Seeder
- [x] `AccreditationCycleSeeder` corregido: ciclo `2024-2028` nace como `completado`, ciclo `2025-2029` como `activo`
- [x] `PermissionSeeder` actualizado: `ciclos.reactivar` registrado y asignado a `Superusuario` automáticamente vía `all_permissions: true`

---

## Adaptación al Modelo Flexible

### Cambio implementado — Protección de coherencia al cambiar modelo (sprint 2)

**Problema identificado:**
`AccreditationCycleService::update()` permitía cambiar `modelo_estructura_id` libremente,
incluso en ciclos que ya tenían procesos con evidencias creadas bajo el modelo anterior.
Efecto: evidencias con `criterio_id` quedarían en un ciclo que dice ser `elemento_flexible`,
o evidencias con `elemento_id` en un ciclo que dice ser `tradicional` — datos inconsistentes.

**Cambio en `AccreditationCycleService::update()`:**
Antes de actualizar, si el request incluye un `modelo_estructura_id` diferente al actual,
se verifica si el ciclo ya tiene procesos. Si los tiene, se lanza `\InvalidArgumentException`
con mensaje descriptivo.

**Cambio en `AccreditationCycleController::update()`:**
Se envuelve la llamada al service en `try/catch`. Si el service lanza `\InvalidArgumentException`,
el controller retorna `422` con el mensaje de error.

**Comportamiento resultante:**

| Caso | Resultado |
|---|---|
| Cambiar `nombre`, `estado` u otros campos | ✅ Sin restricción |
| Cambiar `modelo_estructura_id` en ciclo **sin** procesos | ✅ Permitido |
| Cambiar `modelo_estructura_id` en ciclo **con** procesos | ❌ `422` con mensaje explicativo |

### AC-M1 — Protección del modelo en ciclos con actividad
- [x] `PATCH` cambiando `modelo_estructura_id` en ciclo sin procesos → `200` ✅
- [x] `PATCH` cambiando `modelo_estructura_id` en ciclo con procesos → `422` con mensaje en español ✅
- [x] `PATCH` sin cambiar `modelo_estructura_id` (mismo valor o campo ausente) → sin restricción ✅

## Usuarios de prueba (LDAP)

| Usuario | Cédula | Rol | Permisos ciclos |
|---------|--------|-----|----------------|
| Administrador | `208330811` | Administrador | view, create, edit |
| Superusuario | `801490957` | Superusuario | Todos (incluyendo `reactivar`) |

Password de todos: `password123`

**Login:**
```
POST /api/auth/login
{ "cedula": "208330811", "password": "password123" }
```
