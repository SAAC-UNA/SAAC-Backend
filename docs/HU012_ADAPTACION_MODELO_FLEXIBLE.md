# HU-012 — Adaptación al Modelo Flexible de Estructura

## Contexto

El sistema SAAC maneja dos modelos de acreditación SINAES en paralelo:

- **Modelo Tradicional**: jerarquía fija `DIMENSION → COMPONENTE → CRITERIO → EVIDENCIA`
- **Modelo Flexible**: árbol libre `MODELO_ESTRUCTURA → ELEMENTO (self-ref via padre_id)`

El modelo flexible existe porque SINAES cambia los tipos y nombres de evaluación cada cierto número de años. Con la estructura rígida anterior, cada cambio requería migraciones y código nuevo. Con el modelo flexible, solo se configuran nuevos `ELEMENTO` sin tocar código.

El punto de conexión entre ambos modelos es `CICLO_ACREDITACION.modelo_estructura_id`.

---

## Problema que se resuelve

La tabla `EVIDENCIA` solo tenía `criterio_id NOT NULL` — era imposible asociar una evidencia al modelo flexible. El filtrado de HU-012 tampoco permitía buscar por `elemento_id`.

---

## Cambios realizados

### 1. Migración `043_add_elemento_id_to_evidencia_table.php`

- Agrega `elemento_id BIGINT UNSIGNED NULL` con FK → `ELEMENTO.elemento_id` (`onDelete set null`)
- Cambia `criterio_id` a nullable para permitir evidencias flexibles

**Regla de negocio (excluyente):**
```
Evidencia tradicional:  criterio_id = X,    elemento_id = null
Evidencia flexible:     criterio_id = null,  elemento_id = Y
```

### 2. Modelo `Evidence.php`

- Agrega `elemento_id` al `$fillable`
- Nueva relación `elemento()` → `belongsTo(StructureElement::class, 'elemento_id', 'elemento_id')`

### 3. `FilterEvidenceRequest.php`

- Nueva regla `elemento_id`: nullable, integer, exists en `ELEMENTO`
- Mensaje de error en español
- Atributo legible `'elemento_id' => 'elemento'`

### 4. `EvidenceService.php`

- `WITH_BASE` actualizado: agrega `'elemento'` al eager loading (retorna `null` en evidencias tradicionales sin romper nada)
- `filterEvidences()`: extrae `$elementoId` de los filtros y aplica `WHERE elemento_id = ?`

### 5. `EvidenceResource.php`

- Expone `elemento_id` como campo plano
- Nuevo bloque `elemento` con `$this->when(...)`: solo aparece en la respuesta si la relación está cargada Y la evidencia es flexible

### 6. `RolesAndPermissionsSeeder.php`

- **Antes**: solo asignaba `admin.super` al Superusuario — causaba 403 en rutas con `permission:modelos.view`, `permission:elemento.create`, etc.
- **Después**: lee `config/permissions.modules` y genera todos los permisos automáticamente. Superusuario recibe `syncPermissions` con todos ellos, cumpliendo `all_permissions = true` del config.

---

## Pruebas realizadas en Postman

| Endpoint | Resultado esperado | ✅ |
|----------|-------------------|-----|
| `GET /filter` (sin params) | 13 evidencias tradicionales, `elemento_id: null` | ✅ |
| `GET /filter?criterio_id=1` | 2 evidencias del criterio 1.1.1 | ✅ |
| `GET /filter?elemento_id=999` | 422 — elemento no existe | ✅ |
| `GET /filter?elemento_id=1` | `data: [], total: 0` sin error | ✅ |

---

## Escritura flexible — Implementación (sprint 2)

Los cambios anteriores cubrieron únicamente la **lectura/filtro** del modelo flexible.
Los siguientes cambios completan la **escritura** (crear evidencias y elementos flexibles).

---

### Gap 1 — `StructureElement.php` — Relación inversa `evidencias()`

**Problema:** `Evidence.php` ya declaraba `belongsTo(StructureElement::class)`, pero la relación
inversa `hasMany` en `StructureElement` nunca se creó. Sin ella era imposible escribir
`$elemento->evidencias()->create(...)` ni hacer eager-load.

**Cambio:** Se agregó la relación `evidencias()`:
```php
public function evidencias(): HasMany
{
    return $this->hasMany(Evidence::class, 'elemento_id', 'elemento_id');
}
```

---

### Gap 2 — `EvidenceRequest.php` — Validación XOR `criterio_id / elemento_id`

