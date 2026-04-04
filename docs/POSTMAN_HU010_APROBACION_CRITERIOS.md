# Postman — HU-010 Aprobación de Criterios

> **Base URL:** `http://localhost:8000/api`  
> ⚠️ Las rutas de aprobación **NO tienen prefijo `/v1/`**  
> **Header en todas las requests:** `Authorization: Bearer {{token}}`  
> **IDs reales disponibles en la BD:**

| Criterio ID | Nomenclatura | Evidencias | Estado bloque |
|---|---|---|---|
| 1 | 1.1.1 | 1, 2 | `aprobado` |
| 2 | 1.1.2 | 3, 4 | `aprobado` |
| **3** | **1.2.1** | **5, 6** | **usar este para pruebas** |
| 4 | (otro) | 7, 8, 9 | `rechazado` |

> **proceso_id = 1** en todos los casos.

---

## PARTE 1 — Aprobaciones individuales de evidencias

### 1.1 — VER estado individual de evidencias de un criterio

```
GET /criterios/3/evidencias/aprobaciones?proceso_id=1
```
No tiene body. Devuelve cada evidencia del criterio con su estado de aprobación (`aprobado`, `rechazado`, o `sin_decision`).

---

### 1.2 — APROBAR evidencia individual (evidencia 5)

```
POST /criterios/3/evidencias/5/aprobar
Content-Type: application/json
```
```json
{
    "proceso_id": 1
}
```
**Resultado esperado:** `APROBACION_EVIDENCIA` evidencia 5 = `aprobado`. Bloque queda en `pendiente` (todavía no se tocó la evidencia 6).

---

### 1.3 — RECHAZAR evidencia individual (evidencia 6) → bloque pasa a `incompleto`

```
POST /criterios/3/evidencias/6/rechazar
Content-Type: application/json
```
```json
{
    "proceso_id": 1,
    "comentario": "La evidencia no tiene los archivos requeridos. Favor corregir.",
    "nueva_fecha_limite": "2026-04-20"
}
```
**Resultado esperado:**
- `APROBACION_EVIDENCIA` evidencia 6 = `rechazado`, con `comentario` y `nueva_fecha_limite = 2026-04-20`
- `EVIDENCIA_ASIGNACION` evidencia 6 → `Pendiente` con `fecha_limite = 2026-04-20`
- `APROBACION_CRITERIO` bloque = `incompleto`

> **Nota:** `nueva_fecha_limite` se guarda tanto en `APROBACION_EVIDENCIA` (registro de la decisión del RF) como en `EVIDENCIA_ASIGNACION` (lo que ve el Profesor en su tarea).

---

### 1.4 — RECHAZAR sin comentario ni fecha (mínimo)

```
POST /criterios/3/evidencias/6/rechazar
Content-Type: application/json
```
```json
{
    "proceso_id": 1
}
```
**Resultado esperado:** igual que 1.3 pero sin comentario ni nueva fecha.

---

### 1.5 — Intentar tocar evidencia 5 bloqueada → debe dar ERROR

> Primero asegúrate de haber ejecutado 1.2 (evidencia 5 aprobada) y 1.3 (bloque incompleto).

```
POST /criterios/3/evidencias/5/aprobar
Content-Type: application/json
```
```json
{
    "proceso_id": 1
}
```
**Resultado esperado:** Error `400` — "Esta evidencia ya fue aprobada y está bloqueada mientras el bloque tenga estado incompleto."

---

### 1.6 — APROBAR la evidencia rechazada (evidencia 6) → bloque pasa a `aprobado`

> Una vez que el Profesor corrigió y el bloque volvió a `pendiente` (ver Parte 3 más abajo).

```
POST /criterios/3/evidencias/6/aprobar
Content-Type: application/json
```
```json
{
    "proceso_id": 1
}
```
**Resultado esperado:** Todas las evidencias `aprobado` → bloque pasa automáticamente a `aprobado`.

---

