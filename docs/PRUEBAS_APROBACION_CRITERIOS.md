# 🧪 Guía de Pruebas - Aprobación de Criterios por Bloques (HU-010)

## 📋 Pre-requisitos

Antes de probar, asegúrate de tener:
1. ✅ Base de datos con estructura básica (criterios, evidencias, procesos)
2. ✅ Al menos 1 criterio con evidencias asociadas
3. ✅ Asignaciones de evidencias con estado `completado`

---

## 🚀 Escenarios de Prueba

### **ESCENARIO 1: Aprobar criterio exitosamente**

**Condición:** Todas las evidencias del criterio deben estar completadas.

```http
POST http://localhost:8000/api/criterios/1/aprobar
Content-Type: application/json

{
  "proceso_id": 1,
  "comentario": "Todas las evidencias completas"
}
```

**Respuesta esperada (201):**
```json
{
  "success": true,
  "message": "Criterio aprobado exitosamente.",
  "data": {
    "aprobacion_criterio_id": 1,
    "criterio_id": 1,
    "proceso_id": 1,
    "usuario_id": 1,
    "estado": "aprobado",
    "comentario": "Todas las evidencias completas",
    "created_at": "2025-12-09T15:30:00.000000Z",
    "updated_at": "2025-12-09T15:30:00.000000Z"
  }
}
```

---

### **ESCENARIO 2: Intentar aprobar criterio con evidencias incompletas**

**Condición:** Al menos una evidencia NO está completada.

```http
POST http://localhost:8000/api/criterios/2/aprobar
Content-Type: application/json

{
  "proceso_id": 1,
  "comentario": "Revisión inicial"
}
```

**Respuesta esperada (400):**
```json
{
  "success": false,
  "message": "No se puede aprobar el criterio. Faltan 2 evidencia(s) por completar."
}
```

---

### **ESCENARIO 3: Rechazar un criterio**

```http
POST http://localhost:8000/api/criterios/1/rechazar
Content-Type: application/json

{
  "proceso_id": 1,
  "comentario": "Falta evidencia E3, corregir formato"
}
```

**Respuesta esperada (201):**
```json
{
  "success": true,
  "message": "Criterio rechazado exitosamente.",
  "data": {
    "aprobacion_criterio_id": 2,
    "criterio_id": 1,
    "proceso_id": 1,
    "usuario_id": 1,
    "estado": "rechazado",
    "comentario": "Falta evidencia E3, corregir formato",
    "created_at": "2025-12-09T16:00:00.000000Z",
    "updated_at": "2025-12-09T16:00:00.000000Z"
  }
}
```

---

### **ESCENARIO 4: Revertir decisión (aprobar después de rechazar)**

```http
# Primero rechazar
POST http://localhost:8000/api/criterios/1/rechazar
{
  "proceso_id": 1,
  "comentario": "Error encontrado"
}

# Luego aprobar (ACTUALIZA el mismo registro)
POST http://localhost:8000/api/criterios/1/aprobar
{
  "proceso_id": 1,
  "comentario": "Corregido, ahora está todo bien"
}
```

**Nota:** El `aprobacion_criterio_id` será el mismo, pero `updated_at` cambiará.

---

### **ESCENARIO 5: Listar todas las aprobaciones**

```http
GET http://localhost:8000/api/aprobaciones-criterios
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "data": [
    {
      "aprobacion_criterio_id": 1,
      "criterio_id": 1,
      "proceso_id": 1,
      "usuario_id": 1,
      "estado": "aprobado",
      "comentario": "Todas completas",
      "created_at": "2025-12-09T15:30:00.000000Z",
      "updated_at": "2025-12-09T15:30:00.000000Z",
      "criterion": { "criterio_id": 1, "nomenclatura": "C1" },
      "process": { "proceso_id": 1, "nombre": "Proceso 2024" },
      "user": { "usuario_id": 1, "nombre": "Admin" }
    }
  ]
}
```

---

### **ESCENARIO 6: Ver una aprobación específica**

```http
GET http://localhost:8000/api/aprobaciones-criterios/1
```

---

### **ESCENARIO 7: Validación - Proceso inexistente**

```http
POST http://localhost:8000/api/criterios/1/aprobar
{
  "proceso_id": 9999,
  "comentario": "Test"
}
```

**Respuesta esperada (422):**
```json
{
  "success": false,
  "message": "Errores de validación.",
  "errors": {
    "proceso_id": ["El proceso especificado no existe."]
  }
}
```

---

### **ESCENARIO 8: Validación - Criterio inexistente**

```http
POST http://localhost:8000/api/criterios/9999/aprobar
{
  "proceso_id": 1,
  "comentario": "Test"
}
```

**Respuesta esperada (404):**
```json
{
  "success": false,
  "message": "El criterio especificado no existe."
}
```

---

### **ESCENARIO 9: Validación - Comentario muy largo**

```http
POST http://localhost:8000/api/criterios/1/aprobar
{
  "proceso_id": 1,
  "comentario": "Este comentario tiene más de 100 caracteres y debería fallar la validación porque excede el límite..."
}
```

**Respuesta esperada (422):**
```json
{
  "success": false,
  "message": "Errores de validación.",
  "errors": {
    "comentario": ["El comentario no puede exceder 100 caracteres."]
  }
}
```

---

## 🔍 Verificación en Base de Datos

Después de cada prueba, ejecuta:

```sql
-- Ver todas las aprobaciones
SELECT * FROM APROBACION_CRITERIO;

-- Ver aprobación con detalles
SELECT 
    ac.aprobacion_criterio_id,
    c.nomenclatura AS criterio,
    ac.estado,
    ac.comentario,
    ac.created_at,
    ac.updated_at,
    TIMESTAMPDIFF(SECOND, ac.created_at, ac.updated_at) AS segundos_diferencia
FROM APROBACION_CRITERIO ac
JOIN CRITERIO c ON ac.criterio_id = c.criterio_id;
```

Si `created_at` ≠ `updated_at`, significa que la decisión fue **revertida**.

---

## ✅ Checklist de Pruebas

- [ ] Aprobar criterio con todas las evidencias completadas
- [ ] Intentar aprobar criterio con evidencias incompletas (debe fallar)
- [ ] Rechazar un criterio
- [ ] Revertir decisión: rechazar → aprobar
- [ ] Revertir decisión: aprobar → rechazar
- [ ] Listar todas las aprobaciones
- [ ] Ver una aprobación específica
- [ ] Validar criterio inexistente
- [ ] Validar proceso inexistente
- [ ] Validar comentario > 100 caracteres
- [ ] Verificar que `created_at` y `updated_at` funcionan correctamente
