# Pruebas Postman — RF-15 Solicitudes de Ampliación (Modelo Flexible)

Cubre el soporte a `elemento_asignacion_id` añadido en la adaptación al modelo flexible.
El flujo con `evidencia_asignacion_id` (modelo tradicional) sigue igual que antes.

## Preparación

```bash
# 1. Prerequisito: árbol TEST-* creado
php artisan db:seed --class=ApprovalElementsTestSeeder

# 2. Seeder específico de solicitudes
php artisan db:seed --class=ExtensionTimeRequestTestSeeder
```

El seeder imprime en consola los IDs exactos a usar. Ejemplo de output:

```
IDs ASIGNACIONES:
  asignacion_f1_id (TEST-F1.1, Pendiente, +10 días):   3
  asignacion_f2_id (TEST-F1.2, En Progreso, +5 días):  4

IDs SOLICITUDES CREADAS:
  solicitud_1_id: 1 — PENDIENTE (F1.2)
  solicitud_2_id: 2 — PENDIENTE (F1.1)
  solicitud_3_id: 3 — APROBADA  (historial F1.1)
```

---

## Variables de entorno Postman

| Variable | Valor de ejemplo |
|---|---|
| `{{base_url}}` | `http://localhost:8000/api` |
| `{{token_profesor}}` | obtenido en Login Profesor |
| `{{token_encargado}}` | obtenido en Login Encargado |
| `{{asignacion_f1_id}}` | ID de TEST-F1.1 (del seeder) |
| `{{asignacion_f2_id}}` | ID de TEST-F1.2 (del seeder) |
| `{{solicitud_1_id}}` | ID de solicitud pendiente F1.2 (del seeder) |
| `{{solicitud_2_id}}` | ID de solicitud pendiente F1.1 (del seeder) |

---

## 1. Login

### 1a. Login Profesor

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "cedula": "<cedula_del_seeder>",
  "password": "password123"
}
```

Guardar `data.token` → `{{token_profesor}}`

### 1b. Login Encargado

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "cedula": "<cedula_encargado_del_seeder>",
  "password": "password123"
}
```

Guardar `data.token` → `{{token_encargado}}`

---

## 2. Próximos a vencer — comparativa tradicional vs flexible

Ambos endpoints son del mismo controller (`ExtensionTimeRequestController`), mismo middleware,
mismo criterio de ventana (7 días). Solo difieren en qué tabla consultan.

### 2a. Modelo tradicional — evidencias próximas a vencer

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer
Authorization: Bearer {{token_profesor}}
```

**Respuesta esperada 200:**
```json
{
  "data": [
    {
      "evidencia_asignacion_id": 7,
      "estado": "En Progreso",
      "fecha_limite": "2026-04-04",
      "evidence": {
        "evidencia_id": 3,
        "nombre": "Evidencia de prueba",
        "criterion": {
          "criterio_id": 1,
          "nombre": "Criterio A"
        }
      },
      "process": { "proceso_id": 1, "nombre": "..." }
    }
  ],
  "total": 1,
  "message": "Evidencias próximas a vencer o ya vencidas."
}
```

> Mensaje cuando no hay: `"No tiene evidencias próximas a vencer en los próximos 7 días."`

---

### 2b. Modelo flexible — elementos próximos a vencer (NUEVO)

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo/elementos/proximas-vencer
Authorization: Bearer {{token_profesor}}
```

**Respuesta esperada 200:**
```json
{
  "data": [
    {
      "elemento_asignacion_id": 4,
      "estado": "En Progreso",
      "fecha_limite": "2026-04-05",
      "element": {
        "elemento_id": 12,
        "nomenclatura": "TEST-F1.2",
        "descripcion": "Fuente de prueba 1.2"
      },
      "process": { "proceso_id": 1, "nombre": "..." }
    }
  ],
  "total": 1,
  "message": "Asignaciones de elementos próximas a vencer o ya vencidas."
}
```

> Mensaje cuando no hay: `"No tiene asignaciones de elementos próximas a vencer en los próximos 7 días."`

**Diferencias clave entre los dos:**

| | Tradicional (`/evidencias/...`) | Flexible (`/elementos/...`) |
|---|---|---|
| Tabla consultada | `EVIDENCIA_ASIGNACION` | `ELEMENTO_ASIGNACION` |
| FK expuesto | `evidencia_asignacion_id` | `elemento_asignacion_id` |
| Relación cargada | `evidence.criterion` | `element` (nomenclatura) |
| Mensaje vacío | "evidencias próximas a vencer" | "asignaciones de elementos..." |
| Lógica de ventana | 7 días — misma | 7 días — misma |

---

## 3. Crear solicitud — modelo flexible (NUEVO)

