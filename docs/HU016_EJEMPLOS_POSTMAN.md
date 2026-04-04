# HU-016: Ejemplos para Postman - Solicitudes de Ampliación

## 📋 Configuración Inicial

### Headers para todas las peticiones:
```
Content-Type: application/json
Accept: application/json
```

> **Arquitectura actual (post refactorización SOLID):**  
> Existen **dos endpoints** para crear solicitudes de ampliación según el modelo de negocio:
> - **Modelo Tradicional** (`evidencia_asignacion_id`) → `POST /api/solicitudes-ampliacion`
> - **Modelo Flexible** (`elemento_asignacion_id`) → `POST /api/elementos-asignaciones/{id}/solicitud-ampliacion`

---

## 1️⃣ **Crear Solicitud — Modelo Tradicional**

**Endpoint:** `POST http://localhost:8000/api/solicitudes-ampliacion`

**Servicio:** `TradicionalExtensionRequestService::createRequest()`

### Request Body (JSON):
```json
{
  "evidencia_asignacion_id": 1,
  "motivo": "Necesito más tiempo para recopilar la documentación requerida debido a que el departamento administrativo está en período de cierre mensual.",
  "fecha_sugerida": "2026-05-20"
}
```

### Campos obligatorios:
- `evidencia_asignacion_id`: ID de la asignación de evidencia (debe existir en `EVIDENCIA_ASIGNACION`)
- `motivo`: Mínimo 10, máximo **300** caracteres. Solo letras, números y puntuación básica
- `fecha_sugerida`: Fecha futura en formato `YYYY-MM-DD` (estrictamente después de hoy)

### Respuesta exitosa (201):
```json
{
  "message": "Solicitud de ampliación creada correctamente.",
  "data": {
    "solicitud_ampliacion_id": 1,
    "evidencia_asignacion_id": 1,
    "elemento_asignacion_id": null,
    "usuario_id": 5,
    "fecha_solicitud": "2026-03-31T03:45:00.000000Z",
    "motivo": "Necesito más tiempo para recopilar la documentación...",
    "fecha_sugerida": "2026-05-20T00:00:00.000000Z",
    "estado": "pendiente",
    "fecha_resolucion": null,
    "usuario_resolutor_id": null,
    "justificacion": null,
    "evidencia_asignacion": {
      "evidencia_asignacion_id": 1,
      "evidencia_id": 10,
      "estado": "pendiente",
      "fecha_limite": "2026-04-15T00:00:00.000000Z"
    },
    "elemento_asignacion": null,
    "usuario": {
      "usuario_id": 5,
      "nombre": "María González",
      "email": "maria@example.com"
    },
    "resolutor": null
  }
}
```

### 🔔 **Notificación enviada:**
- Se envía email a todos los `Encargado de Acreditacion` de la carrera
- Revisa `storage/logs/laravel.log` si `MAIL_MAILER=log`

---

## 1️⃣b **Crear Solicitud — Modelo Flexible**

**Endpoint:** `POST http://localhost:8000/api/elementos-asignaciones/{id}/solicitud-ampliacion`

**Servicio:** `FlexibleExtensionRequestService::createRequest()`

El `{id}` de la URL es el `elemento_asignacion_id`. No se envía en el body.

### Request Body (JSON):
```json
{
  "motivo": "Necesito más tiempo para completar los documentos asignados al elemento.",
  "fecha_sugerida": "2026-05-20"
}
```

### Campos obligatorios:
- `motivo`: Mínimo **10**, máximo **1000** caracteres
- `fecha_sugerida`: Fecha futura en formato `YYYY-MM-DD`, debe ser estrictamente posterior a la `fecha_limite` actual de la asignación, y no puede exceder **30 días** desde esa fecha límite

### Validaciones adicionales del servicio:
- El usuario autenticado debe ser el dueño de la asignación (`usuario_id`)
- No puede existir una solicitud `pendiente` para esa misma asignación

### Respuesta exitosa (201):
```json
{
  "data": {
    "solicitud_ampliacion_id": 3,
    "evidencia_asignacion_id": null,
    "elemento_asignacion_id": 2,
    "usuario_id": 5,
    "motivo": "Necesito más tiempo para completar los documentos...",
    "fecha_sugerida": "2026-05-20T00:00:00.000000Z",
    "estado": "pendiente",
    "fecha_resolucion": null,
    "usuario_resolutor_id": null,
    "justificacion": null,
    "evidencia_asignacion": null,
    "elemento_asignacion": {
      "elemento_asignacion_id": 2,
      "fecha_limite": "2026-04-15T00:00:00.000000Z",
      "estado": "pendiente"
    },
    "usuario": {
      "usuario_id": 5,
      "nombre": "María González",
      "email": "maria@example.com"
    },
    "resolutor": null
  }
}
```