**Problema:** El campo `criterio_id` era `required` para toda evidencia. Enviar una evidencia
flexible con `elemento_id` fallaba en validación con 422.

**Cambios:**
- `criterio_id` cambia de `'required'` → `'nullable'` en POST
- Se agrega `elemento_id`: `nullable|integer|exists:ELEMENTO,elemento_id`
- Se agrega validación de unicidad de `nomenclatura` también dentro del scope de elemento
- `withValidator()` aplica regla XOR en CREATE: exactamente uno de los dos debe venir no-nulo;
  si vienen ambos o ninguno → 422 con mensaje descriptivo
- En UPDATE: si ambos campos vienen simultáneamente → 422 (no se puede cambiar el tipo de anchor)

**Regla de negocio resultante:**
```
POST sin criterio_id ni elemento_id  → 422 "Debe especificar criterio_id o elemento_id"
POST con ambos                       → 422 "No puede usar criterio_id y elemento_id juntos"
POST con solo criterio_id            → evidencia tradicional ✅
POST con solo elemento_id            → evidencia flexible   ✅
```

---

### Gap 3 — `EvidenceService.php` — `create()` y `update()` persisten `elemento_id`

**Problema:** `create()` solo guardaba `criterio_id`; `elemento_id` llegaba en `$data` pero
se descartaba silenciosamente. `update()` usaba `??` (nullish-coalescing), que no distinguía
entre "campo ausente" y "campo explícitamente enviado como null" — imposible cambiar anchor.

**Cambios:**
- `create()`: guarda `'criterio_id' => $data['criterio_id'] ?? null` y
  `'elemento_id' => $data['elemento_id'] ?? null`
- `update()`: usa `array_key_exists()` para detectar si el campo viene explícitamente nulo

---

### Gap 4 — `StructureElementRequest.php` — Campo opcional `evidencias[]`

**Problema:** No existía ninguna validación para recibir evidencias embebidas al crear un elemento
flexible (Opción 2 del diseño: crear ELEMENTO + EVIDENCIAs en una sola petición).

**Cambios:** Se agrega al bloque `rules()`:
```php
'evidencias'                => $isUpdate ? 'prohibited' : 'sometimes|nullable|array',
'evidencias.*.nomenclatura' => 'required_with:evidencias|string|max:20|regex:...',
'evidencias.*.descripcion'  => 'nullable|string|max:500',
```

- `evidencias` en PUT/PATCH es `prohibited`: las evidencias individuales se manejan vía
  `POST /api/estructura/evidencias`, no a través de la actualización del elemento.

---

### Gap 5 — `StructureElementService.php` — Transacción atómica + loop de evidencias

**Problema:** `create()` creaba el ELEMENTO sin transacción y sin soporte para evidencias.
Si el INSERT del ELEMENTO fallaba a mitad, no había rollback posible.

**Cambios:**
- Imports: `use App\Models\Evidence;` y `use Illuminate\Support\Facades\DB;`
- `create()` envuelto en `DB::transaction()`:
  1. INSERT en ELEMENTO
  2. Loop sobre `$data['evidencias'] ?? []` — INSERT en EVIDENCIA por cada item
  3. `clearCache()` solo si todo fue exitoso
  4. Retorna `$elemento->load('evidencias')` para que el controller devuelva el estado real
- `findById()` actualizado: `StructureElement::with('evidencias')->find($id)` — GET /elementos/{id}
  ahora incluye las evidencias del nodo (array vacío en el modelo tradicional, correcto)

---

### Gap 6 — `StructureElementController.php` — Response de `store()` incluye `evidencias`

**Problema:** El response del `store()` devolvía `$item` crudo (sin evidencias). Si el cliente
enviaba `evidencias[]`, estas se creaban en BD pero no aparecían en el 201 — el cliente
necesitaba un segundo GET para confirmarlas.

**Cambio:** Se agrega `'evidencias' => $item->evidencias` al response JSON del 201.
Como el service ya retorna `$item->load('evidencias')`, no hay query extra — son los mismos
datos ya en memoria.

---

## Resumen de reglas de negocio (modelo flexible)

| Situación | Resultado |
|-----------|-----------|
| POST evidencia sin `criterio_id` ni `elemento_id` | 422 — obligatorio uno |
| POST evidencia con `criterio_id` + `elemento_id` | 422 — excluyentes |
| POST evidencia con solo `criterio_id` | Evidencia tradicional ✅ |
| POST evidencia con solo `elemento_id` | Evidencia flexible ✅ |
| POST elemento sin `evidencias[]` | Crea solo el ELEMENTO ✅ |
| POST elemento con `evidencias[]` | Crea ELEMENTO + EVIDENCIAs en transacción ✅ |
| PUT/PATCH elemento con `evidencias[]` | 422 — campo prohibido en update |
| GET elemento por ID | Devuelve ELEMENTO + evidencias cargadas ✅ |

