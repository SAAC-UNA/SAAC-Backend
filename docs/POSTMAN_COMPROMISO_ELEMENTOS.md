# POSTMAN — Compromiso de Mejora: Flexible vs Tradicional

> Comparación lado a lado para probar ambos modelos en Postman.
> **Flexible** → `/api/compromisos-elementos`
> **Tradicional** → `/api/compromisos-de-mejora`

---

## Base URL
```
http://localhost:8000/api
```

## Autenticación
**POST** `/api/auth/login`
```json
{
  "cedula": "",
  "password": "password123"
}
```
Copia el `token` y úsalo en todos los requests como:
`Authorization: Bearer {token}`

---

## IDs de referencia (post-seeder)

| Variable              | Flexible               | Tradicional           |
|-----------------------|------------------------|-----------------------|
| proceso_id            | 1                      | 1                     |
| elemento_id (raíz)    | 15 (TEST-F1.1)         | —                     |
| usuario_id (profesor) | 3 (Marisol)            | 3 (Marisol)           |

---

---

# 1. CREAR

## Flexible — POST `/api/compromisos-elementos`

```json
{
  "proceso_id": 1,
  "elemento_id": 15,
  "descripcion": "Mejorar documentación del elemento TEST-F1.1",
  "fecha_inicio": "2026-04-01",
  "fecha_fin": "2026-06-30",
  "estado": "Pendiente",
  "elementos_asignar": [
    {
      "elemento_id": 15,
      "usuarios": [3],
      "fecha_limite": "2026-05-15",
      "comentario": "Asignación desde compromiso flexible"
    }
  ]
}
```

> `elementos_asignar` es opcional. Sin él, se crea el compromiso sin asignaciones.
> **Las asignaciones se crean aquí** — no existían antes en `ELEMENTO_ASIGNACION`.

---

## Tradicional — POST `/api/compromisos-de-mejora`

```json
{
  "proceso_id": 1,
  "descripcion": "Mejorar documentación de procesos académicos",
  "fecha_inicio": "2026-04-01",
  "fecha_fin": "2026-06-30",
  "selecciones": [
    {
      "entidad_tipo": "CRITERIO",
      "entidad_id": 1
    }
  ],
  "evidencias_asignar": [
    {
      "evidencia_id": 1,
      "usuarios": [3],
      "fecha_limite": "2026-05-15",
      "comentario": "Asignación desde compromiso tradicional"
    }
  ]
}
```

> `selecciones` define el árbol (por CRITERIO, COMPONENTE o DIMENSION).
> `proceso_id` es requerido — debe existir previamente en `PROCESO`.

---

---

# 2. EDITAR

## Flexible — PUT `/api/compromisos-elementos/{id}`

```json
{
  "descripcion": "Descripción actualizada flexible",
  "fecha_fin": "2026-07-31",
  "estado": "En Progreso",
  "elementos_asignar": [
    {
      "elemento_id": 15,
      "usuarios": [3, 4],
      "fecha_limite": "2026-06-01",
      "comentario": "Asignación actualizada"
    }
  ]
}
```

> `elementos_asignar` en UPDATE **reemplaza** todas las asignaciones del pivot.
> Todos los campos son opcionales (PUT parcial).

---

## Tradicional — PUT `/api/compromisos-de-mejora/{id}`

```json
{
  "descripcion": "Descripción actualizada tradicional",
  "fecha_fin": "2026-07-31",
  "estado": "En Progreso",
  "selecciones": [
    {
      "entidad_tipo": "CRITERIO",
      "entidad_id": 1
    }
  ],
  "evidencias_asignar": [
    {
      "evidencia_id": 1,
      "usuarios": [3],
      "fecha_limite": "2026-06-01"
    }
  ]
}
```

---

---

# 3. LISTAR

## Flexible — GET `/api/compromisos-elementos`

```
GET /api/compromisos-elementos
GET /api/compromisos-elementos?estado=Pendiente
GET /api/compromisos-elementos?proceso_id=1
GET /api/compromisos-elementos?elemento_id=15
GET /api/compromisos-elementos?usuario_id=3
GET /api/compromisos-elementos?search=documentación
GET /api/compromisos-elementos?per_page=5
```

---

## Tradicional — GET `/api/compromisos-de-mejora`

```
GET /api/compromisos-de-mejora
GET /api/compromisos-de-mejora?estado=Pendiente
GET /api/compromisos-de-mejora?proceso_id=1
GET /api/compromisos-de-mejora?usuario_id=3
GET /api/compromisos-de-mejora?search=documentación
```