## PARTE 2 — Aprobación/rechazo de bloque completo

### 2.1 — APROBAR bloque completo (todas las evidencias de un criterio de golpe)

```
POST /criterios/3/aprobar
Content-Type: application/json
```
```json
{
    "proceso_id": 1,
    "comentario": "Criterio revisado y aprobado completamente."
}
```
**Resultado esperado:** Bloque = `aprobado`, todas las evidencias del criterio = `aprobado`.

---

### 2.2 — APROBAR bloque sin comentario (mínimo)

```
POST /criterios/3/aprobar
Content-Type: application/json
```
```json
{
    "proceso_id": 1
}
```

---

### 2.3 — RECHAZAR bloque completo (sin nueva fecha)

```
POST /criterios/3/rechazar
Content-Type: application/json
```
```json
{
    "proceso_id": 1,
    "comentario": "El criterio no cumple con los estándares requeridos."
}
```
**Resultado esperado:**
- `APROBACION_CRITERIO`: `estado = rechazado`, `nueva_fecha_limite = null`
- `APROBACION_EVIDENCIA` de cada evidencia: `estado = rechazado`, `nueva_fecha_limite = null`
- `EVIDENCIA_ASIGNACION` de cada evidencia → `Pendiente` (fecha límite sin cambios)

---

### 2.4 — RECHAZAR bloque completo CON nueva fecha límite

```
POST /criterios/3/rechazar
Content-Type: application/json
```
```json
{
    "proceso_id": 1,
    "comentario": "El criterio no cumple los estándares. Corregir antes de la nueva fecha.",
    "nueva_fecha_limite": "2026-05-15"
}
```
**Resultado esperado:**
- `APROBACION_CRITERIO`: `estado = rechazado`, `comentario` y `nueva_fecha_limite = 2026-05-15`
- `APROBACION_EVIDENCIA` de **cada** evidencia: `estado = rechazado`, `nueva_fecha_limite = 2026-05-15`
- `EVIDENCIA_ASIGNACION` de cada evidencia → `Pendiente` con `fecha_limite = 2026-05-15`

> **Importante — el bloque sobreescribe fechas individuales:** si antes rechazaste algunas evidencias individualmente con fechas distintas, el rechazo de bloque las reemplaza todas con la misma `nueva_fecha_limite`. El `comentario` por evidencia se preserva (no se sobreescribe).

---

### 2.5 — Secuencia: rechazo individual + rechazo global

> Prueba que el rechazo por bloque sobreescribe las fechas individuales previas.

**Paso 1:** Rechaza evidencia 6 con fecha individual `2026-06-01`:
```
POST /criterios/3/evidencias/6/rechazar
```
```json
{
    "proceso_id": 1,
    "comentario": "Falta documentación específica de esta evidencia.",
    "nueva_fecha_limite": "2026-06-01"
}
```
**Estado:** evidencia 5 sin tocar, evidencia 6 → `nueva_fecha_limite = 2026-06-01`

**Paso 2:** Rechaza el bloque completo con fecha global `2026-09-01`:
```
POST /criterios/3/rechazar
```
```json
{
    "proceso_id": 1,
    "comentario": "Rechazo global del criterio.",
    "nueva_fecha_limite": "2026-09-01"
}
```
**Resultado esperado:**
- Evidencia 5: `nueva_fecha_limite = 2026-09-01` (antes no tenía)
- Evidencia 6: `nueva_fecha_limite = 2026-09-01` (sobreescribió `2026-06-01`)
- El `comentario` de evidencia 6 se preserva (el bloque no sobreescribe comentarios individuales)

---

## PARTE 3 — Consultas de estado

### 3.1 — VER todas las aprobaciones

```
GET /aprobaciones-criterios
```

### 3.2 — Filtrar por estado

```
GET /aprobaciones-criterios?estado=pendiente
GET /aprobaciones-criterios?estado=incompleto
GET /aprobaciones-criterios?estado=aprobado
GET /aprobaciones-criterios?estado=rechazado
```

