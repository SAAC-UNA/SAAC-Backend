# Pruebas Postman Ã¢â‚¬â€ RF-15 Solicitudes de AmpliaciÃƒÂ³n (Tradicional y Flexible)

GuÃƒÂ­a completa para los dos modelos de solicitudes de ampliaciÃƒÂ³n de plazo. Ambos estÃƒÂ¡n
completamente separados: tablas distintas, rutas distintas, controllers distintos.

## Resumen de endpoints

| Modelo | Prefijo | Controller | Tabla |
|--------|---------|------------|-------|
| GestiÃƒÂ³n encargado (tradicional) | `/api/solicitudes-ampliacion` | `ExtensionRequestController` | `SOLICITUD_AMPLIACION` |
| Profesor Ã¢â‚¬â€ tradicional | `/api/solicitudes-ampliacion-tiempo` | `ExtensionTimeRequestController` | `SOLICITUD_AMPLIACION` |
| Profesor Ã¢â‚¬â€ flexible | `/api/solicitudes-ampliacion-elemento` | `ElementExtensionTimeRequestController` | `SOLICITUD_AMPLIACION_ELEMENTO` |

> Los dos primeros grupos comparten la misma tabla `SOLICITUD_AMPLIACION`. El grupo "gestiÃƒÂ³n
> encargado" sirve para que el Encargado de AcreditaciÃƒÂ³n apruebe/rechace. El grupo "profesor
> tradicional" sirve para que el profesor cree y administre sus propias solicitudes.

## IDs reales en la base de datos

### EVIDENCIA_ASIGNACION (modelo tradicional)

| evidencia_asignacion_id | descripciÃƒÂ³n | usuario asignado | fecha_limite |
|---|---|---|---|
| 1 | Lista descriptiva de los materiales informativos... | Naydelin Nayeli Jiron Castellon | 2026-05-01 |
| 2 | DescripciÃƒÂ³n de la estrategia de comunicaciÃƒÂ³n... | Naydelin Nayeli Jiron Castellon | 2026-05-01 |
| 3 | Porcentaje de estudiantes que reciben informaciÃƒÂ³n | Naydelin Nayeli Jiron Castellon | 2026-05-01 |
| 4 | Porcentaje de estudiantes que opina sobre entrega | Naydelin Nayeli Jiron Castellon | 2026-05-01 |

### ELEMENTO_ASIGNACION (modelo flexible)

| elemento_asignacion_id | elemento | usuario asignado | estado | fecha_limite |
|---|---|---|---|---|
| 1 | TEST-F1.1 | Marisol Hidalgo Murillo | **Completado** ❌ | 2026-05-01 |
| 2 | TEST-F1.2 | Marisol Hidalgo Murillo | En Progreso ✅ | 2026-04-16 |
| 3 | TEST-F1.1 | Marisol Hidalgo Murillo | **Completado** ❌ | 2026-05-16 |
| 4 | TEST-F1.2 | Marisol Hidalgo Murillo | En Progreso ✅ | 2026-05-16 |
| 5 | TEST-F1.1 | Ian Enmanuel Villegas Jimenez | Pendiente ✅ | 2026-05-15 |
| 6 | TEST-F1.2 | Ian Enmanuel Villegas Jimenez | Pendiente ✅ | 2026-05-30 |

> IDs 1 y 3 tienen `estado=Completado` → el servicio rechaza solicitudes sobre ellos.
> Usar **2 o 4** (Marisol) o **5 o 6** (Ian) para crear solicitudes.

### Usuarios disponibles

| cedula | nombre | rol |
|---|---|---|
| `801490957` | Naydelin Nayeli Jiron Castellon | Superusuario (tiene evidencias asignadas) |
| `208330811` | Jose Andres Jara Arias | Administrador |
| `118620669` | Marisol Hidalgo Murillo | Profesor (tiene elementos asignados 1-4) |
| `207800171` | Ian Enmanuel Villegas Jimenez | Profesor (tiene elemento_asignacion 5) |
| `222222222` | Encargado AcreditaciÃƒÂ³n Test | Encargado de AcreditaciÃƒÂ³n |