---

---

# 4. OBTENER POR ID

## Flexible — GET `/api/compromisos-elementos/{id}`

```
GET /api/compromisos-elementos/1
```

---

## Tradicional — GET `/api/compromisos-de-mejora/{id}`

```
GET /api/compromisos-de-mejora/1
```

---

---

# 5. LISTAR POR USUARIO

## Flexible — GET `/api/compromisos-elementos/usuario/{usuarioId}`

```
GET /api/compromisos-elementos/usuario/3
```

---

## Tradicional — GET `/api/compromisos-de-mejora/usuario/{usuarioId}`

```
GET /api/compromisos-de-mejora/usuario/3
```

---

---

# 6. LISTAR POR ELEMENTO / EVIDENCIA

## Flexible — GET `/api/compromisos-elementos/elemento/{elementoId}`

```
GET /api/compromisos-elementos/elemento/15
```

---

## Tradicional — GET `/api/compromisos-de-mejora/evidencia/{evidenciaId}`

```
GET /api/compromisos-de-mejora/evidencia/1
```

---

---

# 7. ACTIVAR / DESACTIVAR

## Flexible — PATCH `/api/compromisos-elementos/{id}/active`

```json
{ "activo": false }
```

---

## Tradicional — PATCH `/api/compromisos-de-mejora/{id}/active`

```json
{ "activo": false }
```

---

---

## Diferencias clave

| Aspecto                        | Flexible                              | Tradicional                          |
|-------------------------------|---------------------------------------|--------------------------------------|
| Prefijo ruta                  | `/compromisos-elementos`              | `/compromisos-de-mejora`             |
| Proceso                       | `proceso_id` (ya existe, se envía)    | `proceso_id` (ya existe, se envía)   |
| Árbol de contenido            | `elemento_id` (raíz del árbol ELEMENTO) | `selecciones[]` (CRITERIO/COMPONENTE/DIMENSION) |
| Asignaciones                  | `elementos_asignar[]` → crea `ELEMENTO_ASIGNACION` | `evidencias_asignar[]` → crea `EVIDENCIA_ASIGNACION` |
| Filtro especial               | `?elemento_id=`                       | `?evidencia_id=` (vía ruta dedicada) |


> **Módulo:** `compromisos-elementos` 
> **Principio clave:** Al crear un compromiso con un `elemento_id`, el sistema recorre el árbol
> descendente y vincula automáticamente **todas las `ELEMENTO_ASIGNACION`** encontradas en ese
> proceso. No es necesario enviar IDs de asignaciones manualmente (son opcionales para override).

---

## Prerequisitos

### 1. Seeder de datos de prueba

```bash
# Prerequisito: AprobacionElementosTestSeeder debe haber corrido (crea el árbol TEST-*)
php artisan db:seed --class=AprobacionElementosTestSeeder

# Seeder específico de compromisos
php artisan db:seed --class=CompromisoElementosTestSeeder
```

### 2. Base URL

```
http://localhost:8000/api
```

---

## Usuarios de prueba

| Usuario                      | Cédula      | Password    | Rol                     |
|------------------------------|-------------|-------------|-------------------------|
| Encargado Acreditación Test  | 222222222   | password123 | Encargado de Acreditación |
| Marisol Hidalgo Murillo      | 118620669   | password123 | Profesor                |

---

## Árbol ELEMENTO de prueba

```
TEST-D1  (ID: 5)  — Dimensión raíz
└── TEST-P1  (ID: 6)  — Pauta
    ├── TEST-F1.1  (ID: 7)  — Fuente (tiene ELEMENTO_ASIGNACION ID: 3)
    └── TEST-F1.2  (ID: 8)  — Fuente (tiene ELEMENTO_ASIGNACION ID: 4)
```

### Comportamiento CASCADE al crear un compromiso

| `elemento_id` enviado | Nivel      | Asignaciones vinculadas automáticamente |
|-----------------------|------------|------------------------------------------|
| 7 (TEST-F1.1)         | Fuente     | 1 → asignacion_id: 3                    |
| 6 (TEST-P1)           | Pauta      | 2 → asignacion_ids: 3, 4               |
| 5 (TEST-D1)           | Dimensión  | 2 → asignacion_ids: 3, 4 (P1 sin asignación propia) |

---

## IDs de referencia (post-seeder)