### 🔔 **Notificación enviada:**
- Misma lógica que el modelo tradicional: email a encargados + `event(ExtensionRequestCreated)` para notificaciones internas

---

## 2️⃣ **Ver Mis Solicitudes**

**Endpoint:** `GET http://localhost:8000/api/solicitudes-ampliacion/mis-solicitudes`

### Query params opcionales:
- `estado`: `pendiente`, `aprobada` o `rechazada`
- `evidencia_asignacion_id`: ID de la asignación específica
- `fecha_desde`: Fecha desde (YYYY-MM-DD)
- `fecha_hasta`: Fecha hasta (YYYY-MM-DD)
- `per_page`: Registros por página (default 15, max 100)
- `page`: Número de página

### Ejemplos de uso:
```
GET /api/solicitudes-ampliacion/mis-solicitudes
GET /api/solicitudes-ampliacion/mis-solicitudes?estado=pendiente
GET /api/solicitudes-ampliacion/mis-solicitudes?per_page=25&page=2
GET /api/solicitudes-ampliacion/mis-solicitudes?fecha_desde=2025-01-01&fecha_hasta=2025-12-31
GET /api/solicitudes-ampliacion/mis-solicitudes?evidencia_asignacion_id=5&estado=aprobada
```

### Respuesta exitosa (200) - Con paginación:
```json
{
  "data": [
    {
      "solicitud_ampliacion_id": 1,
      "evidencia_asignacion_id": 1,
      "usuario_id": 5,
      "estado": "pendiente",
      "motivo": "Necesito más tiempo...",
      "fecha_solicitud": "2025-12-09T03:45:00.000000Z",
      "fecha_sugerida": "2025-12-20T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 42
  },
  "links": {
    "first": "http://localhost:8000/api/solicitudes-ampliacion/mis-solicitudes?page=1",
    "last": "http://localhost:8000/api/solicitudes-ampliacion/mis-solicitudes?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/solicitudes-ampliacion/mis-solicitudes?page=2"
  }
}
```

---

## 3️⃣ **Ver Solicitudes Pendientes (Solo Encargados)**

**Endpoint:** `GET http://localhost:8000/api/solicitudes-ampliacion/pendientes`

### Requiere:
- Usuario con rol `encargado_acreditacion` o `admin`

### Query params opcionales:
- `usuario_id`: Filtrar por usuario solicitante
- `evidencia_asignacion_id`: ID de la asignación específica
- `fecha_desde`: Fecha desde (YYYY-MM-DD)
- `fecha_hasta`: Fecha hasta (YYYY-MM-DD)
- `per_page`: Registros por página (default 15, max 100)
- `page`: Número de página

### Ejemplos de uso:
```
GET /api/solicitudes-ampliacion/pendientes
GET /api/solicitudes-ampliacion/pendientes?usuario_id=5
GET /api/solicitudes-ampliacion/pendientes?per_page=50
GET /api/solicitudes-ampliacion/pendientes?fecha_desde=2025-12-01&per_page=25&page=1
```

### Respuesta exitosa (200) - Con paginación:
```json
{
  "data": [
    {
      "solicitud_ampliacion_id": 1,
      "estado": "pendiente",
      "fecha_solicitud": "2025-12-09T03:45:00.000000Z",
      "usuario": {
        "usuario_id": 5,
        "nombre": "María González",
        "email": "maria@example.com"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 2,
    "per_page": 15,
    "to": 15,
    "total": 28
  },
  "links": {
    "first": "http://localhost:8000/api/solicitudes-ampliacion/pendientes?page=1",
    "last": "http://localhost:8000/api/solicitudes-ampliacion/pendientes?page=2",
    "prev": null,
    "next": "http://localhost:8000/api/solicitudes-ampliacion/pendientes?page=2"
  }
}
```

---

## 4️⃣ **Ver Detalle de una Solicitud**

**Endpoint:** `GET http://localhost:8000/api/solicitudes-ampliacion/1`

