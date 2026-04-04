# Arquitectura B — Cambios rama Cristina (`hu-30_feature/hu-flexible-modelo2_cristina`)

## Contexto

El sistema SAAC maneja dos modelos de estructura de acreditación, discriminados por
`MODELO_ESTRUCTURA.tipo`:

| Constante | Valor | Descripción |
|---|---|---|
| `StructureModel::TIPO_TRADICIONAL` | `'tradicional'` | Criterio → Evidencia → Archivo |
| `StructureModel::TIPO_ELEMENTO_FLEXIBLE` | `'elemento_flexible'` | Elemento → ElementoAsignacion → Archivo |

**Arquitectura B (adoptada)**: los dos flujos son paralelos e independientes.
`EVIDENCIA` pertenece **únicamente** al modelo tradicional. `ELEMENTO` pertenece
**únicamente** al modelo flexible. No existe dependencia cruzada entre ambas ramas.

```
Tradicional:  PROCESO → CRITERIO → EVIDENCIA → EVIDENCIA_ASIGNACION → ARCHIVO(evidencia_id)
Flexible:     PROCESO → ELEMENTO → ELEMENTO_ASIGNACION              → ARCHIVO(elemento_id)
```

---

## Problema detectado — Arquitectura A (contaminación)

Commits anteriores (`7578920`, `7b33dc0`) de la rama de Cristina introdujeron
código de **Arquitectura A** (errónea): agregaron `elemento_id` a la tabla `EVIDENCIA`
para intentar conectar evidencias con elementos del modelo flexible.

**Archivos afectados**:
- Migración `043_add_elemento_id_to_evidencia_table.php` — añadía `elemento_id` FK a `EVIDENCIA`
- `Evidence.php` — `elemento_id` en `$fillable`, relación `elemento()`
- `EvidenceResource.php` — campo `elemento_id` y bloque `'elemento'` en el response
- `EvidenceService.php` — `'elemento'` en `WITH_BASE`, persistencia de `elemento_id`
- `EvidenceRequest.php` — validación XOR entre `criterio_id` y `elemento_id`
- `FilterEvidenceRequest.php` — filtro `elemento_id` sobre evidencias
- `EvidenceAssignmentService.php` — guardias incompletas que intentaban detectar el modelo

---

## Correcciones aplicadas

### 1. Migración 047 — Limpieza de base de datos

**Archivo**: `database/migrations/047_remove_elemento_id_from_evidencia_table.php`

- Elimina en cascada los registros de `EVIDENCIA_ASIGNACION` hijos de evidencias huérfanas
  (que tenían `criterio_id = NULL` por las pruebas de Arquitectura A)
- Elimina los registros de `EVIDENCIA` con `criterio_id = NULL`
- Elimina la columna `elemento_id` de `EVIDENCIA` (con guarda idempotente)
- Restaura `criterio_id` como `NOT NULL`

> La migración `043` sigue en disco para preservar la cadena de `migrate:rollback`.
> Su efecto queda revertido por la `047`.

---

### 2. `app/Models/Evidence.php`

- Eliminado `'elemento_id'` de `$fillable`
- Eliminada relación `elemento()` (belongsTo StructureElement)

---

### 3. `app/Http/Resources/EvidenceResource.php`

- Eliminado campo `'elemento_id'` del array de respuesta
- Eliminado bloque condicional `'elemento'` (whenLoaded)

---

### 4. `app/Services/EvidenceService.php`

- `WITH_BASE`: eliminada carga eager `'elemento'` (ahora solo `['criterion.component.dimension']`)
- `create()`: eliminada persistencia de `elemento_id`, `criterio_id` vuelve a ser obligatorio
- `update()`: eliminada línea de persistencia de `elemento_id`
- `filterEvidences()`: eliminada variable `$elementoId` y bloque `where('elemento_id', ...)`

---

### 5. `app/Http/Requests/EvidenceRequest.php`

- `criterio_id`: vuelve a `required` en CREATE (ya no puede ser nulo)
- Eliminada regla `elemento_id` completamente
- `withValidator()`: simplificado — solo verifica que se envíe al menos un campo en UPDATE;
  eliminada lógica XOR entre `criterio_id` y `elemento_id`
- Eliminado mensaje `'elemento_id.exists'`; restaurado mensaje `'criterio_id.required'`

---

### 6. `app/Http/Requests/FilterEvidenceRequest.php`

- Eliminada regla de filtro `elemento_id` sobre evidencias
- Fusionados (del merge con `development`) los filtros por `dimension_id`, `componente_id`,
  `estandar_id` y sus `attributes()`

---

### 7. `app/Services/EvidenceAssignmentService.php`