```http
POST {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_profesor}}
Content-Type: application/json

{
  "elemento_asignacion_id": {{asignacion_f2_id}},
  "motivo": "El archivo requiere validación adicional con el comité de acreditación. El plazo original no es suficiente para completar el análisis requerido.",
  "fecha_sugerida": "2026-05-15"
}
```

**Respuesta esperada 201:**
```json
{
  "message": "Solicitud de ampliación creada exitosamente.",
  "data": {
    "solicitud_ampliacion_id": 10,
    "evidencia_asignacion_id": null,
    "elemento_asignacion_id": 4,
    "elemento_asignacion": {
      "elemento_asignacion_id": 4,
      "estado": "En Progreso",
      "fecha_limite": "2026-04-05",
      "element": {
        "elemento_id": 12,
        "nomenclatura": "TEST-F1.2"
      }
    },
    "estado": "pendiente",
    "motivo": "El archivo requiere...",
    "fecha_sugerida": "2026-05-15"
  }
}
```

---

## 4. Crear solicitud — modelo tradicional (sin cambios)

```http
POST {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_profesor}}
Content-Type: application/json

{
  "evidencia_asignacion_id": 1,
  "motivo": "Retraso justificado por enfermedad con constancia médica adjunta.",
  "fecha_sugerida": "2026-05-20"
}
```

---

## 5. Errores de validación esperados

### 5a. Enviar ambos IDs al mismo tiempo → 422

```http
POST {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_profesor}}
Content-Type: application/json

{
  "evidencia_asignacion_id": 1,
  "elemento_asignacion_id": {{asignacion_f1_id}},
  "motivo": "Motivo de prueba de error de validación.",
  "fecha_sugerida": "2026-05-15"
}
```

**Respuesta esperada 422:**
```json
{
  "message": "Error de validación.",
  "errors": {
    "evidencia_asignacion_id": [
      "Solo puede especificar una asignación: de evidencia o de elemento, no ambas."
    ]
  }
}
```

### 5b. No enviar ningún ID → 422

```http
POST {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_profesor}}
Content-Type: application/json

{
  "motivo": "Sin ID de asignación.",
  "fecha_sugerida": "2026-05-15"
}
```

**Respuesta esperada 422:**
```json
{
  "errors": {
    "evidencia_asignacion_id": ["Debe especificar una asignación de evidencia o de elemento."],
    "elemento_asignacion_id":  ["Debe especificar una asignación de elemento o de evidencia."]
  }
}
```

### 5c. Crear duplicado pendiente para la misma asignación → 422

La solicitud 1 del seeder ya está pendiente para `asignacion_f2_id`. Intentar crear otra:

```http
POST {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_profesor}}
Content-Type: application/json

{
  "elemento_asignacion_id": {{asignacion_f2_id}},
  "motivo": "Intento crear segunda solicitud pendiente para la misma asignación.",
  "fecha_sugerida": "2026-06-01"
}
```

**Respuesta esperada 422:**
```json
{
  "errors": {
    "elemento_asignacion_id": [
      "Ya tiene una solicitud de ampliación pendiente para esta asignación de elemento."
    ]
  }
}
```

### 5d. Fecha sugerida ≤ fecha límite → 422

```http
POST {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_profesor}}
Content-Type: application/json

{
  "elemento_asignacion_id": {{asignacion_f1_id}},
  "motivo": "Intentando poner fecha anterior a la fecha límite original.",
  "fecha_sugerida": "2026-04-01"
}
```

**Respuesta esperada 422** (el servicio valida que `fecha_sugerida > fecha_limite` de la asignación):
```json
{
  "errors": {
    "fecha_sugerida": [
      "La fecha sugerida debe ser posterior a la fecha límite original (..."
    ]
  }
}
```

### 5e. Asignación no es del usuario autenticado → 422

```http
POST {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_encargado}}
Content-Type: application/json

{
  "elemento_asignacion_id": {{asignacion_f1_id}},
  "motivo": "El encargado intenta crear solicitud para asignación del profesor.",
  "fecha_sugerida": "2026-06-01"
}
```

**Respuesta esperada 422** o **403** según Policy:
```json
{
  "errors": {
    "elemento_asignacion_id": ["Solo puede solicitar ampliación para asignaciones propias."]
  }
}
```

---

## 6. Listar solicitudes

### 6a. Profesor — solo ve las suyas

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_profesor}}
```

### 6b. Filtrar solo solicitudes de modelo flexible (`tipo=elemento`)

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo?tipo=elemento
Authorization: Bearer {{token_profesor}}
```

Devuelve únicamente solicitudes con `elemento_asignacion_id` (modelo flexible). Los registros de evidencia no aparecen.

### 6c. Filtrar solo solicitudes de modelo tradicional (`tipo=evidencia`)

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo?tipo=evidencia
Authorization: Bearer {{token_profesor}}
```

Devuelve únicamente solicitudes con `evidencia_asignacion_id` (modelo tradicional). Los registros de elemento no aparecen.

### 6d. Filtrar por asignación específica

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo?elemento_asignacion_id={{asignacion_f1_id}}
Authorization: Bearer {{token_profesor}}
```