### Respuesta exitosa (200):
```json
{
  "data": {
    "solicitud_ampliacion_id": 1,
    "evidencia_asignacion_id": 1,
    "usuario_id": 5,
    "fecha_solicitud": "2025-12-09T03:45:00.000000Z",
    "motivo": "Necesito más tiempo...",
    "fecha_sugerida": "2025-12-20T00:00:00.000000Z",
    "estado": "pendiente",
    "evidencia_asignacion": {
      "evidencia_asignacion_id": 1,
      "evidencia_id": 10,
      "estado": "pendiente",
      "fecha_limite": "2025-12-15T00:00:00.000000Z"
    },
    "usuario": {
      "usuario_id": 5,
      "nombre": "María González"
    }
  }
}
```

---

## 5️⃣ **Aprobar Solicitud (Solo Encargados)**

**Endpoint:** `POST http://localhost:8000/api/solicitudes-ampliacion/1/aprobar`

### Requiere:
- Usuario con rol `encargado_acreditacion` o `admin`

### Request Body (JSON) — opcional:
```json
{
  "justificacion": "Se aprueba la extensión considerando la situación administrativa reportada."
}
```

### Campos:
- `justificacion`: Opcional al aprobar. Mínimo 10, máximo 500 caracteres

### Respuesta exitosa (200):
```json
{
  "message": "Solicitud aprobada correctamente.",
  "data": {
    "solicitud_ampliacion_id": 1,
    "estado": "aprobada",
    "fecha_resolucion": "2026-03-31T04:00:00.000000Z",
    "usuario_resolutor_id": 2,
    "justificacion": "Se aprueba la extensión considerando...",
    "evidencia_asignacion": {
      "evidencia_asignacion_id": 1,
      "fecha_limite": "2026-05-20T00:00:00.000000Z"
    },
    "elemento_asignacion": null,
    "resolutor": {
      "usuario_id": 2,
      "nombre": "Carlos Pérez",
      "email": "carlos@example.com"
    }
  }
}
```

### ⚠️ **Efecto (patrón XOR):**
- Si la solicitud es del **modelo tradicional**: se actualiza `EVIDENCIA_ASIGNACION.fecha_limite`
- Si la solicitud es del **modelo flexible**: se actualiza `ELEMENTO_ASIGNACION.fecha_limite`
- Nunca se actualizan ambas a la vez

---

## 6️⃣ **Rechazar Solicitud (Solo Encargados)**

**Endpoint:** `POST http://localhost:8000/api/solicitudes-ampliacion/1/rechazar`

### Requiere:
- Usuario con rol `encargado_acreditacion` o `admin`

### Request Body (JSON):
```json
{
  "justificacion": "La fecha solicitada excede el plazo máximo permitido por la política de acreditación."
}
```

### Campos:
- `justificacion`: **OBLIGATORIA** al rechazar, mínimo **10**, máximo **500** caracteres

### Respuesta exitosa (200):
```json
{
  "message": "Solicitud rechazada correctamente.",
  "data": {
    "solicitud_ampliacion_id": 1,
    "estado": "rechazada",
    "fecha_resolucion": "2026-03-31T04:10:00.000000Z",
    "usuario_resolutor_id": 2,
    "justificacion": "La fecha solicitada excede...",
    "evidencia_asignacion": { "fecha_limite": "2026-04-15T00:00:00.000000Z" },
    "elemento_asignacion": null,
    "resolutor": {
      "usuario_id": 2,
      "nombre": "Carlos Pérez"
    }
  }
}
```

### ⚠️ **Efecto:**
- La `fecha_limite` **no cambia** en ninguno de los dos modelos

---

## 7️⃣ **Ver Todas las Solicitudes (Solo Encargados)**

**Endpoint:** `GET http://localhost:8000/api/solicitudes-ampliacion`

### Requiere:
- Usuario con rol `encargado_acreditacion` o `admin`

### Query params opcionales:
- `estado`: `pendiente`, `aprobada` o `rechazada`
- `usuario_id`: Filtrar por usuario solicitante
- `evidencia_asignacion_id`: ID de la asignación específica
- `fecha_desde`: Fecha desde (YYYY-MM-DD)
- `fecha_hasta`: Fecha hasta (YYYY-MM-DD)
- `per_page`: Registros por página (default 15, max 100)
- `page`: Número de página

### Ejemplos de uso:
```
GET /api/solicitudes-ampliacion
GET /api/solicitudes-ampliacion?estado=pendiente
GET /api/solicitudes-ampliacion?usuario_id=5&estado=aprobada
GET /api/solicitudes-ampliacion?fecha_desde=2025-01-01&fecha_hasta=2025-12-31&per_page=50
GET /api/solicitudes-ampliacion?evidencia_asignacion_id=3&page=2
```