- `assignEvidence()`: reemplazadas dos guardias incompletas de Arquitectura A por una
  **guarda de Arquitectura B** explícita:

```php
if ($tipoModelo === StructureModel::TIPO_ELEMENTO_FLEXIBLE) {
    throw new \InvalidArgumentException(
        'El proceso pertenece a un ciclo con modelo flexible. '
        . 'Use la API de asignaciones de elementos (POST /api/elementos-asignaciones) en su lugar.'
    );
}
```

---

## HUs implementadas en esta rama

| HU | Descripción | Estado |
|---|---|---|
| HU-007 | CRUD de asignaciones de elementos (`ELEMENTO_ASIGNACION`) | ✅ Committed + Testeado Postman |
| HU-008 | Subida de archivos con `elemento_id` (modelo flexible) + fix FileResource | ✅ Committed + Testeado Postman |
| HU-008b | SOLID completo capa HTTP para archivos de elemento (controller/resource/request propios) | ✅ Committed + Testeado Postman |
| HU-012 | Filtrado avanzado y exportación de elementos (Excel/PDF) | ✅ Committed |
| HU-013 | Retroalimentación equivalente en `ELEMENTO_ASIGNACION` (`Observada`/`Validada`) | ✅ Committed + Testeado Postman |
| HU-016 | Solicitud de ampliación — refactorizado a patrón Strategy (tradicional + flexible) | ✅ Committed |
| HU-016b | SOLID completo capa HTTP para ampliaciones de elemento (controller/request propios) | ✅ Committed + Testeado Postman |
| HU-018 | Notificaciones al asignar elementos (`NotifyElementAssignment`) | ✅ Committed |
| HU-023 | Enlace público para archivos sin autenticación (SINAES) | ✅ Committed + Testeado Postman |
| HU-030 | Ciclos de acreditación — CRUD + reactivar (sin cambios en esta rama) | ✅ Pre-existente |

---

## HU-012 — Filtrado avanzado y exportación de elementos

Implementa búsqueda y exportación sobre `ELEMENTO` para el modelo flexible,
equivalente al filtrado que ya existía para evidencias en el modelo tradicional.

### Nuevos archivos

| Archivo | Descripción |
|---|---|
| `app/Services/FilterElementService.php` | Filtros: búsqueda full-text, estado, responsable, categoría, fecha, paginación |
| `app/Services/TradicionalEvidenceFilterService.php` | Equivalente para evidencias del modelo tradicional |
| `app/Services/TradicionalEvidenceService.php` | Servicios CRUD de evidencias modelo tradicional |
| `app/Http/Requests/FilterElementRequest.php` | Validación de parámetros de filtro |
| `app/Exports/ElementsExport.php` | Exportación Excel vía Maatwebsite |
| `resources/views/exports/elements.blade.php` | Vista PDF de exportación |
| `database/migrations/2026_03_31_add_fulltext_index_to_elemento_table.php` | Índice FULLTEXT en `ELEMENTO.nombre` y `ELEMENTO.descripcion` |

### Nuevos endpoints

| Método | Ruta | Propósito |
|---|---|---|
| `GET` | `/api/estructura/elementos/filter` | Filtrado avanzado con paginación |
| `GET` | `/api/estructura/elementos/export/excel` | Exportar resultados a Excel |
| `GET` | `/api/estructura/elementos/export/pdf` | Exportar resultados a PDF |

---

## HU-016 — Refactor a patrón Strategy

La implementación original usaba un único `ExtensionRequestService` que mezclaba
lógica tradicional y flexible. Se refactorizó a patrón Strategy:

```
ExtensionRequestContract (interfaz)
├── AbstractExtensionRequestService  (lógica compartida)
│   ├── TradicionalExtensionRequestService  (evidencia_asignacion_id)
│   └── FlexibleExtensionRequestService     (elemento_asignacion_id)
```

- `ExtensionRequestController` resuelve la estrategia según el tipo de proceso
- `ExtensionRequestService.php` eliminado (reemplazado por los dos anteriores)
- Registrados como singletons en `AppServiceProvider`

---

## HU-023 — Enlace público sin autenticación

Permite compartir archivos con evaluadores externos (SINAES) sin necesidad de login.

### Endpoints

| Método | Ruta | Auth | Propósito |
|---|---|---|---|
| `POST` | `/api/archivos/{id}/make-public` | ✅ Bearer | Genera token UUID y URL pública con expiración |
| `POST` | `/api/archivos/{id}/revoke-public` | ✅ Bearer | Revoca el token (limpia `token_publico`, `is_publico=false`) |
| `POST` | `/api/archivos/bulk-make-public` | ✅ Bearer | Hace públicos varios archivos en una llamada |
| `GET` | `/api/p/{token}` | ❌ Sin auth | Descarga el archivo o redirige al enlace |

