# HU-016: Ejemplos para Postman - Solicitudes de Ampliación

## 📋 Configuración Inicial

### Headers para todas las peticiones:
```
Content-Type: application/json
Accept: application/json
```

---

## 1️⃣ **Crear Solicitud de Ampliación**

**Endpoint:** `POST http://localhost:8000/api/solicitudes-ampliacion`

### Request Body (JSON):
```json
{
  "evidencia_asignacion_id": 1,
  "motivo": "Necesito más tiempo para recopilar la documentación requerida debido a que el departamento administrativo está en período de cierre mensual.",
  "fecha_sugerida": "2025-12-20"
}
```

### Campos obligatorios:
- `evidencia_asignacion_id`: ID de la asignación de evidencia (debe existir)
- `motivo`: Mínimo 10 caracteres, máximo 300
- `fecha_sugerida`: Fecha futura en formato YYYY-MM-DD

### Respuesta exitosa (201):
```json
{
  "message": "Solicitud de ampliación creada correctamente.",
  "data": {
    "solicitud_ampliacion_id": 1,
    "evidencia_asignacion_id": 1,
    "usuario_id": 5,
    "fecha_solicitud": "2025-12-09T03:45:00.000000Z",
    "motivo": "Necesito más tiempo para recopilar la documentación...",
    "fecha_sugerida": "2025-12-20T00:00:00.000000Z",
    "estado": "pendiente",
    "fecha_resolucion": null,
    "usuario_resolutor_id": null,
    "justificacion": null,
    "created_at": "2025-12-09T03:45:00.000000Z",
    "updated_at": "2025-12-09T03:45:00.000000Z",
    "evidencia_asignacion": {
      "evidencia_asignacion_id": 1,
      "evidencia_id": 10,
      "estado": "pendiente",
      "fecha_limite": "2025-12-15T00:00:00.000000Z"
    },
    "usuario": {
      "usuario_id": 5,
      "nombre": "María González",
      "email": "maria@example.com"
    },
    "resolutor": {
      "usuario_id": null,
      "nombre": null,
      "email": null
    }
  }
}
```

### 🔔 **Notificación enviada:**
- Se envía email a todos los `encargado_acreditacion`
- Revisa `storage/logs/laravel.log` para ver el contenido

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

### Request Body (JSON):
```json
{
  "justificacion": "Se aprueba la extensión considerando la situación administrativa reportada."
}
```

### Campos:
- `justificacion`: Opcional al aprobar, máximo 500 caracteres

### Respuesta exitosa (200):
```json
{
  "message": "Solicitud aprobada correctamente.",
  "data": {
    "solicitud_ampliacion_id": 1,
    "estado": "aprobada",
    "fecha_resolucion": "2025-12-09T04:00:00.000000Z",
    "usuario_resolutor_id": 2,
    "justificacion": "Se aprueba la extensión considerando...",
    "evidencia_asignacion": {
      "evidencia_asignacion_id": 1,
      "fecha_limite": "2025-12-20T00:00:00.000000Z"
    },
    "resolutor": {
      "usuario_id": 2,
      "nombre": "Carlos Pérez",
      "email": "carlos@example.com"
    }
  }
}
```

### ⚠️ **Efecto:**
- La `fecha_limite` de la asignación se actualiza a la fecha sugerida

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
- `justificacion`: **OBLIGATORIA** al rechazar, mínimo 10 caracteres, máximo 500

### Respuesta exitosa (200):
```json
{
  "message": "Solicitud rechazada correctamente.",
  "data": {
    "solicitud_ampliacion_id": 1,
    "estado": "rechazada",
    "fecha_resolucion": "2025-12-09T04:10:00.000000Z",
    "usuario_resolutor_id": 2,
    "justificacion": "La fecha solicitada excede...",
    "resolutor": {
      "usuario_id": 2,
      "nombre": "Carlos Pérez"
    }
  }
}
```

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

### Error 422 - Validación:
```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "motivo": ["El motivo debe tener al menos 10 caracteres."],
    "fecha_sugerida": ["La fecha sugerida debe ser posterior a hoy."]
  }
}
```

### Error 400 - Lógica de negocio:
```json
{
  "message": "Error al crear la solicitud.",
  "error": "Ya existe una solicitud pendiente para esta asignación."
}
```

### Error 403 - Sin autorización:
```json
{
  "message": "This action is unauthorized."
}
```

### Error 404 - No encontrado:
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

1. **Crear asignación de evidencia** (si no existe)
2. **Crear solicitud** como usuario normal
3. **Ver en logs** el email enviado a encargados
4. **Listar pendientes** como encargado
5. **Aprobar o rechazar** la solicitud
6. **Verificar** que la fecha_limite se actualizó (si aprobada)

---

## 🔧 **Troubleshooting**

### Si no funciona la notificación:
1. Verifica que `MAIL_MAILER=log` en `.env`
2. Verifica que existan usuarios con rol `encargado_acreditacion`
3. Revisa `storage/logs/laravel.log` para errores

### Si no puedes crear solicitud:
1. Verifica que la `evidencia_asignacion_id` existe
2. Verifica que el usuario autenticado es el asignado
3. Verifica que no hay solicitud pendiente para esa asignación
