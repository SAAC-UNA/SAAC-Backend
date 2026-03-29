# HU-012 — Ejemplos de Filtrado Avanzado de Evidencias

## Estructura del modelo SINAES (datos reales del sistema)

```
DIMENSIÓN 1 - Relación con el contexto
  ├── 1.1 Información y promoción
  │     ├── Criterio 1.1.1 → tiene estándar (estandar_id=1)
  │     └── Criterio 1.1.2 → tiene estándar (estandar_id=2)
  ├── 1.2 Proceso de admisión e ingreso
  │     ├── Criterio 1.2.1 → SIN estándar
  │     └── Criterio 1.2.2 → SIN estándar
  └── 1.3 Correspondencia con el contexto

DIMENSIÓN 2 - Recursos
  ├── 2.1 Plan de estudios
  │     ├── Criterio 2.1.1 → tiene estándar (estandar_id=3)
  │     └── Criterio 2.1.2 → SIN estándar
  └── 2.2 Personal académico
        ├── Criterio 2.2.1 → SIN estándar
        └── Criterio 2.2.3 → SIN estándar

DIMENSIÓN 3 - Proceso educativo
  ├── 3.1 Desarrollo docente
  └── 3.2 Metodología enseñanza-aprendizaje

DIMENSIÓN 4 - Resultados
  └── 4.1 ...
```

> **Nota:** El sistema tiene 4 dimensiones (1–4). No existe dimensión 5.

---

## Filtros individuales — Ejemplos en Postman

### 1. Filtrar por Dimensión

Trae **todas las evidencias** de todos los criterios que pertenecen a esa dimensión (sin importar el componente o criterio específico).

```
GET /api/estructura/evidencias/filter?dimension_id=2
Authorization: Bearer {token}
```

**Respuesta (fragmento):**
```json
{
  "data": [
    {
      "evidencia_id": 10,
      "nomenclatura": "EV-2.1.1-01",
      "descripcion": "Documento descriptivo del plan de estudios 2025",
      "activo": true,
      "fecha_publicacion": "2025-02-15T10:30:00.000Z",
      "criterion": {
        "id": 5,
        "nomenclatura": "2.1.1",
        "descripcion": "La carrera debe contar con un documento descriptivo...",
        "activo": true,
        "component": {
          "componente_id": 4,
          "nomenclatura": "2.1",
          "nombre": "Plan de estudios",
          "dimension": {
            "dimension_id": 2,
            "nomenclatura": "2",
            "nombre": "Recursos"
          }
        },
        "standards": [
          {
            "estandar_id": 3,
            "descripcion": "Todos los cursos —el 100%— deben contar con sus respectivos programas...",
            "activo": true
          }
        ]
      },
      "estado_evidencia": { "nombre": "Pendiente" },
      "archivos_count": 2,
      "enlaces_count": 1
    },
    {
      "evidencia_id": 11,
      "nomenclatura": "EV-2.2.1-01",
      "descripcion": "Normativa del personal académico vigente",
      "activo": true,
      "fecha_publicacion": "2025-03-10T08:00:00.000Z",
      "criterion": {
        "id": 7,
        "nomenclatura": "2.2.1",
        "descripcion": "Se debe contar con normativas para el personal académico...",
        "activo": true,
        "component": {
          "componente_id": 5,
          "nomenclatura": "2.2",
          "nombre": "Personal académico",
          "dimension": {
            "dimension_id": 2,
            "nomenclatura": "2",
            "nombre": "Recursos"
          }
        },
        "standards": []
      },
      "estado_evidencia": { "nombre": "En Revisión" },
      "archivos_count": 3,
      "enlaces_count": 0
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 12
  }
}
```

---

### 2. Filtrar por Componente

Trae evidencias de todos los criterios de ese componente.

```
GET /api/estructura/evidencias/filter?componente_id=4
Authorization: Bearer {token}
```

> Componente 4 = **2.1 Plan de estudios** (dentro de Dimensión 2)

**Resultado esperado:** Solo evidencias de los criterios 2.1.1 y 2.1.2.

---

### 3. Filtrar por Criterio