### Fix aplicado — `FileResource.php`

`$this->evidence` es `null` cuando el archivo pertenece a `elemento_id` (modelo flexible).
`relationLoaded()` devuelve `true` incluso cuando la relación carga `null`,
causando `Attempt to read property "evidencia_id" on null`.

```php
// ANTES (crasheaba con modelo flexible):
'evidencia' => $this->when(
    $this->relationLoaded('evidence'),
    fn() => ['evidencia_id' => $this->evidence->evidencia_id, ...]
),

// DESPUÉS (correcto):
'elemento_id' => $this->elemento_id,   // campo nuevo expuesto
'evidencia' => $this->when(
    $this->relationLoaded('evidence') && $this->evidence !== null,
    fn() => ['evidencia_id' => $this->evidence->evidencia_id, ...]
),
```

---

## Extensión implementada hoy (equivalente flexible HU-013/HU-016)

Se agregó soporte operativo para que el flujo flexible tenga capacidad equivalente a
retroalimentación y ampliación de plazo, sin romper Arquitectura B.

### Nuevos endpoints (modelo flexible)

| Método | Ruta | Propósito |
|---|---|---|
| `POST` | `/api/elementos-asignaciones/{id}/retroalimentacion` | Marcar asignación como `Observada` o `Validada` con comentario |
| `POST` | `/api/elementos-asignaciones/{id}/solicitud-ampliacion` | Crear solicitud de ampliación para asignación de elemento |

### Cambios de base de datos (Migration 048)

**Archivo**: `database/migrations/048_flexible_retroalimentacion_ampliacion.php`

- `ELEMENTO_ASIGNACION.estado` amplía enum con: `Observada`, `Validada`
- `SOLICITUD_AMPLIACION` agrega `elemento_asignacion_id` (nullable + FK)
- `SOLICITUD_AMPLIACION.evidencia_asignacion_id` pasa a nullable para soportar XOR
  entre flujo tradicional y flujo flexible

### Cambios de código relevantes

- `ElementAssignmentService::retroalimentar(...)`
  - Actualiza estado (`Observada`/`Validada`)
  - Crea comentario polimórfico (`Comment` sobre `ElementAssignment`)
  - Registra bitácora
  - Emite notificación al usuario asignado

- `ElementAssignmentService::solicitarAmpliacion(...)`
  - Valida ownership (solo usuario asignado)
  - Evita duplicados pendientes
  - Valida fecha sugerida (> fecha límite actual, máximo 30 días)
  - Crea `SOLICITUD_AMPLIACION` usando `elemento_asignacion_id`

- `ElementAssignmentController`
  - Agrega métodos `retroalimentar()` y `storeExtension()`

- `ExtensionRequest`
  - Agrega `elemento_asignacion_id` a `$fillable`
  - Agrega relación `elementAssignment()`

### Archivos nuevos y migraciones (HU-007/008/013/016/018/023)

| Archivo | Descripción |
|---|---|
| `app/Models/ElementAssignment.php` | Modelo ELEMENTO_ASIGNACION |
| `app/Events/ElementAssigned.php` | Evento disparado al asignar |
| `app/Listeners/NotifyElementAssignment.php` | Listener con cola, notifica al usuario |
| `app/Services/ElementAssignmentService.php` | Lógica de negocio de asignaciones |
| `app/Http/Requests/ElementAssignmentRequest.php` | Validación de requests |
| `app/Http/Controllers/ElementAssignmentController.php` | 7 endpoints REST |
| `app/Services/AbstractFileService.php` | Operaciones model-agnostic (makePublic, revokePublic, delete, download) |
| `app/Contracts/ExtensionRequestContract.php` | Interfaz Strategy para ampliaciones |
| `app/Services/AbstractExtensionRequestService.php` | Lógica compartida de ampliaciones |
| `app/Services/TradicionalExtensionRequestService.php` | Ampliaciones modelo tradicional |
| `app/Services/FlexibleExtensionRequestService.php` | Ampliaciones modelo flexible |
| `app/Http/Requests/StoreElementFileRequest.php` | Request exclusiva archivos de elemento (SOLID) |
| `app/Http/Resources/ElementFileResource.php` | Resource exclusiva archivos de elemento con metadatos HU-008 |
| `app/Http/Controllers/ElementFileController.php` | Controller exclusivo archivos de elemento (SOLID) |
| `app/Http/Requests/StoreFlexibleExtensionRequestRequest.php` | Request exclusiva ampliaciones flexible — valida `elemento_asignacion_id` (SOLID) |
| `app/Http/Controllers/FlexibleExtensionRequestController.php` | Controller exclusivo ampliaciones flexible — inyecta `FlexibleExtensionRequestService` (SOLID) |
| `database/migrations/044_add_estado_fecha_limite_to_elemento_table.php` | Estado y fecha límite en ELEMENTO |
| `database/migrations/045_create_elemento_asignacion_table.php` | Tabla ELEMENTO_ASIGNACION |
| `database/migrations/046_add_elemento_id_to_archivo_table.php` | `ARCHIVO.elemento_id` (Architecture B) |
| `database/migrations/047_remove_elemento_id_from_evidencia_table.php` | Limpieza Architecture A |
| `database/migrations/048_flexible_retroalimentacion_ampliacion.php` | Soporte HU-013/HU-016 en flujo flexible |

