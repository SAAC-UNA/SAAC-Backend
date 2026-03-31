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
| HU-008 | Subida de archivos con `elemento_id` (modelo flexible) | ✅ Committed |
| HU-018 | Notificaciones al asignar elementos (`NotifyElementAssignment`) | ✅ Committed |

### Archivos nuevos (HU-007/008/018)

| Archivo | Descripción |
|---|---|
| `app/Models/ElementAssignment.php` | Modelo ELEMENTO_ASIGNACION |
| `app/Events/ElementAssigned.php` | Evento disparado al asignar |
| `app/Listeners/NotifyElementAssignment.php` | Listener con cola, notifica al usuario |
| `app/Services/ElementAssignmentService.php` | Lógica de negocio de asignaciones |
| `app/Http/Requests/ElementAssignmentRequest.php` | Validación de requests |
| `app/Http/Controllers/ElementAssignmentController.php` | 7 endpoints REST |
| `database/migrations/044_add_estado_fecha_limite_to_elemento_table.php` | Estado y fecha límite en ELEMENTO |
| `database/migrations/045_create_elemento_asignacion_table.php` | Tabla ELEMENTO_ASIGNACION |
| `database/migrations/046_add_elemento_id_to_archivo_table.php` | `ARCHIVO.elemento_id` (Architecture B) |
| `database/migrations/047_remove_elemento_id_from_evidencia_table.php` | Limpieza Architecture A |

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
