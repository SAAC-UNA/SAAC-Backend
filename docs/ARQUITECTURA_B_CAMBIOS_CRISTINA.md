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
| HU-007 | CRUD de asignaciones de elementos (`ELEMENTO_ASIGNACION`) | ✅ Committed |
| HU-008 | Subida de archivos con `elemento_id` (modelo flexible) + fix FileResource | ✅ Committed |
| HU-012 | Filtrado avanzado y exportación de elementos (Excel/PDF) | ✅ Committed |
| HU-013 | Retroalimentación equivalente en `ELEMENTO_ASIGNACION` (`Observada`/`Validada`) | ✅ Committed |
| HU-016 | Solicitud de ampliación — refactorizado a patrón Strategy (tradicional + flexible) | ✅ Committed |
| HU-018 | Notificaciones al asignar elementos (`NotifyElementAssignment`) | ✅ Committed |
| HU-023 | Enlace público para archivos sin autenticación (SINAES) | ✅ Committed |
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

## Estado de pruebas Postman al 2026-04-01

### ✅ HU-008 + HU-023 — Completadas (10/10)

| Punto | Endpoint | Estado |
|---|---|---|
| 1 | `GET /archivos?elemento_id=1` | ✅ |
| 2 | `POST /archivos` (form-data, archivo físico, flexible) | ✅ |
| 3 | `POST /archivos` (JSON, enlace URL) | ✅ |
| 4 | `GET /archivos/{id}` | ✅ |
| 5 | `POST /archivos/{id}/make-public` | ✅ |
| 6 | `GET /p/{token}` sin Authorization | ✅ |
| 7 | `POST /archivos/bulk-make-public` | ✅ |
| 8 | `POST /archivos/{id}/revoke-public` | ✅ |
| 9 | `GET /archivos/{id}/download` | ✅ |
| 10 | `DELETE /archivos/{id}` | ✅ |

### ⏳ Pendiente probar — HU-007 Asignaciones de elementos

```
POST   http://localhost:8000/api/elementos-asignaciones
       Body: { "elemento_id": 1, "usuario_id": 1, "proceso_id": 1, "fecha_limite": "2027-06-30" }

GET    http://localhost:8000/api/elementos-asignaciones?elemento_id=1
GET    http://localhost:8000/api/elementos-asignaciones/{id}
PATCH  http://localhost:8000/api/elementos-asignaciones/{id}
       Body: { "fecha_limite": "2027-09-30" }
DELETE http://localhost:8000/api/elementos-asignaciones/{id}
```

### ⏳ Pendiente probar — HU-013 Retroalimentación elementos

```
POST   http://localhost:8000/api/elementos-asignaciones/{id}/retroalimentacion
       Body (JSON):
       {
           "estado": "Observada",
           "comentario": "Falta firma del documento"
       }

       // O para validar:
       {
           "estado": "Validada",
           "comentario": "Documento correcto y completo"
       }
```
- `estado` acepta: `"Observada"` o `"Validada"`
- Solo puede hacerlo el coordinador/admin (no el mismo usuario asignado)
- Respuesta esperada: `200` con el estado actualizado y comentario guardado

### ✅ HU-016 — Ampliación desde elemento (flexible) — Probada

```
POST   http://localhost:8000/api/elementos-asignaciones/{id}/solicitud-ampliacion
       Body (JSON):
       {
           "fecha_sugerida": "2027-09-30",
           "justificacion": "Se requiere más tiempo para recopilar documentos"
       }
```
- Solo puede hacerlo el usuario asignado a ese elemento
- `fecha_sugerida` debe ser mayor a la `fecha_limite` actual y no más de 30 días
- Respuesta: `201` con la solicitud creada ✅