ContraseÃƒÂ±a de todos: `password123`

---

## Variables de entorno Postman

| Variable | Valor |
|---|---|
| `{{base_url}}` | `http://localhost:8000/api` |
| `{{token_naydelin}}` | token del Superusuario (usa evidencias asignadas) |
| `{{token_marisol}}` | token de Marisol (Profesora, tiene elementos 1-4) |
| `{{token_ian}}` | token de Ian (Profesor, tiene elemento 5) |
| `{{token_encargado}}` | token del Encargado de AcreditaciÃƒÂ³n |
| `{{solicitud_tradicional_id}}` | ID de solicitud creada en el flujo tradicional |
| `{{solicitud_flexible_id}}` | ID de solicitud creada en el flujo flexible |

---

## 1. Login

### Login Superusuario (Naydelin Ã¢â‚¬â€ tiene EVIDENCIA_ASIGNACION 1-4)

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "cedula": "801490957",
  "password": "password123"
}
```

Guardar `data.token` Ã¢â€ â€™ `{{token_naydelin}}`

### Login Profesora Marisol (tiene ELEMENTO_ASIGNACION 1-4)

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "cedula": "118620669",
  "password": "password123"
}
```

Guardar `data.token` Ã¢â€ â€™ `{{token_marisol}}`

### Login Encargado de AcreditaciÃƒÂ³n

```http
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "cedula": "222222222",
  "password": "password123"
}
```

Guardar `data.token` Ã¢â€ â€™ `{{token_encargado}}`

---

## 2. Modelo FLEXIBLE Ã¢â‚¬â€ `/api/solicitudes-ampliacion-elemento`

> Usa `SOLICITUD_AMPLIACION_ELEMENTO` (PK: `solicitud_ampliacion_elemento_id`, FK: `elemento_asignacion_id`)

### 2.1 Elementos prÃƒÂ³ximos a vencer

```http
GET {{base_url}}/solicitudes-ampliacion-elemento/proximas-vencer
Authorization: Bearer {{token_marisol}}
```

Devuelve `ELEMENTO_ASIGNACION` de Marisol cuya `fecha_limite` estÃƒÂ© en Ã¢â€°Â¤ 7 dÃƒÂ­as.

**Respuesta esperada 200:**
```json
{
  "data": [
    {
      "elemento_asignacion_id": 2,
      "estado": "Pendiente",
      "fecha_limite": "2026-04-16",
      "element": {
        "elemento_id": 2,
        "nomenclatura": "TEST-F1.2",
        "descripcion": "..."
      }
    }
  ],
  "total": 1,
  "message": "Asignaciones de elementos prÃƒÂ³ximas a vencer o ya vencidas."
}
```

Cuando no hay: `"No tiene asignaciones de elementos prÃƒÂ³ximas a vencer en los prÃƒÂ³ximos 7 dÃƒÂ­as."`

---

### 2.2 Crear solicitud flexible

```http
POST {{base_url}}/solicitudes-ampliacion-elemento
Authorization: Bearer {{token_marisol}}
Content-Type: application/json

{
  "elemento_asignacion_id": 2,
  "motivo": "El comitÃƒÂ© de acreditaciÃƒÂ³n solicita documentaciÃƒÂ³n adicional que requiere coordinaciÃƒÂ³n con entes externos. El plazo original no es suficiente para completar el proceso.",
  "fecha_sugerida": "2026-07-30"
}
```

**Respuesta esperada 201:**
```json
{
  "message": "Solicitud de ampliaciÃƒÂ³n creada exitosamente.",
  "data": {
    "solicitud_ampliacion_elemento_id": 1,
    "usuario_id": 3,
    "elemento_asignacion_id": 2,
    "elemento_asignacion": {
      "elemento_asignacion_id": 2,
      "estado": "Pendiente",
      "fecha_limite": "2026-04-16",
      "element": {
        "elemento_id": 2,
        "nomenclatura": "TEST-F1.2",
        "descripcion": "..."
      }
    },
    "estado": "pendiente",
    "motivo": "El comitÃƒÂ© de acreditaciÃƒÂ³n...",
    "fecha_solicitud": "2026-05-01 10:00:00",
    "fecha_sugerida": "2026-07-30",
    "fecha_resolucion": null,
    "justificacion": null
  }
}
```

