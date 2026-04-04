# POSTMAN — Aprobación: Flexible → Tradicional

> **Flexible** opera sobre `ELEMENTO` con cascada a hijos.
> **Tradicional** opera sobre `CRITERIO` sin cascada.

---

## Base URL

```
http://localhost:8000/api
```

## Autenticación

**POST** `/api/auth/login`

```json
{ "identifier": "222222222", "password": "password123" }
```

Copia el `token` y úsalo en todos los requests:
`Authorization: Bearer {token}`

---

## Usuarios de prueba

| usuario_id | Cédula    | Password    | Rol                       |
|-----------|-----------|-------------|---------------------------|
| 6         | 222222222 | password123 | Encargado de Acreditación |
| 3         | 118620669 | password123 | Profesor (solo lectura)   |

---

## IDs reales en BD (estado actual)

### Árbol ELEMENTO flexible

```
ID 1  TEST-D1  (dimension)   <- sin aprobación aún  -> cascada 4 nodos
  ID 2  TEST-P1  (pauta)     <- sin aprobación aún  -> cascada 3 nodos
    ID 3  TEST-F1.1 (fuente) <- APROBADO   (aprobacion_id = 1)
    ID 4  TEST-F1.2 (fuente) <- RECHAZADO  (aprobacion_id = 2)
```

| elemento_id | Nomenclatura | Tipo      | Estado actual                    |
|-------------|-------------|-----------|----------------------------------|
| **1**       | TEST-D1     | dimension | sin aprobación                   |
| **2**       | TEST-P1     | pauta     | sin aprobación                   |
| **3**       | TEST-F1.1   | fuente    | **aprobado** -> aprobacion_id=1  |
| **4**       | TEST-F1.2   | fuente    | **rechazado** -> aprobacion_id=2 |

### Criterios activos (tradicional)

| criterio_id | Nomenclatura | Estado actual  |
|-------------|-------------|----------------|
| **1**       | 1.1.1       | sin aprobación |
| **2**       | 1.1.2       | sin aprobación |
| **3**       | 1.2.1       | sin aprobación |

**proceso_id activo:** `1`

---

---

# FLEXIBLE

## 1. Aprobar elemento — POST /api/elementos/{elementoId}/aprobar

> Aprueba el elemento raíz **y todos sus descendientes activos** (cascada recursiva).

### Fuente individual — ID 3 (ya aprobado -> retorna 400)

```
POST /api/elementos/3/aprobar
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{ "proceso_id": 1, "comentario": "Documentación verificada" }
```

**Respuesta 400 — ya estaba aprobado:**
```json
{ "success": false, "message": "El elemento ya está aprobado." }
```

### Dimensión con cascada — ID 1 (fresco, crea 4 registros)

```
POST /api/elementos/1/aprobar
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{ "proceso_id": 1, "comentario": "Árbol completo verificado" }
```

**Respuesta 201 — aprueba D1 + P1 + F1.1 + F1.2:**
```json
{
  "success": true,
  "message": "Elemento aprobado exitosamente.",
  "data": {
    "aprobacion_elemento_id": 3,
    "elemento_id": 1,
    "proceso_id": 1,
    "estado": "aprobado",
    "comentario": "Árbol completo verificado",
    "elemento": { "elemento_id": 1, "nomenclatura": "TEST-D1", "tipo": "dimension" }
  }
}
```

> En BD se crearon/actualizaron 4 filas en APROBACION_ELEMENTO.
> El body devuelto es **solo el nodo raíz** (TEST-D1).

### Pauta con cascada parcial — ID 2 (crea/actualiza 3 registros)

```
POST /api/elementos/2/aprobar
Content-Type: application/json
```

```json
{ "proceso_id": 1, "comentario": "Pauta y fuentes verificadas" }
```

> Afecta: P1 (ID 2), F1.1 (ID 3), F1.2 (ID 4).

---

## 2. Rechazar elemento — POST /api/elementos/{elementoId}/rechazar

> Rechaza el elemento raíz y todos sus hijos. `fecha_limite` opcional.

### Fuente individual — ID 4 (ya rechazado -> 400)

```
POST /api/elementos/4/rechazar
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{ "proceso_id": 1, "comentario": "Falta firma" }
```

**Respuesta 400:**
```json
{ "success": false, "message": "El elemento ya está rechazado." }
```

### Pauta con cascada — ID 2 (actualiza 3 registros)

```
POST /api/elementos/2/rechazar
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "proceso_id": 1,
  "comentario": "Documentación insuficiente en fuentes",
  "fecha_limite": "2026-05-15"
}
```

**Respuesta 201:**
```json
{
  "success": true,
  "message": "Elemento rechazado exitosamente.",
  "data": {
    "elemento_id": 2,
    "proceso_id": 1,
    "estado": "rechazado",
    "comentario": "Documentación insuficiente en fuentes"
  }
}
```

---

## 3. Listar aprobaciones — GET /api/aprobaciones-elementos

```
GET /api/aprobaciones-elementos
Authorization: Bearer {token}
```

**Con filtro de estado:**
```
GET /api/aprobaciones-elementos?estado=aprobado
GET /api/aprobaciones-elementos?estado=rechazado
```

