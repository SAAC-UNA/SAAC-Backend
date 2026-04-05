# HU-010 — Aprobación de Bloques en Modelo Flexible

> **Rama:** `hu10_notificaciones_estados_modelo_cristina`
> **Fecha de implementación:** 4 de abril de 2026

---

## 1. Descripción General

Permite que el **Evaluador** apruebe o rechace individualmente cada elemento hijo (componente, fuente, etc.) dentro de un bloque padre en el **modelo flexible (ELEMENTO)**, sin pasar por criterios ni evidencias.

El sistema gestiona automáticamente el estado del bloque padre según las decisiones tomadas sobre sus hijos.

---

## 2. Tabla Principal Afectada

| Tabla | Descripción |
|---|---|
| `APROBACION_ELEMENTO` | Registro de aprobación/rechazo de cada elemento en un proceso |

### Columnas relevantes en `APROBACION_ELEMENTO`

| Columna | Tipo | Descripción |
|---|---|---|
| `aprobacion_elemento_id` | INT PK | Identificador principal |
| `elemento_id` | INT FK | Elemento aprobado/rechazado |
| `proceso_id` | INT FK | Proceso de acreditación |
| `usuario_id` | INT FK | Evaluador que tomó la decisión |
| `estado` | ENUM | `pendiente`, `aprobado`, `rechazado`, `incompleto` |
| `comentario` | VARCHAR(100) | Motivo del rechazo (opcional en aprobación) |
| `nueva_fecha_limite` | DATE | Nueva fecha límite propuesta al rechazar (opcional) |

---

## 3. Migraciones Aplicadas

| # | Archivo | Descripción |
|---|---|---|
| 059 | `059_add_pending_status_to_element_approvals.php` | Agrega valor `pendiente` al ENUM `estado` |
| 060 | `060_add_incomplete_status_to_element_approvals.php` | Agrega valor `incompleto` al ENUM `estado` |
| 061 | `061_add_nueva_fecha_limite_to_element_approvals.php` | Agrega columna `nueva_fecha_limite DATE NULL` |

---

## 4. Lógica de Estados del Bloque Padre

Cada vez que se aprueba o rechaza un hijo individual, el servicio **recalcula automáticamente** el estado del bloque padre mediante `recalculateParentState()`.

| Condición sobre los hijos directos activos | Estado del padre |
|---|---|
| Todos aprobados | `aprobado` |
| Todos rechazados | `rechazado` |
| Al menos 1 rechazado (mezcla) | `incompleto` |
| Ninguna decisión tomada aún | `pendiente` |

---

## 5. Regla de Bloqueo

Cuando el bloque padre está en estado **`incompleto`**, un hijo que ya fue **`aprobado`** **no puede modificarse** (ni re-aprobarse ni rechazarse).

- Si se intenta, el servicio lanza `\LogicException` → respuesta `422 Unprocessable Entity`.

---

## 6. Observer: Reset automático `incompleto → pendiente`

**Clase:** `App\Observers\ElementAssignmentObserver`  
**Evento observado:** `updated` en `ElementAssignment`

**Flujo:**
1. El responsable corrige su trabajo y marca la `ELEMENTO_ASIGNACION` como `Completado`.
2. El Observer detecta que ese elemento tiene una `APROBACION_ELEMENTO` en estado `rechazado`.
3. Si el bloque padre tiene estado `incompleto`, lo resetea automáticamente a `pendiente`.
4. El evaluador ve el bloque como pendiente de nueva revisión.

**Propósito:** Notificar al evaluador que el responsable ya corrigió el trabajo rechazado y está listo para re-evaluar.

---

## 7. Archivos Modificados

| Archivo | Tipo de cambio |
|---|---|
| `app/Models/ElementApproval.php` | Agregado `nueva_fecha_limite` a `$fillable` y `$casts` |
| `app/Http/Requests/ElementApprovalRequest.php` | Agregada regla `nueva_fecha_limite` |
| `app/Services/ElementApprovalService.php` | Nuevos métodos `approveIndividualChild`, `rejectIndividualChild`, `recalculateParentState` |
| `app/Http/Controllers/ElementApprovalController.php` | Nuevos métodos `approveIndividualChild`, `rejectIndividualChild` (thin controller) |
| `app/Observers/ElementAssignmentObserver.php` | Nuevo método `updated()` para reset `incompleto → pendiente` |
| `routes/api.php` | 2 rutas nuevas para aprobación/rechazo individual de hijo |
| `bootstrap/app.php` | Handlers de excepción `LogicException → 422` e `InvalidArgumentException → 404` |