```
dimension_id    (TEST-D1)  : 5
pauta_id        (TEST-P1)  : 6
fuente1_id      (TEST-F1.1): 7
fuente2_id      (TEST-F1.2): 8

asignacion_f1   (F1.1)     : 3
asignacion_f2   (F1.2)     : 4

compromiso_1_id (fuente F1.1)         : 1
compromiso_2_id (pauta P1 — cascada)  : 2
compromiso_3_id (dimensión D1 — cascada): 3

proceso_id: 1
```

---

## Autenticación

### Paso 0 — Login (Encargado)

**POST** `{{base_url}}/auth/login`

```json
{
  "identifier": "222222222",
  "password": "password123"
}
```

**Respuesta esperada:**
```json
{
  "token": "eyJ...",
  "user": {
    "id": 6,
    "nombre": "Encargado Acreditación Test",
    "roles": ["Encargado de Acreditación"]
  }
}
```

> Guarda el token en la variable de entorno `{{token}}`.
> Header para todos los endpoints: `Authorization: Bearer {{token}}`

---

## Paso 0.5 — Limpiar datos de prueba (SQL opcional)

```sql
-- Eliminar compromisos TEST (el pivot se eliminan por CASCADE)
DELETE cme FROM COMPROMISO_MEJORA_ELEMENTO cme
INNER JOIN COMPROMISO_MEJORA_ELEMENTO_ASIGNACION cmea
    ON cmea.compromiso_elemento_id = cme.compromiso_elemento_id
INNER JOIN ELEMENTO_ASIGNACION ea
    ON ea.elemento_asignacion_id = cmea.elemento_asignacion_id
INNER JOIN ELEMENTO e
    ON e.elemento_id = ea.elemento_id
WHERE e.codigo LIKE 'TEST-%';

-- O simplemente re-ejecutar el seeder (limpia y recrea)
-- php artisan db:seed --class=CompromisoElementosTestSeeder
```

---

## Endpoints

---

### 1. Listar compromisos

**GET** `{{base_url}}/compromisos-elementos`

**Headers:**
```
Authorization: Bearer {{token}}
```

**Parámetros de query (todos opcionales):**

| Parámetro   | Tipo    | Descripción                                |
|-------------|---------|--------------------------------------------|
| `search`    | string  | Búsqueda en descripción                    |
| `estado`    | string  | `Pendiente`, `En Progreso`, `Completado`, `Vencido` |
| `proceso_id`| integer | Filtrar por proceso                        |
| `elemento_id`| integer| Filtrar por elemento (include descendientes)|
| `usuario_id`| integer | Filtrar por usuario                        |
| `page`      | integer | Página (default: 1)                        |
| `per_page`  | integer | Items por página (default: 10, max: 50)    |

**Ejemplo — sin filtros:**

`GET {{base_url}}/compromisos-elementos`

**Respuesta 200:**
```json
{
  "data": [
    {
      "compromiso_elemento_id": 1,
      "proceso_id": 1,
      "descripcion": "TEST — Compromiso directo fuente F1.1",
      "fecha_inicio": "2025-01-15",
      "fecha_fin": "2025-02-15",
      "estado": "Pendiente",
      "activo": true,
      "is_overdue": false,
      "assigned_elementos": [
        {
          "elemento_asignacion_id": 3,
          "elemento_id": 7,
          "comentario": "Vinculado automáticamente a TEST-F1.1",
          "pivot": { "comentario": "Vinculado automáticamente a TEST-F1.1" }
        }
      ]
    },
    {
      "compromiso_elemento_id": 2,
      "proceso_id": 1,
      "descripcion": "TEST — Compromiso pauta P1 (cascada F1.1+F1.2)",
      "fecha_inicio": "2025-01-15",
      "fecha_fin": "2025-03-15",
      "estado": "En Progreso",
      "activo": true,
      "assigned_elementos": [
        { "elemento_asignacion_id": 3, "elemento_id": 7 },
        { "elemento_asignacion_id": 4, "elemento_id": 8 }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 3
  }
}
```

**Ejemplo — con filtro de estado:**

`GET {{base_url}}/compromisos-elementos?estado=En Progreso`

**Ejemplo — con filtro de elemento (incluye cascada):**

`GET {{base_url}}/compromisos-elementos?elemento_id=6`

---

### 2. Ver compromiso individual

**GET** `{{base_url}}/compromisos-elementos/1`

**Headers:**
```
Authorization: Bearer {{token}}
```