Guardar `data.solicitud_ampliacion_elemento_id` Ã¢â€ â€™ `{{solicitud_flexible_id}}`

---

### 2.3 Listar solicitudes flexibles

```http
GET {{base_url}}/solicitudes-ampliacion-elemento
Authorization: Bearer {{token_marisol}}
Accept: application/json
```

Marisol solo ve las suyas. El Encargado ve todas.

**Filtros opcionales:**
- `?estado=pendiente` Ã¢â‚¬â€ filtra por estado (`pendiente`, `aprobada`, `rechazada`, `cancelada`)
- `?elemento_asignacion_id=2` Ã¢â‚¬â€ filtra por asignaciÃƒÂ³n especÃƒÂ­fica
- `?fecha_desde=2026-01-01&fecha_hasta=2026-12-31` Ã¢â‚¬â€ rango de fechas
- `?per_page=20&page=1` Ã¢â‚¬â€ paginaciÃƒÂ³n

Ejemplo con filtro:
```http
GET {{base_url}}/solicitudes-ampliacion-elemento?estado=pendiente&per_page=10
Authorization: Bearer {{token_marisol}}
```

---

### 2.4 Ver detalle de solicitud flexible

```http
GET {{base_url}}/solicitudes-ampliacion-elemento/{{solicitud_flexible_id}}
Authorization: Bearer {{token_marisol}}
```

**Respuesta 200:**
```json
{
  "data": {
    "solicitud_ampliacion_elemento_id": 1,
    "usuario_id": 3,
    "elemento_asignacion_id": 2,
    "elemento_asignacion": { ... },
    "estado": "pendiente",
    "motivo": "...",
    "fecha_sugerida": "2026-07-30",
    "fecha_resolucion": null,
    "justificacion": null,
    "resolutor": null
  }
}
```

---

### 2.5 Actualizar solicitud flexible pendiente

Solo se puede modificar `motivo` y/o `fecha_sugerida`. No se puede cambiar `elemento_asignacion_id`.

```http
PUT {{base_url}}/solicitudes-ampliacion-elemento/{{solicitud_flexible_id}}
Authorization: Bearer {{token_marisol}}
Content-Type: application/json

{
  "motivo": "ActualizaciÃƒÂ³n: se agrega requerimiento de documentaciÃƒÂ³n oficial del SINAES.",
  "fecha_sugerida": "2026-08-15"
}
```

**Intentar cambiar el ID de asignaciÃƒÂ³n Ã¢â€ â€™ 422 (campo prohibido):**
```json
{ "elemento_asignacion_id": 999 }
```

---

### 2.6 Cancelar solicitud flexible

Cancela sin eliminar Ã¢â‚¬â€ el registro permanece con estado `cancelada` para historial.

```http
PATCH {{base_url}}/solicitudes-ampliacion-elemento/{{solicitud_flexible_id}}/cancelar
Authorization: Bearer {{token_marisol}}
```

**Respuesta 200:** `{ "message": "Solicitud cancelada exitosamente." }`

**Solo pendientes pueden cancelarse.** Si el estado es `aprobada` o `rechazada` Ã¢â€ â€™ 422.

---

### 2.7 Eliminar solicitud flexible pendiente

EliminaciÃƒÂ³n fÃƒÂ­sica Ã¢â‚¬â€ solo disponible mientras `estado = pendiente`.

```http
DELETE {{base_url}}/solicitudes-ampliacion-elemento/{{solicitud_flexible_id}}
Authorization: Bearer {{token_marisol}}
```

**Respuesta 200:** `{ "message": "Solicitud eliminada exitosamente." }`

---

## 3. Modelo TRADICIONAL Ã¢â‚¬â€ `/api/solicitudes-ampliacion-tiempo`