---

## Qué NO cambia (intencional)

- `ARCHIVO.elemento_id` — permanece; es la FK correcta de Architecture B
- `StructureElement->evidencias()` hasMany — retorna vacío en práctica, sin daño funcional
- `EvidenceObserver` — ya compatible (saltan el recálculo si `criterio_id === null`)
- Migración `043` en disco — necesaria para la cadena de rollback

---

## Merge con `development`

Se resolvieron 2 conflictos al mergear `origin/development`:

1. **`FilterEvidenceRequest.php`**: se fusionaron los filtros de `development`
   (`dimension_id`, `componente_id`, `estandar_id`) con los de la rama
   (`ciclo_acreditacion_id`, `modelo_estructura_id`)
2. **`EvidenceService.php`**: se fusionaron ambos conjuntos de variables y bloques de filtrado

---

## Separación de servicios — garantía de Arquitectura B

Resumen de aislamiento verificado al 2026-04-01:

| Servicio | Solo toca | Nunca toca |
|---|---|---|
| `EvidenceAssignmentService` | `EVIDENCIA_ASIGNACION` | `ELEMENTO_ASIGNACION` |
| `ElementAssignmentService` | `ELEMENTO_ASIGNACION` | `EVIDENCIA_ASIGNACION` |
| `TradicionalFileService` | `ARCHIVO(evidencia_id)` | `elemento_id` |
| `FlexibleFileService` | `ARCHIVO(elemento_id)` | `evidencia_id` |
| `TradicionalExtensionRequestService` | `SOLICITUD_AMPLIACION(evidencia_asignacion_id)` | `elemento_asignacion_id` |
| `FlexibleExtensionRequestService` | `SOLICITUD_AMPLIACION(elemento_asignacion_id)` | `evidencia_asignacion_id` |
| `TradicionalEvidenceFilterService` | `EVIDENCIA`, `EVIDENCIA_ASIGNACION` | tablas flexibles |
| `FilterElementService` | `ELEMENTO`, `ELEMENTO_ASIGNACION` | tablas tradicionales |

`EvidenceAssignmentService` lanza `InvalidArgumentException` si detecta que el proceso
es de modelo flexible, redirigiendo al usuario a `/api/elementos-asignaciones`.

---

## HU-008b — SOLID completo capa HTTP para archivos de elemento

El refactor SOLID del 2026-03-31 separó únicamente la capa de servicios
(`FlexibleFileService` / `TradicionalFileService`). La capa HTTP (controller, resource,
request) seguía siendo compartida en `FileController` / `FileResource` / `StoreFileRequest`.

Se completó SOLID creando la capa HTTP exclusiva para el modelo flexible:

### Nuevos archivos

| Archivo | Descripción |
|---|---|
| `app/Http/Requests/StoreElementFileRequest.php` | Valida solo `elemento_id` + `proceso_id`. Sin `evidencia_id`, sin Gate por evidencia. Usa `$this->user()` para authorize. |
| `app/Http/Resources/ElementFileResource.php` | Expone `elemento.descripcion`, `autor.nombre` + `autor.rol` (snapshot del rol del usuario al subir), `fecha_subida`. No expone `usuario_id` suelto ni campos de evidencia. |
| `app/Http/Controllers/ElementFileController.php` | Inyecta `FlexibleFileService` directamente (sin Factory). Eager-load `['elemento', 'user.roles', 'process']`. |

### Nuevos endpoints bajo `/api/elementos-archivos`