**Respuesta 200:**
```json
{
  "compromiso_elemento_id": 1,
  "proceso_id": 1,
  "descripcion": "TEST — Compromiso directo fuente F1.1",
  "fecha_inicio": "2025-01-15",
  "fecha_fin": "2025-02-15",
  "estado": "Pendiente",
  "activo": true,
  "is_overdue": false,
  "process": {
    "proceso_id": 1,
    "nombre": "Proceso de prueba"
  },
  "assigned_elementos": [
    {
      "elemento_asignacion_id": 3,
      "elemento_id": 7,
      "usuario_id": 3,
      "pivot": {
        "compromiso_elemento_id": 1,
        "elemento_asignacion_id": 3,
        "comentario": "Vinculado automáticamente a TEST-F1.1"
      }
    }
  ]
}
```

**Error 404:**
```json
{
  "message": "Compromiso de mejora no encontrado."
}
```

---

### 3. Compromisos por usuario

**GET** `{{base_url}}/compromisos-elementos/usuario/3`

> Devuelve los compromisos donde el usuario tiene una `ELEMENTO_ASIGNACION` vinculada.
> `usuario_id: 3` = Marisol Hidalgo Murillo (Profesor).

**Headers:**
```
Authorization: Bearer {{token}}
```

**Respuesta 200:**
```json
{
  "data": [
    {
      "compromiso_elemento_id": 1,
      "descripcion": "TEST — Compromiso directo fuente F1.1",
      "estado": "Pendiente"
    }
  ]
}
```

---

### 4. Compromisos por elemento (con cascada descendente)

**GET** `{{base_url}}/compromisos-elementos/elemento/6`

> Devuelve compromisos donde `elemento_id=6` (TEST-P1) **o cualquier descendiente** tenga
> ELEMENTO_ASIGNACION vinculada. Útil para ver todos los compromisos de una pauta/dimensión.

**Headers:**
```
Authorization: Bearer {{token}}
```

**Respuesta 200:**
```json
{
  "data": [
    {
      "compromiso_elemento_id": 1,
      "descripcion": "TEST — Compromiso directo fuente F1.1",
      "estado": "Pendiente"
    },
    {
      "compromiso_elemento_id": 2,
      "descripcion": "TEST — Compromiso pauta P1 (cascada F1.1+F1.2)",
      "estado": "En Progreso"
    },
    {
      "compromiso_elemento_id": 3,
      "descripcion": "TEST — Compromiso dimensión D1 (cascada total)",
      "estado": "Pendiente"
    }
  ]
}
```

**GET** `{{base_url}}/compromisos-elementos/elemento/5`
> Mismo resultado: consulta desde la raíz (D1) también devuelve los 3 compromisos.

---

### 5. Crear compromiso — Escenario A: fuente individual

**POST** `{{base_url}}/compromisos-elementos`

> `elemento_id: 7` (TEST-F1.1) → vincula solo la asignación de F1.1 (ID: 3).

**Headers:**
```
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
  "proceso_id": 1,
  "elemento_id": 7,
  "descripcion": "Compromiso individual para fuente F1.1",
  "fecha_inicio": "2025-02-01",
  "fecha_fin": "2025-04-30",
  "estado": "Pendiente"
}
```

**Respuesta 201:**
```json
{
  "compromiso_elemento_id": 4,
  "proceso_id": 1,
  "descripcion": "Compromiso individual para fuente F1.1",
  "fecha_inicio": "2025-02-01",
  "fecha_fin": "2025-04-30",
  "estado": "Pendiente",
  "activo": true,
  "is_overdue": false,
  "assigned_elementos": [
    {
      "elemento_asignacion_id": 3,
      "elemento_id": 7,
      "pivot": { "comentario": null }
    }
  ]
}
```

---

### 5b. Crear compromiso — Escenario B: pauta con cascada

**POST** `{{base_url}}/compromisos-elementos`

> `elemento_id: 6` (TEST-P1) → vincula asignaciones de F1.1 (ID: 3) y F1.2 (ID: 4) automáticamente.

**Body:**
```json
{
  "proceso_id": 1,
  "elemento_id": 6,
  "descripcion": "Compromiso para pauta P1 — aplica a todas las fuentes",
  "fecha_inicio": "2025-02-01",
  "fecha_fin": "2025-05-31",
  "estado": "En Progreso"
}
```

