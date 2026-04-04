# Solicitudes de Ampliación — Soporte Modelo Flexible

**Módulo:** Solicitudes de Ampliación (HU-016)  
**Rama:** `development`

---

## Contexto

La tabla `SOLICITUD_AMPLIACION` y el servicio `AbstractExtensionRequestService`
existían únicamente para el modelo tradicional (`evidencia_asignacion_id`). Con la
introducción del modelo flexible SINAES 2026+ (`ELEMENTO_ASIGNACION`), los
profesores pueden solicitar ampliación de plazo sobre **pautas** asignadas, no solo
sobre criterios de evidencia.

El backend ya contaba con `FlexibleExtensionRequestService` y
`FlexibleExtensionRequestController`, pero la tabla y el modelo Eloquent no estaban
preparados para persistir ni consultar solicitudes del modelo flexible.

---

## Cambios realizados

### 1. Migración: `add_elemento_asignacion_id_to_solicitud_ampliacion_table`

**Archivo:** `database/migrations/2026_04_04_164757_add_elemento_asignacion_id_to_solicitud_ampliacion_table.php`

Se agregó la columna `elemento_asignacion_id` a la tabla `SOLICITUD_AMPLIACION`:

```sql
ALTER TABLE SOLICITUD_AMPLIACION
  ADD COLUMN elemento_asignacion_id BIGINT UNSIGNED NULL
    AFTER evidencia_asignacion_id,
  ADD CONSTRAINT sa_elemento_asignacion_id_foreign
    FOREIGN KEY (elemento_asignacion_id)
    REFERENCES ELEMENTO_ASIGNACION(elemento_asignacion_id)
    ON DELETE RESTRICT,
  ADD INDEX idx_sa_elemento_asignacion_id (elemento_asignacion_id);
```

**Justificación:** La tabla original solo tenía `evidencia_asignacion_id`. Las
solicitudes del modelo flexible necesitan referenciar `elemento_asignacion_id`. Se
mantiene el campo existente como **nullable en ambos campos** de manera que una
solicitud tiene exactamente uno de los dos: `evidencia_asignacion_id` XOR
`elemento_asignacion_id`.

**Integridad:** El `FlexibleExtensionRequestService::createRequest()` ya garantizaba
la restricción XOR al crear siempre con `evidencia_asignacion_id = null` cuando
vincula a un `ElementAssignment`. La FK asegura integridad referencial a nivel de BD.

---

### 2. Modelo: `app/Models/ExtensionRequest.php`

**Cambios:**

#### a) `$fillable` — campo `elemento_asignacion_id`

```php
protected $fillable = [
    'evidencia_asignacion_id',
    'elemento_asignacion_id',   // ← nuevo
    'usuario_id',
    ...
];
```

Sin este campo en `$fillable`, la asignación masiva que hace
`FlexibleExtensionRequestService::createRequest()` ignoraría silenciosamente el
valor `elemento_asignacion_id`, dejando siempre `null` en la BD.

#### b) Relación `elementAssignment()`

```php
public function elementAssignment()
{
    return $this->belongsTo(ElementAssignment::class, 'elemento_asignacion_id', 'elemento_asignacion_id');
}
```

Necesaria para que `AbstractExtensionRequestService::WITH_BASE` pueda hacer eager
loading de la asignación de pauta. Sin esta relación definida, cualquier llamada a
`ExtensionRequest::with(['elementAssignment'])` lanzaba un `BadMethodCallException`
que causaba HTTP 500 en todos los endpoints de solicitudes.

---

### 3. Servicio: `app/Services/AbstractExtensionRequestService.php`

**Cambio en `WITH_BASE`:**

```php
// Antes:
protected const WITH_BASE = ['evidenceAssignment.evidence', 'elementAssignment', 'user', 'resolutor'];

// Después:
protected const WITH_BASE = ['evidenceAssignment.evidence', 'evidenceAssignment.process', 'elementAssignment.process', 'user', 'resolutor'];
```

**Justificación:**

- `evidenceAssignment.process` y `elementAssignment.process`: el frontend necesita
  `ciclo_acreditacion_id` del proceso para poder agrupar las solicitudes por ciclo
  de acreditación en el selector. Sin cargar el proceso, la respuesta no incluía
  `ciclo_acreditacion_id` y el selector siempre quedaría vacío.

- Se eliminó `elementAssignment` suelto y se reemplazó por
  `elementAssignment.process` (nested eager load), manteniendo la eficiencia en una
  sola consulta SQL.

---

### 4. Migraciones previas pendientes

Durante el trabajo se detectó que las migraciones **044 a 053** (todo el modelo
flexible: `ELEMENTO`, `ELEMENTO_ASIGNACION`, `SOLICITUD_AMPLIACION_ELEMENTO`, etc.)
**no habían sido ejecutadas** en el entorno de desarrollo.

Se corrieron con `php artisan migrate`, aplicando:

| Migración | Acción |
|---|---|
| `044` | Estados + fecha límite en `ELEMENTO` |
| `045` | Tabla `ELEMENTO_ASIGNACION` |
| `046` | `elemento_id` en `ARCHIVO` |
| `047` | Remoción de `elemento_id` de `EVIDENCIA` |
| `048` | Estados `Observada`/`Validada` en `ELEMENTO_ASIGNACION` |
| `049` | Tabla `ELEMENTO_APPROVALS` |
| `050–051` | Tablas compromiso de mejora (elementos) |
| `052` | Estado `cancelada` en `SOLICITUD_AMPLIACION` |
| `053` | Tabla `SOLICITUD_AMPLIACION_ELEMENTO` (desuso, ver nota) |

> **Nota sobre migración 053:** Se creó una tabla independiente
> `SOLICITUD_AMPLIACION_ELEMENTO`. Esta tabla **no es utilizada** por
> `FlexibleExtensionRequestService`, que reutiliza `SOLICITUD_AMPLIACION` con el
> campo XOR. La tabla puede considerarse un artefacto de diseño descartado; se
> mantiene para no revertir migraciones ya aplicadas.

---

## Arquitectura resultante

```
SOLICITUD_AMPLIACION
├── evidencia_asignacion_id (nullable) → EVIDENCIA_ASIGNACION  [modelo tradicional]
└── elemento_asignacion_id  (nullable) → ELEMENTO_ASIGNACION   [modelo flexible]

Restricción de negocio (XOR):
  Una solicitud tiene exactamente uno de los dos fks con valor ≠ NULL.
  Garantizado por FlexibleExtensionRequestService y ExtensionRequestService
  al momento de creación.
```

---

## Impacto en endpoints

Todos los endpoints de `/api/solicitudes-ampliacion` y
`/api/elemento-solicitudes-ampliacion` se ven afectados por los cambios en
`WITH_BASE`, pero de forma no regresiva: solo se agregan relaciones al eager load,
no se modifica la estructura de la respuesta para el modelo tradicional.

| Endpoint | Comportamiento |
|---|---|
| `GET /solicitudes-ampliacion` | Igual que antes + `evidencia_asignacion.process` en respuesta |
| `GET /elemento-solicitudes-ampliacion` | Ahora funciona (antes: 500 por columna faltante) |
| `POST /elemento-solicitudes-ampliacion/{id}/aprobar` | Sin cambios |
| `POST /elemento-solicitudes-ampliacion/{id}/rechazar` | Sin cambios |