| Método | Ruta | Permiso | Propósito |
|---|---|---|---|
| `GET` | `/api/elementos-archivos?elemento_id={id}` | `archivos.view` | Listar archivos de un elemento con metadatos completos |
| `POST` | `/api/elementos-archivos` | `archivos.upload` | Subir archivo/enlace — registra autor+rol+timestamp automáticamente |
| `GET` | `/api/elementos-archivos/{archivo}` | `archivos.view` | Ver metadatos: descripción del elemento, autor, rol, sello de tiempo |
| `DELETE` | `/api/elementos-archivos/{archivo}` | `archivos.delete` | Eliminar archivo |
| `GET` | `/api/elementos-archivos/{archivo}/download` | `archivos.download` | Descargar archivo o redirigir a enlace |
| `POST` | `/api/elementos-archivos/{archivo}/make-public` | `archivos.make_public` | Generar enlace público con expiración |
| `POST` | `/api/elementos-archivos/{archivo}/revoke-public` | `archivos.make_public` | Revocar acceso público |

### HUs cubiertas por ElementFileResource (sin migración adicional)

| HU | Campo que la cubre | Fuente |
|---|---|---|
| Registro de autor automático (nombre + rol) | `autor.nombre` + `autor.rol` | `USUARIO` + Spatie `getRoleNames()` |
| Sello de tiempo | `fecha_subida` | Ya existía en `ARCHIVO` |
| Descripción del elemento | `elemento.descripcion` | Ya existía en `ELEMENTO` — ahora se expone en respuesta |
| Visualización inmediata de metadatos | Respuesta completa en el mismo `201` de la subida | `ElementFileResource` |

### Diferencias clave respecto a FileController/FileResource (modelo tradicional)

| Aspecto | Tradicional (`FileController`) | Flexible (`ElementFileController`) |
|---|---|---|
| Request | `StoreFileRequest` (acepta ambos modelos) | `StoreElementFileRequest` (solo `elemento_id`) |
| Resource | `FileResource` (muestra evidencia, usuario_id suelto) | `ElementFileResource` (muestra elemento+descripción, autor sin id suelto) |
| Service | Resuelve vía `FileStorageFactory` | Inyecta `FlexibleFileService` directo |
| Eager load | `['evidence', 'user', 'process']` | `['elemento', 'user.roles', 'process']` |
| Autorización | Gate por evidencia en `authorize()` | Solo `$this->user() !== null` |
| Punto | Endpoint | Estado |
|---|---|---|
| 1 | `GET /api/elementos-archivos?elemento_id=19` | ✅ Probado |
| 2 | `POST /api/elementos-archivos` (JSON, enlace URL) | ✅ Probado |
| 3 | `GET /api/elementos-archivos/{id}` | ✅ Probado |
| 4 | `GET /api/elementos-archivos/{id}/download` | ✅ Probado |
| 5 | `POST /api/elementos-archivos/{id}/make-public` | ✅ Probado |
| 6 | `POST /api/elementos-archivos/{id}/revoke-public` | ✅ Probado |
| 7 | `GET /api/p/{token}` sin Authorization | ✅ Probado |

### ✅ HU-007 — Asignaciones de elementos — Probada (2026-04-01)

```
POST   http://localhost:8000/api/elementos-asignaciones
       Body: { "elemento_id": 19, "usuario_id": 56, "proceso_id": 33, "fecha_limite": "2026-12-31" }
       → 201 con asignacion_id: 18

GET    http://localhost:8000/api/elementos-asignaciones?elemento_id=19   → 200 lista paginada
GET    http://localhost:8000/api/elementos-asignaciones/18               → 200 detalle
PATCH  http://localhost:8000/api/elementos-asignaciones/18
       Body: { "estado": "En Progreso" }                               → 200 actualizado
DELETE http://localhost:8000/api/elementos-asignaciones/{id}             → 204
```

### ✅ HU-013 — Retroalimentación de elementos — Probada (2026-04-01)

```
POST   http://localhost:8000/api/elementos-asignaciones/18/retroalimentacion
       Body (JSON):
       {
           "estado": "Validada",
           "comentario": "Documento correcto y completo"
       }
       → 200 con estado actualizado
```
- `estado` acepta: `"Observada"` o `"Validada"`
- Requiere que la asignación NO esté en estado `Pendiente` (hacer PATCH a `En Progreso` primero)
- Solo puede hacerlo Encargado/Admin/Superusuario (no el usuario asignado)

### ✅ HU-016 — Ampliación desde elemento (flexible) — Probada (2026-04-01)