Útil para el frontend cuando muestra las solicitudes de una asignación en particular.

### 6e. Combinación de filtros

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo?tipo=elemento&estado=pendiente
Authorization: Bearer {{token_profesor}}
```

### 6f. Encargado — ve todas

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_encargado}}
```

---

## 7. Ver detalle con relaciones cargadas

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_1_id}}
Authorization: Bearer {{token_profesor}}
```

**Respuesta esperada 200:**
```json
{
  "data": {
    "solicitud_ampliacion_id": 1,
    "evidencia_asignacion_id": null,
    "elemento_asignacion_id": 4,
    "evidencia_asignacion": null,
    "elemento_asignacion": {
      "elemento_asignacion_id": 4,
      "estado": "En Progreso",
      "fecha_limite": "2026-04-05",
      "element": {
        "elemento_id": 12,
        "nomenclatura": "TEST-F1.2",
        "descripcion": "Fuente de prueba 1.2"
      }
    },
    "estado": "pendiente",
    "motivo": "...",
    "fecha_sugerida": "2026-04-25",
    "fecha_resolucion": null,
    "justificacion": null
  }
}
```

---

## 8. Actualizar solicitud pendiente

```http
PUT {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_1_id}}
Authorization: Bearer {{token_profesor}}
Content-Type: application/json

{
  "motivo": "Actualizo el motivo: se agrega nuevo requerimiento del comité de acreditación.",
  "fecha_sugerida": "2026-05-30"
}
```

**Respuesta esperada 200:**
```json
{
  "message": "Solicitud actualizada exitosamente.",
  "data": { ... }
}
```

**Intentar cambiar el ID de asignación → 422 (prohibido):**
```http
PUT {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_1_id}}
Authorization: Bearer {{token_profesor}}
Content-Type: application/json

{
  "elemento_asignacion_id": 999,
  "motivo": "Intento cambiar asignación."
}
```

---

## 9. Cancelar solicitud pendiente

Cancelar **cambia el estado a `cancelada`** — el registro queda en BD para historial/auditoría.
A diferencia de DELETE, el encargado puede ver por qué la solicitud ya no aplica.

```http
PATCH {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_1_id}}/cancelar
Authorization: Bearer {{token_profesor}}
```

**Respuesta esperada 200:**
```json
{
  "message": "Solicitud cancelada exitosamente."
}
```

**Intentar cancelar una solicitud ya aprobada → 403:**
```http
PATCH {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_3_id}}/cancelar
Authorization: Bearer {{token_profesor}}
```

**Intentar cancelar una solicitud ajena → 403:**
```http
PATCH {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_1_id}}/cancelar
Authorization: Bearer {{token_encargado}}
```

> **Auto-cancelación por Observer:** Si el encargado cambia el estado de una `ELEMENTO_ASIGNACION`
> a `Completado` o `Validada`, todas las solicitudes `pendientes` de esa asignación se cancelan
> automáticamente con `justificacion: "Auto-cancelada: la asignación fue marcada como completado."`

---

## 10. Eliminar solicitud pendiente

```http
DELETE {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_1_id}}
Authorization: Bearer {{token_profesor}}
```

**Respuesta esperada 200:**
```json
{
  "message": "Solicitud eliminada exitosamente."
}
```

**Intentar eliminar la solicitud APROBADA (solicitud_3) → 422:**
```http
DELETE {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_3_id}}
Authorization: Bearer {{token_profesor}}
```

---

## 11. Resumen de reglas de negocio probadas

| # | Regla | Endpoint | Estado esperado |
|---|---|---|---|
| 1 | Solo un FK por solicitud (XOR) | POST | 422 si ambos o ninguno |
| 2 | Solo 1 solicitud pendiente por asignación | POST | 422 duplicado |
| 3 | Debe ser la asignación del propio usuario | POST | 422 si ajena |
| 4 | La asignación no puede estar aprobada/validada | POST | 422 |
| 5 | El plazo no puede estar vencido | POST | 422 |
| 6 | `fecha_sugerida` > `fecha_limite` de la asignación | POST | 422 |
| 7 | Máximo 30 días de extensión | POST | 422 si > 30 días |
| 8 | Solo editar solicitudes propias pendientes | PUT | 403 o 422 |
| 9 | No cambiar el FK de asignación en PUT | PUT | 422 prohibido |
| 10 | Solo eliminar solicitudes propias pendientes | DELETE | 403 o 422 |
| 11 | Solo cancelar solicitudes propias pendientes | PATCH /cancelar | 403 si ajena o ya resuelta |
| 12 | Auto-cancelar al completar asignación (Observer) | — | Automático al cambiar estado EA |