---

## 8. Endpoints

### 8.1 Endpoints Preexistentes (Modelo Flexible — Cascada)

Estos endpoints aprueban/rechazan un elemento **y todos sus descendientes activos** en cascada.

---

#### `GET /api/aprobaciones-elementos`
Lista todas las aprobaciones de elementos.

**Permiso requerido:** `viewAny ElementApproval`  
**Query params opcionales:**

| Param | Tipo | Descripción |
|---|---|---|
| `estado` | string | Filtrar por estado: `pendiente`, `aprobado`, `rechazado`, `incompleto` |

**Respuesta 200:**
```json
{
    "success": true,
    "data": [
        {
            "aprobacion_elemento_id": 1,
            "elemento_id": 19,
            "proceso_id": 33,
            "usuario_id": 56,
            "estado": "incompleto",
            "comentario": null,
            "nueva_fecha_limite": null
        }
    ]
}
```

---

#### `GET /api/aprobaciones-elementos/{aprobacionId}`
Obtiene una aprobación específica, incluyendo las aprobaciones de sus hijos.

**Permiso requerido:** `view ElementApproval`

**Respuesta 200:**
```json
{
    "success": true,
    "data": {
        "aprobacion_elemento_id": 5,
        "elemento_id": 19,
        "estado": "incompleto",
        "hijos": [...]
    }
}
```

---

#### `POST /api/elementos/{elementoId}/aprobar`
Aprueba un elemento y en **cascada** todos sus hijos activos.

**Permiso requerido:** `approve ElementApproval`  
**Throttle:** 10 req/min

**Body:**
```json
{
    "proceso_id": 33,
    "comentario": "Todo correcto"
}
```

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `proceso_id` | integer | ✅ | ID del proceso activo |
| `comentario` | string (max 100) | ❌ | Observación del evaluador |

**Respuesta 201:**
```json
{
    "success": true,
    "message": "Elemento aprobado exitosamente.",
    "data": {
        "raiz": { "elemento_id": 19, "estado": "aprobado" },
        "cascada": [{ "elemento_id": 20, "estado": "aprobado" }, ...]
    }
}
```

---

#### `POST /api/elementos/{elementoId}/rechazar`
Rechaza un elemento y en **cascada** todos sus hijos activos. Resetea las `ELEMENTO_ASIGNACION` a `Pendiente`.

**Permiso requerido:** `reject ElementApproval`  
**Throttle:** 10 req/min

**Body:**
```json
{
    "proceso_id": 33,
    "comentario": "Faltan evidencias de respaldo",
    "fecha_limite": "2026-06-01"
}
```

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `proceso_id` | integer | ✅ | ID del proceso activo |
| `comentario` | string (max 100) | ❌ | Motivo del rechazo |
| `fecha_limite` | date (after:now) | ❌ | Nueva fecha límite para correcciones |

**Respuesta 201:**
```json
{
    "success": true,
    "message": "Elemento rechazado exitosamente.",
    "data": {
        "raiz": { "elemento_id": 19, "estado": "rechazado" },
        "cascada": [{ "elemento_id": 20, "estado": "rechazado" }, ...]
    }
}
```

---

### 8.2 Endpoints Nuevos (HU-010 — Aprobación Individual de Hijo)

Permiten evaluar **un hijo a la vez** dentro de un bloque padre, calculando automáticamente el estado del padre.

---

#### `POST /api/elementos/{padreId}/hijos/{hijoId}/aprobar`
Aprueba un elemento hijo individualmente dentro del bloque padre.

**Permiso requerido:** `approve ElementApproval`  
**Throttle:** 10 req/min

**Parámetros de ruta:**

| Param | Descripción |
|---|---|
| `padreId` | `elemento_id` del bloque padre |
| `hijoId` | `elemento_id` del hijo a aprobar |

**Body:**
```json
{
    "proceso_id": 33
}
```

| Campo | Tipo | Requerido |
|---|---|---|
| `proceso_id` | integer | ✅ |

**Respuesta 201:**
```json
{
    "success": true,
    "message": "Elemento hijo aprobado individualmente.",
    "data": {
        "padre_approval": {
            "elemento_id": 19,
            "estado": "aprobado"
        },
        "hijo_approval": {
            "elemento_id": 21,
            "estado": "aprobado"
        }
    }
}
```