```
POST   http://localhost:8000/api/elemento-solicitudes-ampliacion
       Body (JSON):
       {
           "elemento_asignacion_id": 18,
           "motivo": "Se requiere más tiempo para recopilar documentos",
           "fecha_sugerida": "2027-09-30"
       }
       → 201 solicitud_id: 32

GET    http://localhost:8000/api/elemento-solicitudes-ampliacion          → 200 lista paginada
GET    http://localhost:8000/api/elemento-solicitudes-ampliacion/pendientes → 200
GET    http://localhost:8000/api/elemento-solicitudes-ampliacion/mis-solicitudes → 200
GET    http://localhost:8000/api/elemento-solicitudes-ampliacion/32       → 200 detalle

POST   http://localhost:8000/api/elemento-solicitudes-ampliacion/32/aprobar
       Body: { "comentario_resolutor": "Aprobado" }                     → 200

POST   http://localhost:8000/api/elemento-solicitudes-ampliacion/33/rechazar
       Body: { "comentario_resolutor": "No justifica" }                 → 200
```
- Solo puede crear solicitud el usuario asignado al elemento
- `fecha_sugerida` debe ser mayor a la `fecha_limite` actual y no más de 30 días
- Fix aplicado: paginación usaba path incorrecto → resuelto con `withPath()` en `paginatedResponse()` ✅

---

## HU-016b — SOLID completo capa HTTP para ampliaciones de elemento

### Diagnóstico previo

`ExtensionRequestController` sólo inyectaba `TradicionalExtensionRequestService` y
`StoreExtensionRequestRequest` validaba `evidencia_asignacion_id`. El service flexible
(`FlexibleExtensionRequestService`) existía pero no tenía endpoints propios — mismo problema
que `ElementFileController` resolvió para HU-008b.

### Cambios realizados

| Archivo | Cambio |
|---|---|
| `app/Http/Requests/StoreFlexibleExtensionRequestRequest.php` | **Creado** — valida `elemento_asignacion_id` + `motivo` + `fecha_sugerida` |
| `app/Http/Controllers/FlexibleExtensionRequestController.php` | **Creado** — 7 endpoints, inyecta `FlexibleExtensionRequestService` directamente |
| `app/Services/FlexibleExtensionRequestService.php` | **Modificado** — override de `getAll()` con `whereNotNull('elemento_asignacion_id')` para aislar solicitudes flexible |
| `routes/api.php` | **Modificado** — import + 7 rutas bajo `/api/elemento-solicitudes-ampliacion` |

### Reutilizado sin cambios

- `ExtensionRequestResource` → ya manejaba ambos modelos con `when($this->elemento_asignacion_id !== null, …)`
- `ReviewExtensionRequestRequest` → modelo-agnóstico (aprobar/rechazar no depende del modelo)
- Eventos `ExtensionRequestCreated`, `ExtensionRequestApproved`, `ExtensionRequestRejected`

### Endpoints registrados

```
GET    /api/elemento-solicitudes-ampliacion              → index (encargados)
GET    /api/elemento-solicitudes-ampliacion/pendientes   → pending (encargados)
GET    /api/elemento-solicitudes-ampliacion/mis-solicitudes → mySolicitudes
GET    /api/elemento-solicitudes-ampliacion/{id}         → show
POST   /api/elemento-solicitudes-ampliacion              → store (throttle 10/min)
POST   /api/elemento-solicitudes-ampliacion/{id}/aprobar → approve
POST   /api/elemento-solicitudes-ampliacion/{id}/rechazar → reject
```

### Cómo probar

```http
POST   http://localhost:8000/api/elemento-solicitudes-ampliacion
Authorization: Bearer <token>
Content-Type: application/json

{
    "elemento_asignacion_id": 1,
    "motivo": "Se requiere más tiempo para recopilar la documentación necesaria",
    "fecha_sugerida": "2027-06-30"
}
```
- Respuesta esperada: `201` con la solicitud creada y relación `elemento_asignacion` cargada ✅
- La solicitud solo aparece en `/elemento-solicitudes-ampliacion`, no en `/solicitudes-ampliacion` (aislamiento SOLID) ✅

---

## Estrategia 3 — Restricción de nivel de asignación (`tipos_asignables`)

### Problema

Sin restricción, el sistema permitía asignar responsables y subir archivos a **cualquier
nodo del árbol ELEMENTO**, independientemente de su nivel jerárquico. El resultado era
que un nodo estructural (p.ej. `"dimension"`) podía recibir archivos igual que un nodo
hoja (p.ej. `"fuente"`), generando asignaciones dispersas y datos sin coherencia:

```
Dimensión "Gestión Académica"   ← cualquiera podía subir archivo ❌
  └── Pauta "Calidad Docente"   ← cualquiera podía subir archivo ❌
        └── Fuente "Evidencia1" ← el único que DEBERÍA tener archivo ✅
```

### Decisión arquitectónica

Se adoptó la **Estrategia 3 — tipos asignables por modelo**:

> El modelo de estructura define, una sola vez al crearse, qué tipos de nodo pueden
> recibir asignaciones y archivos. El árbol puede crecer libremente sin afectar la regla.