Trae evidencias de un criterio específico. Este es el filtro más preciso en la jerarquía.

```
GET /api/estructura/evidencias/filter?criterio_id=5
Authorization: Bearer {token}
```

> Criterio 5 = **2.1.1**

**Resultado esperado:** Solo evidencias directamente vinculadas al criterio 2.1.1.

---

### 4. Filtrar por Estándar

Trae evidencias del criterio **al que pertenece ese estándar**.

```
GET /api/estructura/evidencias/filter?estandar_id=3
Authorization: Bearer {token}
```

> Estándar 3 pertenece al criterio 2.1.1

**Resultado esperado:** Idéntico a `?criterio_id=5`

---

### 5. Filtrar por Estado

```
GET /api/estructura/evidencias/filter?estado_evidencia_id=1
Authorization: Bearer {token}
```

---

### 6. Filtrar por Rango de Fechas

```
GET /api/estructura/evidencias/filter?fecha_desde=2025-01-01&fecha_hasta=2025-12-31
Authorization: Bearer {token}
```

---

### 7. Filtros combinados

```
GET /api/estructura/evidencias/filter?dimension_id=2&estado_evidencia_id=1&sort_by=nomenclatura&sort_order=asc&per_page=20
Authorization: Bearer {token}
```

Evidencias de Dimensión 2 (Recursos) que están Pendientes, ordenadas por nomenclatura.

---

## Ejemplo completo: Dimensión 2 agrupada por criterio

Este agrupamiento lo hace el **frontend** con la respuesta paginada. El backend devuelve la lista plana con toda la jerarquía dentro de cada evidencia:

```
GET /api/estructura/evidencias/filter?dimension_id=2&per_page=100&sort_by=nomenclatura&sort_order=asc
```

**Respuesta completa:**
```json
{
  "data": [
    {
      "id": 10,
      "nomenclatura": "EV-2.1.1-01",
      "descripcion": "Documento descriptivo del plan de estudios 2025",
      "activo": true,
      "criterion": {
        "criterio_id": 5,
        "nomenclatura": "2.1.1",
        "descripcion": "La carrera debe contar con un documento descriptivo con antecedentes, fundamentos conceptuales, objetivos...",
        "component": {
          "componente_id": 4,
          "nomenclatura": "2.1",
          "nombre": "Plan de estudios",
          "dimension": {
            "dimension_id": 2,
            "nomenclatura": "2",
            "nombre": "Recursos"
          }
        },
        "standards": [
          {
            "estandar_id": 3,
            "descripcion": "Todos los cursos —el 100%— deben contar con sus respectivos programas y éstos deben estar completos.",
            "activo": true
          }
        ]
      },
      "evidence_state": {
        "estado_evidencia_id": 1,
        "nombre": "Pendiente"
      },
      "archivos_count": 2,
      "enlaces_count": 0,
      "assignments": [
        {
          "usuario_id": 3,
          "estado": "En Progreso",
          "user": { "name": "Ana García", "roles": [{"name": "Profesor"}] }
        }
      ]
    },
    {
      "id": 11,
      "nomenclatura": "EV-2.1.2-01",
      "descripcion": "Objetivos de la carrera alineados con la misión institucional",
      "criterion": {
        "criterio_id": 6,
        "nomenclatura": "2.1.2",
        "descripcion": "Los fines y objetivos de la carrera deben ser claros y congruentes con los postulados de la institución...",
        "component": {
          "componente_id": 4,
          "nomenclatura": "2.1",
          "nombre": "Plan de estudios",
          "dimension": { "dimension_id": 2, "nombre": "Recursos" }
        },
        "standards": []
      },
      "evidence_state": { "nombre": "Aprobado" },
      "archivos_count": 1,
      "enlaces_count": 2
    },
    {
      "id": 12,
      "nomenclatura": "EV-2.2.1-01",
      "descripcion": "Normativa del personal académico — Reglamento vigente",
      "criterion": {
        "criterio_id": 7,
        "nomenclatura": "2.2.1",
        "descripcion": "Se debe contar con normativas para el personal académico que regule sus deberes y derechos...",
        "component": {
          "componente_id": 5,
          "nomenclatura": "2.2",
          "nombre": "Personal académico",
          "dimension": { "dimension_id": 2, "nombre": "Recursos" }
        },
        "standards": []
      },
      "evidence_state": { "nombre": "En Revisión" },
      "archivos_count": 3,
      "enlaces_count": 0
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 100,
    "total": 3,
    "last_page": 1
  },
  "links": { ... }
}
```