### 3.3 — VER aprobación específica por ID

```
GET /aprobaciones-criterios/1
```

---

## PARTE 4 — Ciclo de corrección con bloque `incompleto`

> Este flujo prueba que el Observer funciona: Profesor corrige → bloque `incompleto` vuelve a `pendiente` automáticamente.

> ⚠️ **El Observer solo actúa cuando el bloque está en `incompleto`**, NO cuando está `rechazado`. Ver PARTE 5 para el ciclo con bloque `rechazado`.

**Paso 1:** Ejecuta **1.2** (aprueba evidencia 5) y **1.3** (rechaza evidencia 6).
- Bloque queda `incompleto` (una aprobada + una rechazada).

**Paso 2:** Simula que el Profesor actualiza su asignación a `Completado`:
```
PATCH /asignaciones-evidencias/{evidencia_asignacion_id}
Content-Type: application/json
```
```json
{
    "estado": "Completado"
}
```
> El `evidencia_asignacion_id` de la evidencia 6 es **5** en la BD de pruebas.

**Paso 3:** Consulta el estado del bloque:
```
GET /aprobaciones-criterios?estado=pendiente
```
**Resultado esperado:** El criterio 3 aparece con estado `pendiente` → el Observer lo detectó y lo desbloqueó automáticamente.

**Paso 4:** Ejecuta **1.6** (aprueba evidencia 6).
- Bloque pasa a `aprobado` automáticamente (todas las evidencias = `aprobado`).

---

## PARTE 5 — Ciclo de corrección con bloque `rechazado`

> Cuando el bloque está `rechazado` (todas las evidencias rechazadas), el Observer **no actúa**. El RF debe re-aprobar manualmente.

**Paso 1:** Ejecuta **2.3** o **2.4** para dejar el bloque en `rechazado`.

**Paso 2:** El Profesor corrige sus evidencias y actualiza sus asignaciones a `Completado`. El bloque **no cambia** automáticamente (permanece `rechazado`).

**Paso 3 — opción A:** El RF re-aprueba el bloque completo:
```
POST /criterios/3/aprobar
Content-Type: application/json
```
```json
{
    "proceso_id": 1,
    "comentario": "Correcciones revisadas y aprobadas."
}
```
**Resultado:** Bloque = `aprobado`, todas las evidencias = `aprobado`.

**Paso 3 — opción B:** El RF revisa evidencia por evidencia con **1.6** hasta que todas queden `aprobado` → bloque pasa a `aprobado` automáticamente.

---

## Resumen de estados posibles del bloque

| Situación | Estado bloque |
|---|---|
| Ninguna evidencia tocada | `pendiente` |
| Al menos 1 rechazada (no todas iguales) | `incompleto` |
| Todas rechazadas | `rechazado` |
| Todas aprobadas | `aprobado` |
| Bloque `incompleto` + Profesor marcó `Completado` | vuelve a `pendiente` automáticamente (Observer) |
| Bloque `rechazado` + Profesor marcó `Completado` | **NO cambia** — RF debe re-aprobar manualmente |

---

## Dónde se guarda `nueva_fecha_limite`

| Tabla | Cuándo se guarda | Lo ve |
|---|---|---|
| `APROBACION_CRITERIO` | Rechazo de bloque | RF en `GET /aprobaciones-criterios` |
| `APROBACION_EVIDENCIA` | Rechazo individual O rechazo de bloque | RF en `GET /criterios/{id}/evidencias/aprobaciones` |
| `EVIDENCIA_ASIGNACION` | Cualquier rechazo que resetea la asignación | Profesor en su tarea |

> **Prioridad de sobreescritura:** el rechazo de bloque sobreescribe `nueva_fecha_limite` en todas las `APROBACION_EVIDENCIA`. El rechazo individual posterior sobreescribe solo la evidencia afectada. El `comentario` por evidencia **no** se sobreescribe con el rechazo de bloque.