> Usa `SOLICITUD_AMPLIACION` (PK: `solicitud_ampliacion_id`, FK: `evidencia_asignacion_id`)
> Las EVIDENCIA_ASIGNACION 1-4 estÃƒÂ¡n asignadas al Superusuario Naydelin.

### 3.1 Evidencias prÃƒÂ³ximas a vencer

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer
Authorization: Bearer {{token_naydelin}}
```

Respuesta misma estructura que flexible, pero con campo `evidencia_asignacion_id` y relaciÃƒÂ³n `evidence`.

---

### 3.2 Crear solicitud tradicional

```http
POST {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_naydelin}}
Content-Type: application/json

{
  "evidencia_asignacion_id": 1,
  "motivo": "Retraso justificado: el proveedor de datos no ha entregado el informe anual requerido para completar esta evidencia.",
  "fecha_sugerida": "2026-07-01"
}
```

**Respuesta 201:** mismo patrÃƒÂ³n que flexible, pero con `solicitud_ampliacion_id` y `evidencia_asignacion_id`.

Guardar `data.solicitud_ampliacion_id` Ã¢â€ â€™ `{{solicitud_tradicional_id}}`

---

### 3.3 Listar solicitudes tradicionales

```http
GET {{base_url}}/solicitudes-ampliacion-tiempo
Authorization: Bearer {{token_naydelin}}
```

**Filtros opcionales:** mismos que el modelo flexible (`estado`, `evidencia_asignacion_id`, `fecha_desde`, `fecha_hasta`, `per_page`).

---

### 3.4 Ver, actualizar, cancelar, eliminar

```http
GET    {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_tradicional_id}}
PUT    {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_tradicional_id}}
PATCH  {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_tradicional_id}}/cancelar
DELETE {{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_tradicional_id}}
```

Mismas reglas que flexible. En PUT, `evidencia_asignacion_id` estÃƒÂ¡ prohibido.

---

## 4. GestiÃƒÂ³n de Encargado Ã¢â‚¬â€ `/api/solicitudes-ampliacion`

> Este grupo es exclusivo para que el Encargado de AcreditaciÃƒÂ³n revise y apruebe/rechace
> solicitudes del modelo **tradicional** (`SOLICITUD_AMPLIACION`). El modelo flexible
> **no tiene endpoint de aprobaciÃƒÂ³n separado** Ã¢â‚¬â€ la gestiÃƒÂ³n es directa sobre la tabla.

### 4.1 Listar todas las solicitudes (pendientes o historial)

```http
GET {{base_url}}/solicitudes-ampliacion
Authorization: Bearer {{token_encargado}}
```

**Filtros opcionales:** `?estado=pendiente`, `?usuario_id=1`, `?per_page=20`

---

### 4.2 Ver solo las pendientes

```http
GET {{base_url}}/solicitudes-ampliacion/pendientes
Authorization: Bearer {{token_encargado}}
```

---

### 4.3 Ver mis solicitudes (si el encargado tambiÃƒÂ©n tiene rol de profesor)

```http
GET {{base_url}}/solicitudes-ampliacion/mis-solicitudes
Authorization: Bearer {{token_encargado}}
```

---

### 4.4 Ver detalle de una solicitud

```http
GET {{base_url}}/solicitudes-ampliacion/{{solicitud_tradicional_id}}
Authorization: Bearer {{token_encargado}}
```

---

### 4.5 Aprobar solicitud

```http
POST {{base_url}}/solicitudes-ampliacion/{{solicitud_tradicional_id}}/aprobar
Authorization: Bearer {{token_encargado}}
Content-Type: application/json

{
  "justificacion": "Solicitud aprobada. Plazo extendido conforme a la documentaciÃ³n presentada."
}
```

La `justificacion` es opcional en aprobaciÃ³n pero requerida en rechazo.

**Respuesta 200:**
```json
{
  "message": "Solicitud aprobada exitosamente.",
  "data": {
    "solicitud_ampliacion_id": 1,
    "estado": "aprobada",
    "fecha_resolucion": "2026-05-02 09:30:00",
    "justificacion": "Solicitud aprobada...",
    "resolutor": { "usuario_id": 6, "nombre": "Encargado AcreditaciÃ³n Test" }
  }
}
```

---

### 4.6 Rechazar solicitud

```http
POST {{base_url}}/solicitudes-ampliacion/{{solicitud_tradicional_id}}/rechazar
Authorization: Bearer {{token_encargado}}
Content-Type: application/json