**Respuesta 200** (estado inicial seeder — 2 registros):
```json
{
  "success": true,
  "data": [
    { "aprobacion_elemento_id": 1, "elemento_id": 3, "proceso_id": 1, "estado": "aprobado" },
    { "aprobacion_elemento_id": 2, "elemento_id": 4, "proceso_id": 1, "estado": "rechazado" }
  ]
}
```

> Autenticado como **Profesor (ID 3 / 118620669)**: solo ve elementos con asignación suya.

---

## 4. Ver aprobación por ID — GET /api/aprobaciones-elementos/{id}

```
GET /api/aprobaciones-elementos/1      <- F1.1 aprobado
GET /api/aprobaciones-elementos/2      <- F1.2 rechazado
Authorization: Bearer {token}
```

**Respuesta 200** (ID 1):
```json
{
  "success": true,
  "data": {
    "aprobacion_elemento_id": 1,
    "elemento_id": 3,
    "proceso_id": 1,
    "estado": "aprobado",
    "elemento": { "elemento_id": 3, "nomenclatura": "TEST-F1.1", "tipo": "fuente" }
  }
}
```

---

---

# TRADICIONAL

## 1. Aprobar criterio — POST /api/criterios/{criterioId}/aprobar

```
POST /api/criterios/1/aprobar
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{ "proceso_id": 1, "comentario": "Criterio 1.1.1 revisado y aprobado" }
```

**Respuesta 201:**
```json
{
  "success": true,
  "message": "Criterio aprobado exitosamente.",
  "data": {
    "aprobacion_criterio_id": 1,
    "criterio_id": 1,
    "proceso_id": 1,
    "estado": "aprobado"
  }
}
```

---

## 2. Rechazar criterio — POST /api/criterios/{criterioId}/rechazar

```
POST /api/criterios/2/rechazar
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{ "proceso_id": 1, "comentario": "Evidencias insuficientes en 1.1.2" }
```

**Respuesta 201:**
```json
{
  "success": true,
  "data": { "aprobacion_criterio_id": 2, "criterio_id": 2, "proceso_id": 1, "estado": "rechazado" }
}
```

---

## 3. Listar aprobaciones — GET /api/aprobaciones-criterios

```
GET /api/aprobaciones-criterios
GET /api/aprobaciones-criterios?estado=aprobado
GET /api/aprobaciones-criterios?estado=rechazado
Authorization: Bearer {token}
```

---

## 4. Ver aprobación por ID — GET /api/aprobaciones-criterios/{id}

```
GET /api/aprobaciones-criterios/1
Authorization: Bearer {token}
```

---

---

## Errores comunes

| Código | Cuándo ocurre                        | Mensaje ejemplo                           |
|--------|--------------------------------------|-------------------------------------------|
| 400    | Ya en ese estado                     | `"El elemento ya está aprobado."`         |
| 401    | Sin token                            | `"Unauthenticated."`                      |
| 403    | Profesor intenta aprobar             | `"No tienes permiso para realizar esto."` |
| 404    | ID no existe                         | `"El elemento especificado no existe."`   |
| 422    | proceso_id inválido / fecha pasada   | `errors: { "proceso_id": [...] }`         |

---

## Diferencias clave

| Aspecto        | Flexible (/elementos)             | Tradicional (/criterios)    |
|----------------|-----------------------------------|-----------------------------|
| Tabla          | APROBACION_ELEMENTO               | APROBACION_CRITERIO         |
| Cascada        | Si — hijos recursivos activos     | No — solo el nodo raíz      |
| fecha_limite   | Si en rechazar (opcional)         | No aplica                   |
| Body requerido | proceso_id                        | proceso_id                  |

---

## Flujo de prueba recomendado

```
1.  Login encargado (222222222)            -> guardar {{token}}

-- Ver estado inicial (seeder) --
2.  GET  /aprobaciones-elementos           -> ID 1 (elem 3 aprobado), ID 2 (elem 4 rechazado)
3.  GET  /aprobaciones-elementos/1         -> detalle F1.1 aprobado
4.  GET  /aprobaciones-elementos/2         -> detalle F1.2 rechazado
5.  GET  /aprobaciones-elementos?estado=rechazado -> solo rechazados

-- Ya en ese estado --
6.  POST /elementos/3/aprobar              -> 400 ya está aprobado
7.  POST /elementos/4/rechazar             -> 400 ya está rechazado

-- Aprobar con cascada (nodos sin aprobación) --
8.  POST /elementos/1/aprobar              -> 201 (TEST-D1 + P1 + F1.1 + F1.2 = 4 registros)
9.  GET  /aprobaciones-elementos           -> ahora todos aprobados

-- Rechazar pauta con fecha_limite --
10. POST /elementos/2/rechazar             -> 201 (P1 + F1.1 + F1.2 = 3 actualizados)
11. GET  /aprobaciones-elementos?estado=rechazado -> ver los rechazados

-- Tradicional --
12. POST /criterios/1/aprobar              -> 201 (1.1.1 aprobado, sin cascada)
13. POST /criterios/2/rechazar             -> 201 (1.1.2 rechazado)
14. GET  /aprobaciones-criterios           -> listar
15. GET  /aprobaciones-criterios/1         -> ver detalle

-- Rol Profesor (solo lectura) --
16. Login Marisol (118620669)              -> cambiar {{token}}
17. GET  /aprobaciones-elementos           -> solo sus asignaciones
18. POST /elementos/4/aprobar              -> 403 sin permiso
```