**Errores posibles:**

| Código | Causa |
|---|---|
| `404` | El hijo no existe, no pertenece al padre o está inactivo |
| `422` | El hijo ya fue aprobado y el bloque padre está `incompleto` (regla de bloqueo) |
| `403` | Sin permiso `approve ElementApproval` |
| `422` | Falla de validación del `FormRequest` |

---

#### `POST /api/elementos/{padreId}/hijos/{hijoId}/rechazar`
Rechaza un elemento hijo individualmente dentro del bloque padre. Guarda comentario y nueva fecha límite en `APROBACION_ELEMENTO`, y resetea la `ELEMENTO_ASIGNACION` del hijo a `Pendiente`.

**Permiso requerido:** `reject ElementApproval`  
**Throttle:** 10 req/min

**Parámetros de ruta:**

| Param | Descripción |
|---|---|
| `padreId` | `elemento_id` del bloque padre |
| `hijoId` | `elemento_id` del hijo a rechazar |

**Body:**
```json
{
    "proceso_id": 33,
    "comentario": "Falta completar la sección 2",
    "nueva_fecha_limite": "2026-06-15"
}
```

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `proceso_id` | integer | ✅ | ID del proceso activo |
| `comentario` | string (max 100) | ❌ | Motivo del rechazo |
| `nueva_fecha_limite` | date (after:now) | ❌ | Nueva fecha límite para la corrección |

**Respuesta 201:**
```json
{
    "success": true,
    "message": "Elemento hijo rechazado individualmente.",
    "data": {
        "padre_approval": {
            "elemento_id": 19,
            "estado": "incompleto"
        },
        "hijo_approval": {
            "elemento_id": 20,
            "estado": "rechazado",
            "comentario": "Falta completar la sección 2",
            "nueva_fecha_limite": "2026-06-15"
        }
    }
}
```

**Errores posibles:**

| Código | Causa |
|---|---|
| `404` | El hijo no existe, no pertenece al padre o está inactivo |
| `422` | El hijo ya fue aprobado y el bloque padre está `incompleto` (regla de bloqueo) |
| `403` | Sin permiso `reject ElementApproval` |
| `422` | Falla de validación del `FormRequest` |

---

## 9. Efectos Secundarios al Rechazar un Hijo

Cuando se rechaza un hijo con `POST .../hijos/{hijoId}/rechazar`:

1. Se crea/actualiza `APROBACION_ELEMENTO` del hijo con `estado = rechazado`.
2. Se actualizan las `ELEMENTO_ASIGNACION` del hijo en ese proceso:
   - `estado → Pendiente`
   - `comentario → {comentario}` (si se envió)
   - `fecha_limite → {nueva_fecha_limite}` (si se envió)
3. Se recalcula el estado del bloque padre.

---

## 10. Flujo Completo de Uso

```
1. Evaluador: POST /api/elementos/19/hijos/21/aprobar     → hijo 21 = aprobado, padre 19 = pendiente (1 de 2 hijos)
2. Evaluador: POST /api/elementos/19/hijos/20/rechazar    → hijo 20 = rechazado, padre 19 = incompleto
3. Responsable: PUT /api/elementos-asignaciones/20        → estado: "Completado"
   └── Observer detecta: hijo 20 rechazado + padre incompleto
   └── Padre 19 reseteado → pendiente  (evaluador sabe que hay correcciones listas)
4. Evaluador: POST /api/elementos/19/hijos/20/aprobar     → hijo 20 = aprobado, padre 19 = aprobado (todos aprobados)
```

---

## 11. Notas Técnicas

- **Thin controller:** Los métodos `approveIndividualChild` y `rejectIndividualChild` en el controller tienen 3-4 líneas; toda la lógica está en `ElementApprovalService`.
- **Excepciones globales:** En Laravel 12, los handlers de excepción deben registrarse en `bootstrap/app.php` (`withExceptions`), no en `App\Exceptions\Handler::register()`. Se agregaron `\LogicException → 422` e `\InvalidArgumentException → 404`.
- **Transacciones:** Las validaciones de negocio (`LogicException`, `InvalidArgumentException`) se ejecutan **fuera** del `DB::transaction()` para que el `ExceptionHandler` las capture directamente.
- **Audit log:** Cada aprobación/rechazo individual queda registrada en el log de auditoría con la nomenclatura del hijo y del padre.