**Respuesta 201:**
```json
{
  "compromiso_elemento_id": 5,
  "proceso_id": 1,
  "descripcion": "Compromiso para pauta P1 — aplica a todas las fuentes",
  "fecha_inicio": "2025-02-01",
  "fecha_fin": "2025-05-31",
  "estado": "En Progreso",
  "activo": true,
  "assigned_elementos": [
    {
      "elemento_asignacion_id": 3,
      "elemento_id": 7,
      "pivot": { "comentario": null }
    },
    {
      "elemento_asignacion_id": 4,
      "elemento_id": 8,
      "pivot": { "comentario": null }
    }
  ]
}
```

> **Nota:** Las 2 asignaciones se vincularon automáticamente. P1 no tiene asignación propia,
> pero sus hijos F1.1 y F1.2 sí.

---

### 5c. Crear compromiso — Escenario C: dimensión raíz (cascada total)

**POST** `{{base_url}}/compromisos-elementos`

> `elemento_id: 5` (TEST-D1) → recorre toda la jerarquía y vincula F1.1 + F1.2.

**Body:**
```json
{
  "proceso_id": 1,
  "elemento_id": 5,
  "descripcion": "Compromiso a nivel dimensión — aplica a toda la jerarquía",
  "fecha_inicio": "2025-02-01",
  "fecha_fin": "2025-06-30",
  "estado": "Pendiente"
}
```

**Respuesta 201:**
```json
{
  "compromiso_elemento_id": 6,
  "proceso_id": 1,
  "descripcion": "Compromiso a nivel dimensión — aplica a toda la jerarquía",
  "fecha_inicio": "2025-02-01",
  "fecha_fin": "2025-06-30",
  "estado": "Pendiente",
  "activo": true,
  "assigned_elementos": [
    { "elemento_asignacion_id": 3, "elemento_id": 7 },
    { "elemento_asignacion_id": 4, "elemento_id": 8 }
  ]
}
```

---

### 5d. Crear compromiso — con comentarios personalizados por asignación

**POST** `{{base_url}}/compromisos-elementos`

> Pasar `asignaciones[]` y `comentarios_asignacion[]` para controlar exactamente qué
> asignaciones vincular y con qué comentario.

**Body:**
```json
{
  "proceso_id": 1,
  "elemento_id": 6,
  "descripcion": "Compromiso con comentarios específicos",
  "fecha_inicio": "2025-02-01",
  "fecha_fin": "2025-04-30",
  "estado": "Pendiente",
  "asignaciones": [3, 4],
  "comentarios_asignacion": [
    "F1.1 debe cumplir criterio X",
    "F1.2 está en revisión"
  ]
}
```

**Respuesta 201:**
```json
{
  "compromiso_elemento_id": 7,
  "assigned_elementos": [
    {
      "elemento_asignacion_id": 3,
      "pivot": { "comentario": "F1.1 debe cumplir criterio X" }
    },
    {
      "elemento_asignacion_id": 4,
      "pivot": { "comentario": "F1.2 está en revisión" }
    }
  ]
}
```

---

### 6. Actualizar compromiso

**PUT** `{{base_url}}/compromisos-elementos/1`

> Todos los campos son opcionales (`sometimes` en la request).
> Si se cambia `elemento_id`, el sistema re-sincroniza las asignaciones del nuevo árbol.

**Headers:**
```
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body — cambio de estado y fecha:**
```json
{
  "estado": "En Progreso",
  "fecha_fin": "2025-03-31"
}
```

**Respuesta 200:**
```json
{
  "compromiso_elemento_id": 1,
  "estado": "En Progreso",
  "fecha_fin": "2025-03-31",
  "activo": true,
  "assigned_elementos": [
    { "elemento_asignacion_id": 3 }
  ]
}
```

**Body — cambio de elemento (re-sincronización cascade):**
```json
{
  "elemento_id": 6,
  "descripcion": "Ahora cubre toda la pauta P1"
}
```

> Al cambiar `elemento_id` de 7 a 6, el sistema detecta el cambio y recalcula las asignaciones:
> se pasa de 1 asignación (F1.1) a 2 (F1.1 + F1.2).

---

### 7. Activar / Desactivar compromiso

**PATCH** `{{base_url}}/compromisos-elementos/1/active`

**Headers:**
```
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body — desactivar:**
```json
{
  "activo": false
}
```

**Respuesta 200:**
```json
{
  "compromiso_elemento_id": 1,
  "activo": false,
  "estado": "Pendiente"
}
```

**Body — reactivar:**
```json
{
  "activo": true
}
```

---

## Casos de error

### 401 — Sin autenticación

```
GET {{base_url}}/compromisos-elementos
(sin header Authorization)
```

```json
{
  "message": "Unauthenticated."
}
```

---