Se descartaron:
- **Estrategia 1 (topología/hoja)** — retroactiva: añadir un hijo a un nodo lo hace dejar de ser asignable sin intención
- **Estrategia 2 (flag por nodo)** — carga operativa alta: 80+ nodos por modelo, configuración manual en cada ciclo
- **Estrategia 4 (hardcodeado)** — contradice el modelo flexible; acoplado al vocabulario SINAES actual

### Cambios implementados (2026-04-01)

#### Migración `049_add_tipos_asignables_to_modelo_estructura_table.php`

- Agrega columna `tipos_asignables JSON NULL` a `MODELO_ESTRUCTURA`
- `NULL` = sin restricción — **retrocompatible** con modelos ya existentes

#### `app/Models/StructureModel.php`

- `'tipos_asignables'` agregado a `$fillable`
- Cast `'tipos_asignables' => 'array'` — Eloquent serializa/deserializa JSON automáticamente

#### `app/Models/StructureElement.php`

- Nueva relación `modeloEstructura()` — `belongsTo(StructureModel)` vía `modelo_estructura_id`
- Permite navegar desde un elemento hacia su modelo para leer `tipos_asignables`
- Import `use App\Models\StructureModel;` agregado

#### `app/Services/ElementAssignmentService.php` — guard en `assignElement()`

Después de confirmar que el elemento existe, se evalúa:

```php
$tiposAsignables = optional($element->modeloEstructura)->tipos_asignables;
if (!empty($tiposAsignables) && !in_array($element->tipo, $tiposAsignables)) {
    throw new \InvalidArgumentException(
        "El elemento de tipo '{$element->tipo}' no acepta asignaciones en este modelo. " .
        'Tipos permitidos: ' . implode(', ', $tiposAsignables) . '.'
    );
}
```

- Si `tipos_asignables` es `null` o `[]` → no hay restricción (retrocompat)
- Si el `$element->tipo` no está en la lista → lanza `InvalidArgumentException` con mensaje descriptivo

#### `app/Services/FlexibleFileService.php` — guard en `uploadFile()` y `saveLink()`

Nuevo método privado `validarTipoAsignable(int $elementoId)` que aplica la misma lógica:

```php
private function validarTipoAsignable(int $elementoId): void
{
    $elemento = StructureElement::find($elementoId);
    $tiposAsignables = optional($elemento->modeloEstructura)->tipos_asignables;
    if (!empty($tiposAsignables) && !in_array($elemento->tipo, $tiposAsignables)) {
        throw new \InvalidArgumentException(
            "El elemento de tipo '{$elemento->tipo}' no acepta archivos en este modelo. " .
            'Tipos permitidos: ' . implode(', ', $tiposAsignables) . '.'
        );
    }
}
```

Llamado al inicio de `uploadFile()` y `saveLink()` — antes de tocar Storage o BD.

### Flujo de uso

1. Admin crea modelo flexible con `tipos_asignables: ["fuente"]`
2. Árbol: `Dimension > Pauta > Fuente`
3. Intentar asignar/subir a `"Dimension"` o `"Pauta"` → **422** con mensaje claro
4. Solo `"Fuente"` pasa el guard → ✅

### Actualización tabla de aislamiento (Arquitectura B)

| Servicio | Guard adicional |
|---|---|
| `ElementAssignmentService::assignElement()` | Verifica `tipos_asignables` del modelo antes de crear asignación |
| `FlexibleFileService::uploadFile()` | Verifica `tipos_asignables` del modelo antes de guardar en Storage |
| `FlexibleFileService::saveLink()` | Verifica `tipos_asignables` del modelo antes de guardar enlace |

---

## Referencia rápida de endpoints — Modelo Flexible (para el Frontend)

> Base URL: `http://localhost:8000`  
> Todos los endpoints (excepto `GET /api/p/{token}`) requieren `Authorization: Bearer {token}`

### HU-007 — Asignaciones de elementos

| Método | Ruta | Body / Params | Respuesta |
|---|---|---|---|
| `POST` | `/api/elementos-asignaciones` | `{ "elemento_id": 19, "usuario_id": 56, "proceso_id": 33, "fecha_limite": "2026-12-31" }` | 201 asignación creada |
| `GET` | `/api/elementos-asignaciones` | `?elemento_id=19` (query param) | 200 lista paginada |
| `GET` | `/api/elementos-asignaciones/{id}` | — | 200 detalle |
| `PATCH` | `/api/elementos-asignaciones/{id}` | `{ "estado": "En Progreso" }` | 200 actualizado |
| `DELETE` | `/api/elementos-asignaciones/{id}` | — | 204 sin contenido |

### HU-008 — Archivos de elementos