{
  "justificacion": "La solicitud no cumple con los requisitos mÃ­nimos de documentaciÃ³n segÃºn el reglamento vigente."
}
```

La `justificacion` es **requerida** (mÃ­nimo 10 chars, mÃ¡ximo 500) al rechazar.

**Respuesta 200:**
```json
{
  "message": "Solicitud rechazada.",
  "data": {
    "solicitud_ampliacion_id": 1,
    "estado": "rechazada",
    "fecha_resolucion": "...",
    "justificacion": "La solicitud..."
  }
}
```

---

### 4.7 Crear solicitud como encargado (si rol lo permite)

```http
POST {{base_url}}/solicitudes-ampliacion
Authorization: Bearer {{token_encargado}}
Content-Type: application/json

{
  "evidencia_asignacion_id": 2,
  "motivo": "Solicitud creada por el encargado en nombre del profesor.",
  "fecha_sugerida": "2026-07-15"
}
```

---

## 5. Validaciones y errores comunes

### 5.1 Motivo demasiado corto (<10 chars) â†’ 422

```json
{ "motivo": "Corto" }
```

**Error:** `"El motivo debe tener al menos 10 caracteres para una explicaciÃ³n adecuada."`

---

### 5.2 Fecha en el pasado o igual a hoy â†’ 422

```json
{ "fecha_sugerida": "2024-01-01" }
```

**Error:** `"La fecha sugerida debe ser posterior a hoy."`

---

### 5.3 AsignaciÃ³n de otro usuario â†’ 422

El servicio valida que `elemento_asignacion.usuario_id == auth()->id()`.

---

### 5.4 Solicitud pendiente duplicada â†’ 422

Solo se permite **una** solicitud `pendiente` por asignaciÃ³n. Si ya existe una pendiente para
`elemento_asignacion_id=2`, una segunda POST devuelve:

```json
{
  "errors": {
    "elemento_asignacion_id": ["Ya tiene una solicitud de ampliaciÃ³n pendiente para esta asignaciÃ³n de elemento."]
  }
}
```

---

### 5.5 Actualizar/cancelar/eliminar solicitud no-pendiente â†’ 422

```json
{ "message": "Solo se pueden actualizar solicitudes pendientes" }
```

---

### 5.6 Intentar operar en solicitud ajena â†’ 403

```json
{ "message": "No autorizado" }
```

---

## 6. SeparaciÃ³n de modelos â€” verificaciÃ³n rÃ¡pida

Los dos modelos **no comparten ningÃºn dato**:

| | Tradicional | Flexible |
|---|---|---|
| Tabla | `SOLICITUD_AMPLIACION` | `SOLICITUD_AMPLIACION_ELEMENTO` |
| PK | `solicitud_ampliacion_id` | `solicitud_ampliacion_elemento_id` |
| FK asignaciÃ³n | `evidencia_asignacion_id` | `elemento_asignacion_id` |
| Route profesor | `/solicitudes-ampliacion-tiempo` | `/solicitudes-ampliacion-elemento` |
| Route encargado | `/solicitudes-ampliacion` | _(no tiene endpoint de aprobaciÃ³n separado)_ |
| Controller | `ExtensionTimeRequestController` | `ElementExtensionTimeRequestController` |
| Service | `ExtensionTimeRequestService` | `ElementExtensionTimeRequestService` |
| Model | `ExtensionRequest` | `ElementExtensionRequest` |
| Resource | `ExtensionTimeRequestResource` | `ElementExtensionTimeRequestResource` |
| PrÃ³ximos vencer | `/evidencias/proximas-vencer` | `/proximas-vencer` |