---

## Filtros contextuales por ciclo y modelo — Implementación (sprint 2, parte 2)

Los cambios de sprint 2 parte 1 añadieron la **escritura** flexible. Esta parte añade
**filtros contextuales** en el endpoint `GET /api/evidencias/filter` para que el front-end
pueda acotar resultados a un ciclo de acreditación o a un modelo de estructura específico.

**Problema:** Sin estos filtros, `filterEvidences()` devolvía todas las evidencias del sistema
sin importar a qué ciclo o modelo pertenecían, mezclando datos de ciclos distintos.

---

### Gap 7 — `FilterEvidenceRequest.php` — Nuevas reglas de validación

**Archivo:** `app/Http/Requests/FilterEvidenceRequest.php`

**Cambio:** Se agregaron dos parámetros opcionales al método `rules()`:

```php
'ciclo_acreditacion_id' => ['nullable', 'integer', 'exists:CICLO_ACREDITACION,ciclo_acreditacion_id'],
'modelo_estructura_id'  => ['nullable', 'integer', 'exists:MODELO_ESTRUCTURA,modelo_estructura_id'],
```

Y sus mensajes en español en `messages()`:

```php
'ciclo_acreditacion_id.exists'  => 'El ciclo de acreditación especificado no existe.',
'modelo_estructura_id.exists'   => 'El modelo de estructura especificado no existe.',
```

**Regla de negocio resultante:**

| Parámetro enviado | Comportamiento |
|---|---|
| Sin `ciclo_acreditacion_id` | Sin restricción por ciclo (devuelve todos) |
| `ciclo_acreditacion_id` válido | Solo evidencias asignadas a procesos de ese ciclo |
| `ciclo_acreditacion_id` inexistente | 422 — ciclo no existe |
| Sin `modelo_estructura_id` | Sin restricción por modelo |
| `modelo_estructura_id` válido | Solo evidencias del modelo de estructura indicado |
| `modelo_estructura_id` inexistente | 422 — modelo no existe |

---

### Gap 8 — `EvidenceService.php` — Filtros en `filterEvidences()`

**Archivo:** `app/Services/EvidenceService.php`

**Cambio:** Se extrae el valor de cada nuevo filtro y se aplica vía `whereHas` navegando
la cadena `EVIDENCIA → EVIDENCIA_ASIGNACION → PROCESO → CICLO_ACREDITACION / MODELO_ESTRUCTURA`:

```php
$cicloId          = $filters['ciclo_acreditacion_id'] ?? null;
$modeloEstructuraId = $filters['modelo_estructura_id'] ?? null;

// Filtro por ciclo de acreditación
if ($cicloId) {
    $query->whereHas('assignments.process', fn ($q) =>
        $q->where('ciclo_acreditacion_id', $cicloId)
    );
}

// Filtro por modelo de estructura
if ($modeloEstructuraId) {
    $query->whereHas('assignments.process', fn ($q) =>
        $q->where('modelo_estructura_id', $modeloEstructuraId)
    );
}
```

**Cadena de relaciones Eloquent usada:**

```
Evidence
  → assignments()   [hasMany EvidenciaAsignacion, fk: evidencia_id]
    → process()     [belongsTo Proceso]
      → ciclo_acreditacion_id  (columna directa en PROCESO)
      → modelo_estructura_id   (columna directa en PROCESO)
```

---

### Pruebas adicionales (sprint 2, parte 2)

| Endpoint | Resultado esperado |
|---|---|
| `GET /filter?ciclo_acreditacion_id=1` | Solo evidencias de procesos del ciclo 1 ✅ |
| `GET /filter?ciclo_acreditacion_id=999` | 422 — ciclo no existe ✅ |
| `GET /filter?modelo_estructura_id=2` | Solo evidencias ligadas al modelo 2 ✅ |
| `GET /filter?modelo_estructura_id=999` | 422 — modelo no existe ✅ |
| `GET /filter?ciclo_acreditacion_id=1&modelo_estructura_id=2` | Intersección: ciclo 1 AND modelo 2 ✅ |
| `GET /filter` (sin params nuevos) | Mismo comportamiento anterior — sin regresión ✅ |