**Cómo el frontend agrupa por criterio:**
```js
// Agrupar la respuesta por criterio
const grouped = data.reduce((acc, evidencia) => {
  const key = evidencia.criterion.nomenclatura;
  if (!acc[key]) acc[key] = { criterio: evidencia.criterion, evidencias: [] };
  acc[key].evidencias.push(evidencia);
  return acc;
}, {});

// Resultado:
// {
//   "2.1.1": { criterio: {..., standards: [{...}]}, evidencias: [ev10] },
//   "2.1.2": { criterio: {..., standards: []},      evidencias: [ev11] },
//   "2.2.1": { criterio: {..., standards: []},      evidencias: [ev12] }
// }
```

---

## ¿Tiene sentido el filtro por `estandar_id`?

### La relación real (dato del seeder)

| Estándar | Pertenece a criterio | ¿Todos los criterios tienen estándar? |
|---|---|---|
| estandar_id=1 | criterio 1.1.1 | No |
| estandar_id=2 | criterio 1.1.2 | No |
| estandar_id=3 | criterio 2.1.1 | No |
| — | criterio 1.2.1 | No tiene estándar |
| — | criterio 2.1.2 | No tiene estándar |
| — | criterio 2.2.1 | No tiene estándar |

### Resultado del filtro `?estandar_id=3`

Como un estándar pertenece a **exactamente un criterio**, el resultado de:
```
?estandar_id=3
```
es **idéntico** a:
```
?criterio_id=5
```
(el criterio 2.1.1 que tiene ese estándar)

### Veredicto: el filtro `estandar_id` es válido pero limitado

| Aspecto | Análisis |
|---|---|
| ¿Es redundante con `criterio_id`? | **Sí**, produce el mismo resultado que filtrar por el criterio dueño del estándar |
| ¿Tiene caso de uso real? | Sí: cuando el usuario llega **desde la vista de un estándar** y quiere ver sus evidencias sin saber el criterio_id |
| ¿Filtra "solo criterios que tienen estándares"? | **No.** Para eso necesitarías un parámetro diferente como `?con_estandar=true` |
| ¿Es peligroso dejarlo? | No, no hace daño |

### Si quieres también poder filtrar "solo criterios con estándar"

Eso requeriría un parámetro adicional como `?solo_con_estandar=true`, que haría internamente:
```php
$query->whereHas('criterion.standards');
```
Pero eso es una funcionalidad diferente a la que se pidió. Por ahora el `estandar_id` sirve para el caso de uso de "mostrar evidencias de UN estándar específico".

---

## Resumen de todos los filtros disponibles (RF-12)

| Parámetro | Tipo | Descripción |
|---|---|---|
| `dimension_id` | integer | Filtra por dimensión (1–4) |
| `componente_id` | integer | Filtra por componente dentro de una dimensión |
| `criterio_id` | integer | Filtra por criterio específico |
| `estandar_id` | integer | Filtra por el criterio dueño de ese estándar |
| `estado_evidencia_id` | integer | Filtra por estado (Pendiente, Aprobado, etc.) |
| `responsable_id` | integer | (validado, pendiente de aplicar en service) |
| `rol_id` | integer | Filtra evidencias asignadas a usuarios con ese rol |
| `fecha_desde` | date (YYYY-MM-DD) | Rango inicio por `created_at` |
| `fecha_hasta` | date (YYYY-MM-DD) | Rango fin por `created_at` |
| `sort_by` | string | `fecha`, `nomenclatura`, `descripcion`, `estado` |
| `sort_order` | string | `asc` o `desc` |
| `per_page` | integer | Resultados por página (5–100, default 15) |
| `page` | integer | Número de página |
