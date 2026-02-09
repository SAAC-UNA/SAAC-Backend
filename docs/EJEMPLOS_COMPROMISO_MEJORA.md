# Ejemplos de Uso - Compromiso de Mejora con Asignación por Roles

## 📋 Tabla de Contenidos
1. [Crear Compromiso con Usuarios](#1-crear-compromiso-con-usuarios)
2. [Crear Compromiso con Roles](#2-crear-compromiso-con-roles)
3. [Crear Compromiso Mixto (Usuarios + Roles)](#3-crear-compromiso-mixto)
4. [Actualizar Compromisos](#4-actualizar-compromisos)
5. [Consultar Compromisos](#5-consultar-compromisos)

---

## 1. Crear Compromiso con Usuarios

### Endpoint
```
POST /api/compromisos-mejora
```

### Headers
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer {token}"
}
```

### Body (JSON)
```json
{
  "ciclo_acreditacion_id": 1,
  "descripcion": "Mejorar documentación de procesos académicos",
  "fecha_inicio": "2025-12-21",
  "fecha_fin": "2026-03-31",
  "selecciones": [
    {
      "entidad_tipo": "CRITERIO",
      "entidad_id": 5
    }
  ],
  "evidencias_asignar": [
    {
      "evidencia_id": 10,
      "usuarios": [1, 2, 3],
      "fecha_limite": "2026-02-15",
      "comentario": "Asignación directa a coordinadores"
    }
  ]
}
```

### Respuesta Esperada (201 Created)
```json
{
  "message": "Compromiso de mejora creado exitosamente.",
  "data": {
    "compromiso_mejora_id": 1,
    "proceso_id": 5,
    "descripcion": "Mejorar documentación de procesos académicos",
    "fecha_inicio": "2025-12-21",
    "fecha_fin": "2026-03-31",
    "estado": "Pendiente",
    "activo": true,
    "assignedEvidences": [
      {
        "evidencia_asignacion_id": 10,
        "evidencia_id": 10,
        "usuario_id": 1,
        "estado": "Pendiente",
        "fecha_limite": "2026-02-15",
        "pivot": {
          "comentario": "Asignación directa a coordinadores"
        }
      }
      // ... más asignaciones
    ]
  }
}
```

---

## 2. Crear Compromiso con Roles

### Endpoint
```
POST /api/compromisos-mejora
```

### Body (JSON)
```json
{
  "ciclo_acreditacion_id": 1,
  "descripcion": "Actualizar normativas de evaluación",
  "fecha_inicio": "2025-12-21",
  "fecha_fin": "2026-04-30",
  "selecciones": [
    {
      "entidad_tipo": "DIMENSION",
      "entidad_id": 3
    }
  ],
  "evidencias_asignar": [
    {
      "evidencia_id": 15,
      "roles": [4, 5],
      "fecha_limite": "2026-03-01",
      "comentario": "Asignación para coordinadores y directores"
    }
  ]
}
```

### Comportamiento
- El sistema busca **todos los usuarios activos** que tengan el rol con ID 4 o 5
- Crea una asignación individual para cada usuario encontrado
- Evita duplicados automáticamente

### Respuesta Esperada (201 Created)
```json
{
  "message": "Compromiso de mejora creado exitosamente.",
  "data": {
    "compromiso_mejora_id": 2,
    "descripcion": "Actualizar normativas de evaluación",
    "assignedEvidences": [
      {
        "evidencia_asignacion_id": 20,
        "evidencia_id": 15,
        "usuario_id": 5,
        "usuario": {
          "usuario_id": 5,
          "nombre": "Juan Pérez",
          "roles": ["Coordinador"]
        }
      },
      {
        "evidencia_asignacion_id": 21,
        "evidencia_id": 15,
        "usuario_id": 7,
        "usuario": {
          "usuario_id": 7,
          "nombre": "María González",
          "roles": ["Director"]
        }
      }
      // ... más asignaciones según usuarios con esos roles
    ]
  }
}
```

---

## 3. Crear Compromiso Mixto (Usuarios + Roles)

### Endpoint
```
POST /api/compromisos-mejora
```

### Body (JSON)
```json
{
  "ciclo_acreditacion_id": 1,
  "descripcion": "Revisión integral de evidencias del componente X",
  "fecha_inicio": "2025-12-21",
  "fecha_fin": "2026-06-30",
  "selecciones": [
    {
      "entidad_tipo": "COMPONENTE",
      "entidad_id": 8
    },
    {
      "entidad_tipo": "CRITERIO",
      "entidad_id": 12
    }
  ],
  "evidencias_asignar": [
    {
      "evidencia_id": 20,
      "usuarios": [1, 2],
      "roles": [4],
      "fecha_limite": "2026-05-01",
      "comentario": "Asignación combinada: usuarios específicos + rol coordinador"
    },
    {
      "evidencia_id": 21,
      "roles": [5, 6],
      "fecha_limite": "2026-04-15",
      "comentario": "Solo para directores y evaluadores"
    }
  ]
}
```

### Comportamiento
- **Evidencia 20**: Se asigna a usuarios 1 y 2 directamente + todos los usuarios con rol 4
- **Evidencia 21**: Se asigna a todos los usuarios con rol 5 o 6
- Si un usuario aparece en múltiples asignaciones, solo se crea UNA asignación

---

## 4. Actualizar Compromisos

### 4.1 Actualizar Solo Descripción y Fechas

#### Endpoint
```
PUT /api/compromisos-mejora/{id}
```

#### Body (JSON)
```json
{
  "descripcion": "Descripción actualizada del compromiso",
  "fecha_fin": "2026-07-31"
}
```

### 4.2 Actualizar Asignaciones (Reemplazar Completamente)

#### Endpoint
```
PUT /api/compromisos-mejora/{id}
```

#### Body (JSON)
```json
{
  "evidencias_asignar": [
    {
      "evidencia_id": 20,
      "usuarios": [1, 3, 5],
      "roles": [4, 5],
      "fecha_limite": "2026-06-01"
    }
  ]
}
```

⚠️ **Importante**: Al actualizar asignaciones, se usa `sync()` lo que significa:
- Se **eliminan** todas las asignaciones anteriores vinculadas al compromiso
- Se **crean** las nuevas asignaciones especificadas

### 4.3 Cambiar Estado del Compromiso

#### Endpoint
```
PUT /api/compromisos-mejora/{id}
```

#### Body (JSON)
```json
{
  "estado": "En Progreso"
}
```

Estados válidos:
- `Pendiente`
- `En Progreso`
- `Completado`
- `Vencido`

---

## 5. Consultar Compromisos

### 5.1 Listar Todos los Compromisos (Paginado)

#### Endpoint
```
GET /api/compromisos-mejora?per_page=15&search=documentación
```

#### Parámetros Query
- `per_page`: Cantidad de registros por página (default: 10, max: 50)
- `search`: Búsqueda por descripción
- `estado`: Filtrar por estado (Pendiente, En Progreso, etc.)
- `proceso_id`: Filtrar por proceso
- `usuario_id`: Filtrar compromisos donde el usuario tiene asignaciones

### 5.2 Obtener Compromiso Específico

#### Endpoint
```
GET /api/compromisos-mejora/{id}
```

### 5.3 Obtener Compromisos por Usuario

#### Endpoint
```
GET /api/compromisos-mejora/usuario/{usuario_id}
```

Retorna todos los compromisos donde el usuario tiene al menos una evidencia asignada.

### 5.4 Obtener Compromisos por Evidencia

#### Endpoint
```
GET /api/compromisos-mejora/evidencia/{evidencia_id}
```

Retorna todos los compromisos que incluyen esa evidencia.

---

## 6. Desactivar/Activar Compromiso

### Endpoint
```
PATCH /api/compromisos-mejora/{id}/toggle-active
```

### Body (JSON)
```json
{
  "activo": false
}
```

**Comportamiento**:
- `activo: false` → Oculta el compromiso pero preserva todos los datos
- `activo: true` → Reactiva el compromiso

---

## 7. Casos de Uso Comunes

### Caso 1: Asignar Evidencia a Todo un Departamento

```json
{
  "evidencias_asignar": [
    {
      "evidencia_id": 30,
      "roles": [3],
      "comentario": "Asignado a todo el departamento académico"
    }
  ]
}
```

### Caso 2: Asignar con Fechas Límite Diferentes

```json
{
  "evidencias_asignar": [
    {
      "evidencia_id": 25,
      "usuarios": [1],
      "fecha_limite": "2026-01-15",
      "comentario": "Prioridad alta - Coordinador principal"
    },
    {
      "evidencia_id": 25,
      "roles": [4],
      "fecha_limite": "2026-02-28",
      "comentario": "Prioridad normal - Equipo de apoyo"
    }
  ]
}
```

### Caso 3: Múltiples Evidencias con Asignaciones Diferentes

```json
{
  "evidencias_asignar": [
    {
      "evidencia_id": 10,
      "usuarios": [1],
      "comentario": "Líder del proceso"
    },
    {
      "evidencia_id": 11,
      "roles": [4, 5],
      "comentario": "Equipo de revisión"
    },
    {
      "evidencia_id": 12,
      "usuarios": [1, 2],
      "roles": [6],
      "comentario": "Asignación combinada"
    }
  ]
}
```

---

## 8. Validaciones y Errores Comunes

### Error: Evidencia no vinculada al compromiso

```json
{
  "error": "Validation Error",
  "message": "La evidencia con ID 99 no está vinculada al compromiso de mejora. Solo se pueden asignar evidencias que pertenezcan al compromiso."
}
```

**Solución**: Asegúrate de que la evidencia esté en las `selecciones` del compromiso.

### Error: Duplicado de asignación

```json
{
  "error": "Validation Error",
  "message": "La evidencia con ID 10 ya está asignada al usuario con ID 5 en este proceso. No se pueden crear asignaciones duplicadas."
}
```

**Solución**: El usuario ya tiene esa evidencia asignada. Usa UPDATE si quieres modificar la asignación.

### Error: Rol no existe

```json
{
  "error": "Validation Error",
  "message": "El rol con ID 99 no existe."
}
```

**Solución**: Verifica que el ID del rol sea correcto en la tabla `roles`.

---

## 9. Notas Importantes

### ✅ Buenas Prácticas

1. **Usar roles** para asignaciones grupales (más mantenible)
2. **Combinar usuarios + roles** cuando necesites asignaciones específicas + grupales
3. **Establecer fechas límite** para mejor seguimiento
4. **Agregar comentarios** para contextualizar las asignaciones

### ⚠️ Consideraciones

1. **Solo usuarios activos** con el rol recibirán asignaciones
2. **Sync en UPDATE** elimina asignaciones previas (cuidado al actualizar)
3. **Duplicados automáticos** se omiten silenciosamente en asignación por roles
4. **Transacciones** garantizan atomicidad (todo se crea o nada)

### 🔒 Seguridad

- Validar permisos antes de crear/actualizar compromisos
- Solo usuarios autorizados deben poder asignar evidencias
- Verificar que los usuarios y roles pertenezcan a la organización

---

## 10. Diferencias con el Sistema Anterior

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| Asignación | Solo usuarios individuales | Usuarios + Roles |
| Estructura | `usuario_id` | `usuarios[]`, `roles[]` |
| Duplicados | Error inmediato | Omisión silenciosa en roles |
| Flexibilidad | Baja | Alta |

---

**Fecha**: 21 de Diciembre de 2025  
**Versión**: 2.0  
**Autor**: GitHub Copilot