| Método | Ruta | Body / Params | Respuesta |
|---|---|---|---|
| `POST` | `/api/elementos-archivos` | `{ "elemento_id": 19, "proceso_id": 33, "tipo": "enlace", "url": "https://...", "nombre_original": "Nombre" }` | 201 archivo creado |
| `GET` | `/api/elementos-archivos` | `?elemento_id=19` (query param) | 200 lista |
| `GET` | `/api/elementos-archivos/{id}` | — | 200 detalle |
| `GET` | `/api/elementos-archivos/{id}/download` | — | Redirect 302 (enlace) o descarga binaria (archivo) |
| `DELETE` | `/api/elementos-archivos/{id}` | — | 204 sin contenido |

### HU-013 — Retroalimentación

| Método | Ruta | Body | Respuesta |
|---|---|---|---|
| `POST` | `/api/elementos-asignaciones/{id}/retroalimentacion` | `{ "estado": "Observada" \| "Validada", "comentario": "texto" }` | 200 estado actualizado |

> Requisito: la asignación debe tener `estado != "Pendiente"`. Si está en Pendiente, hacer PATCH con `{ "estado": "En Progreso" }` primero.

### HU-016 — Solicitudes de ampliación (modelo flexible)

| Método | Ruta | Body | Respuesta |
|---|---|---|---|
| `POST` | `/api/elemento-solicitudes-ampliacion` | `{ "elemento_asignacion_id": 18, "motivo": "texto", "fecha_sugerida": "2027-09-30" }` | 201 solicitud creada |
| `GET` | `/api/elemento-solicitudes-ampliacion` | — | 200 lista paginada |
| `GET` | `/api/elemento-solicitudes-ampliacion/pendientes` | — | 200 solo pendientes |
| `GET` | `/api/elemento-solicitudes-ampliacion/mis-solicitudes` | — | 200 del usuario autenticado |
| `GET` | `/api/elemento-solicitudes-ampliacion/{id}` | — | 200 detalle |
| `POST` | `/api/elemento-solicitudes-ampliacion/{id}/aprobar` | `{ "comentario_resolutor": "texto" }` | 200 aprobada |
| `POST` | `/api/elemento-solicitudes-ampliacion/{id}/rechazar` | `{ "comentario_resolutor": "texto" }` | 200 rechazada |

### HU-023 — Enlace público sin autenticación

| Método | Ruta | Auth | Body | Respuesta |
|---|---|---|---|---|
| `POST` | `/api/elementos-archivos/{id}/make-public` | ✅ Bearer | `{ "expires_at": "2027-12-31" }` (opcional) | 200 con `url_publica` y `token_publico` |
| `POST` | `/api/elementos-archivos/{id}/revoke-public` | ✅ Bearer | — | 200 con `is_publico: false` |
| `GET` | `/api/p/{token}` | ❌ Sin auth | — | 302 redirect (enlace) o descarga (archivo) |

### HU-030 — Ciclos de acreditación

| Método | Ruta | Body | Respuesta |
|---|---|---|---|
| `GET` | `/api/estructura/ciclos-acreditacion` | — | 200 lista |
| `GET` | `/api/estructura/ciclos-acreditacion/{id}` | — | 200 detalle |
| `POST` | `/api/estructura/ciclos-acreditacion` | `{ "nombre": "...", "carrera_sede_id": 19, "modelo_estructura_id": 37, "fecha_inicio": "2026-01-01", "fecha_fin": "2026-12-31" }` | 201 ciclo creado |
| `PATCH` | `/api/estructura/ciclos-acreditacion/{id}` | campos a actualizar | 200 actualizado |
| `DELETE` | `/api/estructura/ciclos-acreditacion/{id}` | — | 204 sin contenido |
| `PATCH` | `/api/estructura/ciclos-acreditacion/{id}/reactivar` | — | 200 reactivado |

### HU-006 / Panel de Estructura — Elementos Flexible

| Método | Ruta | Params | Respuesta |
|---|---|---|---|
| `GET` | `/api/estructura/elementos` | `?modelo_estructura_id=37` | 200 lista paginada |
| `GET` | `/api/estructura/elementos/filter` | `?busqueda=AG&estado=pendiente&modelo_estructura_id=37` | 200 filtrado |
| `GET` | `/api/estructura/elementos/export/excel` | mismos params de filter | Descarga .xlsx |
| `GET` | `/api/estructura/elementos/export/pdf` | mismos params de filter | Descarga .pdf |
| `POST` | `/api/estructura/elementos` | `{ "nombre": "...", "tipo": "area", "nomenclatura": "AG-01", "modelo_estructura_id": 37 }` | 201 elemento creado |
| `PATCH` | `/api/estructura/elementos/{id}` | campos a actualizar | 200 actualizado |
| `DELETE` | `/api/estructura/elementos/{id}` | — | 204 sin contenido |
