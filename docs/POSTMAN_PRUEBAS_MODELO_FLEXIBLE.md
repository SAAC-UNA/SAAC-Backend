# Pruebas Postman — Modelo Flexible (Backend)

Guía paso a paso para verificar que todo el backend adaptado al modelo flexible funciona correctamente.
Cada paso depende del `id` del anterior — guárdalos en variables de entorno de Postman.

---

## PASO 0 — Listar modelos de estructura existentes

**Objetivo:** Confirmar que existe un modelo de tipo `elemento_flexible` y obtener su `id`.

```
GET /api/estructura/modelos/activos
```
Sin body.

**Respuesta esperada:** Array con modelos. Buscar el que tenga `"tipo": "elemento_flexible"` y anotar su `modelo_estructura_id`.

---

## PASO 1 — Listar carreras-sede disponibles

**Objetivo:** Obtener un `carrera_sede_id` válido para crear el ciclo.

```
GET /api/estructura/carreras
```
Sin body.

**Respuesta esperada:** Array de carreras con sus sedes. Anotar un `carrera_sede_id`.

---

## PASO 2 — Crear ciclo de acreditación flexible

**Objetivo (HU-030):** Crear un ciclo vinculado al modelo flexible.

```
POST /api/estructura/ciclos-acreditacion
```
```json
{
  "nombre": "Ciclo Flexible 2026",
  "carrera_sede_id": 1,
  "modelo_estructura_id": 2,
  "fecha_inicio": "2026-01-01",
  "fecha_fin": "2026-12-31"
}
```

**Respuesta esperada:** 201 con `ciclo_acreditacion_id`. Anotar ese id.

---

## PASO 3 — Crear proceso en el ciclo flexible

**Objetivo:** Crear un proceso vinculado al ciclo del paso anterior.

```
POST /api/estructura/procesos
```
```json
{
  "ciclo_acreditacion_id": <id del paso 2>,
  "tipo_proceso": "Autoevaluación",
  "fecha_inicio": "2026-01-01",
  "fecha_finalizacion": "2026-12-31"
}
```

> ⚠️ `tipo_proceso` es case-sensitive. Valores válidos: `"Autoevaluación"` o `"Compromiso de mejora"`.

**Respuesta esperada:** 201 con `proceso_id`. Anotar ese id.

---

## PASO 4 — Crear elemento del modelo flexible

**Objetivo:** Crear un elemento dentro del modelo flexible (equivale al criterio en el modelo tradicional).

```
POST /api/estructura/elementos
```
```json
{
  "modelo_estructura_id": 2,
  "nombre": "Elemento de prueba",
  "tipo": "Indicador",
  "padre_id": null
}
```

> `padre_id: null` = elemento raíz. Se puede crear jerarquía pasando el `elemento_id` de un padre.

**Respuesta esperada:** 201 con `elemento_id`. Anotar ese id.

---

## PASO 5 — Crear evidencia flexible

**Objetivo (HU-012):** Crear una evidencia anclada al elemento (sin `criterio_id`).

```
POST /api/estructura/evidencias
```
```json
{
  "elemento_id": <id del paso 4>,
  "estado": "Pendiente",
  "descripcion": "Evidencia de prueba flexible",
  "nomenclatura": "EV-F-001"
}
```

> ⚠️ `estado` es case-sensitive. Valores válidos: `Pendiente`, `En Proceso`, `Completado`, `Vencido`, `Aprobado`, `Rechazado`, `Observada`, `Validada`.

**Respuesta esperada:** 201 con `evidencia_id`, `criterio_id: null`, `elemento_id` con valor. Anotar el `evidencia_id`.

---

## PASO 6 — Asignar evidencia flexible al proceso ✅ caso positivo

**Objetivo (HU-007):** Verificar que una evidencia flexible se puede asignar a un proceso flexible.

```
POST /api/evidencias-asignaciones
```
```json
{
  "proceso_id": <id del paso 3>,
  "evidencia_id": <id del paso 5>,
  "usuarios": [1]
}
```

**Respuesta esperada:** 200/201 con la asignación creada. La evidencia en la respuesta debe tener `criterio_id: null` y `elemento_id` con valor.

---

## PASO 7 — Intentar asignar evidencia TRADICIONAL al proceso flexible ❌ caso negativo

**Objetivo (HU-007):** Verificar que el guard rechaza la incompatibilidad de modelos.

```
POST /api/evidencias-asignaciones
```
```json
{
  "proceso_id": <id del paso 3>,
  "evidencia_id": 1,
  "usuarios": [1]
}
```

> La evidencia `id: 1` tiene `criterio_id` y pertenece al modelo tradicional.

**Respuesta esperada:** **422** con mensaje:
```json
{
  "message": "El ciclo usa modelo elemento_flexible pero la evidencia está anclada a un criterio tradicional. Use una evidencia con elemento_id."
}
```

---

## PASO 8 — Filtrar evidencias por modelo flexible

**Objetivo (HU-012):** Verificar que el filtro contextual devuelve solo evidencias del modelo flexible.

```
GET /api/estructura/evidencias/filter?modelo_estructura_id=2
```
Sin body.

**Respuesta esperada:** Lista paginada que contiene **solo** la evidencia del paso 5 (`criterio_id: null`, `elemento_id` con valor).

---

## PASO 9 — Actualizar estado de evidencia flexible

**Objetivo (HU-013):** Verificar que el observer no crashea al actualizar una evidencia sin `criterio_id`.

```
PATCH /api/estructura/evidencias/<id del paso 5>
```
```json
{
  "estado": "En Proceso"
}
```

**Respuesta esperada:** **200** con `"estado": "En Proceso"` — sin error 500. Antes del fix crasheaba con TypeError en PHP 8.1+ porque el observer intentaba pasar `null` a un parámetro `int`.

---

## Resumen de resultados esperados

| Paso | HU | Resultado esperado |
|------|----|--------------------|
| 0 | — | Lista modelos, identificar `elemento_flexible` |
| 1 | — | Lista carreras-sede disponibles |
| 2 | HU-030 | 201 — ciclo creado con `modelo_estructura_id` |
| 3 | — | 201 — proceso creado en el ciclo flexible |
| 4 | — | 201 — elemento creado en el modelo flexible |
| 5 | HU-012 | 201 — evidencia con `elemento_id`, `criterio_id: null` |
| 6 | HU-007 | 201 — asignación aceptada (modelos compatibles) |
| 7 | HU-007 | 422 — asignación rechazada (modelos incompatibles) |
| 8 | HU-012 | 200 — solo devuelve evidencias flexibles |
| 9 | HU-013 | 200 — estado actualizado sin crash del observer |
