# Notificaciones — Modelo Flexible (ElementApprovalService)

## Qué se hizo

Se agregaron notificaciones automáticas al `ElementApprovalService`, equivalentes a lo que Naydelin implementó en `CriterionApprovalService` para el modelo tradicional.

### Archivos modificados

| Archivo | Cambio |
|---|---|
| `app/Models/Notification.php` | +2 constantes: `TIPO_APROBACION_ELEMENTO`, `TIPO_RECHAZO_ELEMENTO` |
| `app/Services/NotificationService.php` | `TIPO_RECHAZO_ELEMENTO` agregado a eventos críticos → canal email+app |
| `app/Services/ElementApprovalService.php` | +imports de `Notification` y `NotificationService`, +4 bloques de notificación |
| `database/migrations/2026_04_05_142059_add_elemento_approval_types_to_notificacion_tipo_evento.php` | Migración que agrega los nuevos valores al ENUM `tipo_evento` de la tabla `NOTIFICACION` en MySQL |

### Por qué fue necesaria la migración

La columna `tipo_evento` en la tabla `NOTIFICACION` es de tipo **ENUM** en MySQL — solo acepta valores predefinidos. Sin esta migración, al intentar insertar `rechazo_elemento` MySQL lo rechazaba con error *"Data truncated"*.

Los valores agregados al ENUM:
- `rechazo_criterio` (lo había creado Naydelin en el modelo PHP pero faltaba en la BD)
- `aprobacion_elemento` ← nuevo
- `rechazo_elemento` ← nuevo

### Reglas de canal

| Tipo | Canal |
|---|---|
| `TIPO_APROBACION_ELEMENTO` | Solo app (buena noticia, no urgente) |
| `TIPO_RECHAZO_ELEMENTO` | Email + app (requiere acción del profesor) |

### Los 4 métodos con notificaciones nuevas

| Método | Qué hace | A quién notifica |
|---|---|---|
| `approveElemento(padreId)` | Aprueba pauta + todas sus fuentes en cascada | A cada usuario asignado a cada elemento aprobado |
| `rejectElemento(padreId)` | Rechaza pauta + todas sus fuentes en cascada | A cada usuario asignado, con comentario + nueva fecha |
| `approveIndividualChild(padreId, hijoId)` | Aprueba una fuente individual | Al usuario asignado a esa fuente |
| `rejectIndividualChild(padreId, hijoId)` | Rechaza una fuente individual | Al usuario asignado, con comentario + nueva fecha |

> Las notificaciones van **fuera de la transacción DB** — si falla el envío del correo, la aprobación/rechazo NO se revierte.

---

## Cómo probar en Postman

### Datos reales de la BD (para las pruebas)

- `proceso_id`: **33**
- `elemento padre`: **19** (AG-01 — Area de Gestion Academica)
- `elemento hijo`: **20** (AG-01.1 — Componente de planificacion curricular)
- `usuario asignado`: **56** y **57**

### Paso 1 — Autenticarse

```
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "username": "<usuario_coordinador_o_evaluador>",
  "password": "<password>"
}
```

Copiar el `token` de la respuesta y usarlo como `Bearer Token` en los siguientes requests.

---

### Prueba 1 — Aprobar bloque completo (pauta + hijos en cascada)

```
POST http://localhost:8000/api/elementos/19/aprobar
Authorization: Bearer <token>
Content-Type: application/json

{
  "proceso_id": 33,
  "comentario": "Todo correcto"
}
```

**Resultado esperado:**
- Status `200`
- En tabla `NOTIFICACION`: registro con `tipo_evento = 'aprobacion_elemento'` para usuarios 56 y 57
- Canal: solo interno (app)

---

### Prueba 2 — Rechazar bloque completo (con fecha y comentario)

```
POST http://localhost:8000/api/elementos/19/rechazar
Authorization: Bearer <token>
Content-Type: application/json

{
  "proceso_id": 33,
  "comentario": "Falta documentación de respaldo",
  "fecha_limite": "2026-05-15"
}
```

**Resultado esperado:**
- Status `200`
- En tabla `NOTIFICACION`: registro con `tipo_evento = 'rechazo_elemento'` para usuarios 56 y 57
- Canal: `ambos` (email + app)
- El mensaje incluye el comentario y la nueva fecha

---

### Prueba 3 — Aprobar un hijo individual

```
POST http://localhost:8000/api/elementos/19/hijos/20/aprobar
Authorization: Bearer <token>
Content-Type: application/json

{
  "proceso_id": 33
}
```

**Resultado esperado:**
- Status `200`
- Notificación interna (solo app) al usuario asignado al elemento 20

---

### Prueba 4 — Rechazar un hijo individual

```
POST http://localhost:8000/api/elementos/19/hijos/20/rechazar
Authorization: Bearer <token>
Content-Type: application/json

{
  "proceso_id": 33,
  "comentario": "El componente no cumple los criterios mínimos",
  "nueva_fecha_limite": "2026-05-20"
}
```

**Resultado esperado:**
- Status `200`
- Notificación por email + app al usuario asignado al elemento 20
- Mensaje con comentario y nueva fecha

---

### Verificar en BD después de cada prueba

```sql
SELECT notificacion_id, usuario_id, tipo_evento, canal, titulo, mensaje, created_at
FROM NOTIFICACION
ORDER BY created_at DESC
LIMIT 5;
```