### Respuesta exitosa (200) - Con paginación:
```json
{
  "data": [
    {
      "solicitud_ampliacion_id": 1,
      "estado": "aprobada",
      "fecha_solicitud": "2025-12-09T03:45:00.000000Z"
    },
    {
      "solicitud_ampliacion_id": 2,
      "estado": "pendiente",
      "fecha_solicitud": "2025-12-09T05:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  },
  "links": {
    "first": "http://localhost:8000/api/solicitudes-ampliacion?page=1",
    "last": "http://localhost:8000/api/solicitudes-ampliacion?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/solicitudes-ampliacion?page=2"
  }
}
```

---

## 🔐 **Autenticación y Autorización**

### Políticas de acceso:

| Acción | Cualquier Usuario | Encargado/Admin |
|--------|-------------------|-----------------|
| Crear solicitud | ✅ (solo para sus asignaciones) | ✅ |
| Ver mis solicitudes | ✅ | ✅ |
| Ver todas las solicitudes | ❌ | ✅ |
| Ver pendientes | ❌ | ✅ |
| Aprobar | ❌ | ✅ |
| Rechazar | ❌ | ✅ |

---

## ⚠️ **Validaciones y Errores**

### Error 422 — Validación fallida (tradicional):
```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "motivo": ["El motivo debe tener al menos 10 caracteres."],
    "fecha_sugerida": ["La fecha sugerida debe ser posterior a hoy."],
    "evidencia_asignacion_id": ["La asignación de evidencia no existe."]
  }
}
```

### Error 422 — Validación del servicio (flexible — reglas de negocio):
```json
{
  "message": "Ya existe una solicitud de ampliación pendiente para esta asignación."
}
```
```json
{
  "message": "La fecha sugerida debe ser posterior a la fecha límite actual (15/04/2026)."
}
```
```json
{
  "message": "La ampliación no puede exceder 30 días desde la fecha límite actual."
}
```

### Error 400 — Lógica de resolución (aprobar/rechazar):
```json
{
  "message": "Error al aprobar la solicitud.",
  "error": "Solo se pueden aprobar solicitudes pendientes."
}
```

### Error 403 — Sin autorización:
```json
{
  "message": "This action is unauthorized."
}
```

### Error 404 — No encontrado:
```json
{
  "message": "Solicitud no encontrada."
}
```

---

## 📧 **Verificar Notificaciones**

Después de crear una solicitud, verifica el email en:
```
storage/logs/laravel.log
```

Busca por: `"Nueva Solicitud de Ampliación - SAAC"`

---

## 🧪 **Pasos para Probar Flujo Completo**

### Flujo Tradicional (modelo evidencia):
1. Verificar que exista una `EVIDENCIA_ASIGNACION` con un `evidencia_asignacion_id` válido
2. **Crear solicitud** con `POST /api/solicitudes-ampliacion` como usuario normal
3. Verificar en logs el email enviado a encargados
4. **Listar pendientes** con `GET /api/solicitudes-ampliacion/pendientes` como encargado
5. **Aprobar** con `POST /api/solicitudes-ampliacion/{id}/aprobar`
6. Verificar que `EVIDENCIA_ASIGNACION.fecha_limite` se actualizó a `fecha_sugerida`

### Flujo Flexible (modelo elemento):
1. Verificar que exista un `ELEMENTO_ASIGNACION` con `elemento_asignacion_id` válido asignado al usuario autenticado
2. **Crear solicitud** con `POST /api/elementos-asignaciones/{id}/solicitud-ampliacion`
3. Verificar en logs el email enviado a encargados
4. **Listar pendientes** con `GET /api/solicitudes-ampliacion/pendientes` como encargado (aparece junto a las tradicionales)
5. **Aprobar** con `POST /api/solicitudes-ampliacion/{id}/aprobar`
6. Verificar que `ELEMENTO_ASIGNACION.fecha_limite` se actualizó a `fecha_sugerida`

---

## 🔧 **Troubleshooting**

### Si no funciona la notificación:
1. Verifica que `MAIL_MAILER=log` o `MAIL_MAILER=smtp` en `.env`
2. Verifica que existan usuarios con rol `Encargado de Acreditacion` en la BD
3. Revisa `storage/logs/laravel.log` para errores
4. El email solo se envía a encargados con carrera asociada; si no hay coincidencia por carrera, se envía a todos los encargados del sistema

### Si no puedes crear solicitud (tradicional):
1. Verifica que la `evidencia_asignacion_id` existe
2. Verifica que el usuario autenticado es el asignado
3. Verifica que no hay solicitud pendiente para esa asignación