### 403 — Sin permiso

> Intentar crear como Profesor (solo tiene `compromisos_mejora.view`).

**Login como Profesor primero:**
```json
{
  "identifier": "118620669",
  "password": "password123"
}
```

```
POST {{base_url}}/compromisos-elementos
Authorization: Bearer {{token_profesor}}
```

```json
{
  "message": "This action is unauthorized."
}
```

---

### 404 — Compromiso no encontrado

```
GET {{base_url}}/compromisos-elementos/9999
```

```json
{
  "message": "Compromiso de mejora no encontrado."
}
```

---

### 422 — Validación fallida

**Body inválido (fecha_fin antes de fecha_inicio):**
```json
{
  "proceso_id": 1,
  "elemento_id": 7,
  "descripcion": "Test validación",
  "fecha_inicio": "2025-06-01",
  "fecha_fin": "2025-01-01"
}
```

```json
{
  "message": "The fecha_fin field must be a date after fecha_inicio.",
  "errors": {
    "fecha_fin": ["The fecha_fin field must be a date after fecha_inicio."]
  }
}
```

**Body inválido (proceso_id no existe):**
```json
{
  "proceso_id": 9999,
  "elemento_id": 7,
  "descripcion": "Test",
  "fecha_inicio": "2025-02-01",
  "fecha_fin": "2025-04-30"
}
```

```json
{
  "message": "The selected proceso_id is invalid.",
  "errors": {
    "proceso_id": ["The selected proceso_id is invalid."]
  }
}
```

---

### 409 — Conflicto de integridad

> Se produce si se intenta eliminar un proceso que tiene compromisos activos.

```json
{
  "message": "No se puede completar la operación por conflicto de integridad referencial."
}
```

---

## Resumen de endpoints

| Método | URL                                      | Permiso requerido           | Descripción                                    |
|--------|------------------------------------------|-----------------------------|------------------------------------------------|
| GET    | `/api/compromisos-elementos`             | `compromisos_mejora.view`   | Listar con filtros y paginación                |
| GET    | `/api/compromisos-elementos/{id}`        | `compromisos_mejora.view`   | Ver compromiso individual                      |
| GET    | `/api/compromisos-elementos/usuario/{id}`| `compromisos_mejora.view`   | Compromisos del usuario                        |
| GET    | `/api/compromisos-elementos/elemento/{id}`| `compromisos_mejora.view`  | Compromisos por elemento (cascada descendente) |
| POST   | `/api/compromisos-elementos`             | `compromisos_mejora.create` | Crear (vincula asignaciones automáticamente)   |
| PUT    | `/api/compromisos-elementos/{id}`        | `compromisos_mejora.edit`   | Actualizar (re-sincroniza si cambia elemento)  |
| PATCH  | `/api/compromisos-elementos/{id}/active` | `compromisos_mejora.edit`   | Activar / Desactivar                           |

---

## Diferencia clave con compromisos tradicionales (EVIDENCIA)

| Aspecto                    | Modelo tradicional (EVIDENCIA)       | Modelo flexible (ELEMENTO)             |
|----------------------------|--------------------------------------|----------------------------------------|
| Tabla principal            | `COMPROMISO_MEJORA`                  | `COMPROMISO_MEJORA_ELEMENTO`           |
| Pivot                      | `COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION` | `COMPROMISO_MEJORA_ELEMENTO_ASIGNACION` |
| Segunda tabla pivot        | `COMPROMISO_MEJORA_EVIDENCIA` ✅     | ❌ No existe (innecesaria)             |
| Vinculación automática     | ❌ Manual / stored procedures        | ✅ `collectDescendantIds()` automático  |
| Granularidad               | Por evidencia                        | Por árbol (dimensión/pauta/fuente)     |

---

## Flujo de prueba recomendado en Postman

```
1. Login encargado → guardar {{token}}
2. GET /compromisos-elementos → ver 3 compromisos del seeder
3. GET /compromisos-elementos/1 → fuente individual (1 asignación)
4. GET /compromisos-elementos/2 → pauta cascada (2 asignaciones)
5. GET /compromisos-elementos/elemento/6 → debe devolver los 3 compromisos
6. POST /compromisos-elementos (elemento_id: 6) → crear nuevo con cascada
7. PUT /compromisos-elementos/1 → actualizar estado
8. PATCH /compromisos-elementos/1/active → desactivar
9. Login profesor → intentar POST → debe dar 403
10. GET /compromisos-elementos/usuario/3 → compromisos del profesor
```
