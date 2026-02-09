# 📋 RF-15: Solicitud de Ampliación de Tiempo - PROFESOR

## 📝 Descripción
Como profesor, puedo solicitar una ampliación de tiempo para subir archivos de evidencias, justificando atrasos y proponiendo un nuevo plazo.

---

## 🏗️ Arquitectura Implementada

### **Componentes Creados:**
- ✅ `ExtensionTimeRequestController` - Endpoints del profesor
- ✅ `ExtensionTimeRequestService` - Lógica de negocio
- ✅ `StoreExtensionTimeRequestRequest` - Validaciones para crear
- ✅ `UpdateExtensionTimeRequestRequest` - Validaciones para editar
- ✅ `ExtensionTimeRequestResource` - Formato respuestas JSON
- ✅ Modelo `ExtensionRequest` actualizado

### **Tabla Compartida:**
- `SOLICITUD_AMPLIACION` (usada por RF-15 y RF-16)

---

## 🌐 Endpoints Disponibles

### **1. Listar mis solicitudes**
```http
GET /api/solicitudes-ampliacion-tiempo
```

**Filtros opcionales:**
- `estado`: pendiente|aprobada|rechazada|cancelada
- `evidencia_asignacion_id`: ID de la asignación
- `fecha_desde`: YYYY-MM-DD
- `fecha_hasta`: YYYY-MM-DD
- `per_page`: registros por página (default 15, max 100)
- `incluir_canceladas`: true|false (default false - NO muestra canceladas)

**Respuesta 200:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "total": 10,
    ...
  }
}
```

---

### **2. Ver detalle de una solicitud**
```http
GET /api/solicitudes-ampliacion-tiempo/{id}
```

**Respuesta 200:**
```json
{
  "data": {
    "solicitud_ampliacion_id": 1,
    "motivo": "Razones del atraso...",
    "estado": "pendiente",
    "fecha_solicitud": "2026-01-13 10:30:00",
    "evidencia_asignacion": {...}
  }
}
```

---

### **3. Crear solicitud de ampliación**
```http
POST /api/solicitudes-ampliacion-tiempo
```

**Body:**
```json
{
  "evidencia_asignacion_id": 5,
  "motivo": "Explico detalladamente las razones del atraso...",
  "fecha_sugerida": "2026-02-15"
}
```

**Validaciones:**
- `evidencia_asignacion_id`: requerido, debe existir
- `motivo`: requerido, 10-1000 caracteres
- `fecha_sugerida`: requerido, debe ser fecha futura

**Validaciones de negocio:**
- ✅ Usuario debe estar asignado a la evidencia
- ✅ No puede tener solicitud pendiente previa
- ✅ Evidencia no puede estar completada
- ✅ Fecha sugerida > fecha límite original
- ✅ Ampliación máxima: 30 días

**Respuesta 201:**
```json
{
  "message": "Solicitud de ampliación creada exitosamente.",
  "data": {...}
}
```

---

### **4. Editar solicitud pendiente**
```http
PUT /api/solicitudes-ampliacion-tiempo/{id}
```

**Body:**
```json
{
  "motivo": "Actualizo el motivo de mi solicitud...",
  "fecha_sugerida": "2026-02-20"
}
```

**Validaciones:**
- `motivo`: opcional, si se envía debe tener 10-1000 caracteres
- `fecha_sugerida`: opcional, si se envía debe ser fecha futura
- `evidencia_asignacion_id`: **PROHIBIDO** cambiar

**Validaciones de negocio:**
- ✅ Solo el profesor dueño puede editar
- ✅ Solo se pueden editar solicitudes en estado "pendiente"
- ✅ NO se puede cambiar la evidencia asignada
- ✅ Fecha sugerida > fecha límite original
- ✅ Ampliación máxima: 30 días

**Respuesta 200:**
```json
{
  "message": "Solicitud actualizada exitosamente.",
  "data": {...}
}
```

**Errores 422:**
```json
{
  "message": "No se puede actualizar la solicitud.",
  "errors": {
    "solicitud": ["Solo se pueden editar solicitudes pendientes."]
  }
}
```

---

### **5. Cancelar solicitud pendiente**
```http
DELETE /api/solicitudes-ampliacion-tiempo/{id}
```

**Reglas:**
- Solo solicitudes propias
- Solo estado "pendiente"
- Cambia estado a "cancelada"

**Respuesta 200:**
```json
{
  "message": "Solicitud cancelada exitosamente.",
  "data": {...}
}
```

---

### **6. Ver evidencias próximas a vencer**
```http
GET /api/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer
```

**Criterio:** Evidencias con fecha límite en los próximos 7 días o ya vencidas.

**Respuesta 200:**
```json
{
  "data": [...],
  "total": 3,
  "message": "Evidencias próximas a vencer o ya vencidas."
}
```

---

## 🔒 Estados de Solicitud

- `pendiente` - Recién creada, esperando revisión
- `aprobada` - Aprobada por encargado (RF-16)
- `rechazada` - Rechazada por encargado (RF-16)
- `cancelada` - Cancelada por el profesor (RF-15)

**IMPORTANTE:** Por defecto, las solicitudes canceladas NO se muestran en las listas (filtro automático).

---

## 🗄️ Campos de la Tabla

### **SOLICITUD_AMPLIACION**

```sql
- solicitud_ampliacion_id (PK)
- evidencia_asignacion_id (FK)
- usuario_id (FK - solicitante)
- motivo (VARCHAR 1000)
- fecha_sugerida (DATETIME)
- estado (VARCHAR 30) - pendiente|aprobada|rechazada|cancelada
- fecha_resolucion (DATETIME nullable)
- usuario_resolutor_id (FK nullable)
- justificacion (VARCHAR 500 nullable)
- created_at (TIMESTAMP) - Fecha de solicitud
- updated_at (TIMESTAMP)
```

**Nota:** Se usa `created_at` como fecha de solicitud (automático de Laravel).

---

## 🚀 Próximos Pasos

```bash
# 1. Ejecutar migraciones
php artisan migrate

# 2. Probar endpoints
POST /api/solicitudes-ampliacion-tiempo
GET /api/solicitudes-ampliacion-tiempo
```

---

## ✅ Cambios Aplicados

- ✅ Eliminado campo `fecha_solicitud` → usar `created_at`
- ✅ Eliminado campo `activo` → usar `estado='cancelada'`
- ✅ Rutas cambiadas a `/api/solicitudes-ampliacion-tiempo/*`
- ✅ Por defecto NO mostrar canceladas (scope `noCanceladas()`)
- ✅ Una sola tabla compartida entre RF-15 y RF-16
