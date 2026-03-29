# Pruebas Postman � Modelos, Elementos y Procesos

> **Base URL:** `http://localhost:8000/api`  
> **Header requerido:** `Authorization: Bearer <token>` en todos los requests.  
> El token se obtiene con el endpoint de login.

---

## 0. Login (obtener token)

```
POST /api/auth/login
Content-Type: application/json
```
```json
{
    "cedula": "801490957",
    "password": "password123"
}
```
Guardar el `token` de la respuesta como variable en Postman y usarlo en todos los dem�s requests.

---

## M�DULO A � MODELOS DE ESTRUCTURA

> **Importante:** La BD arranca **vac�a** � no hay modelos preinsertados.  
> Hay que crearlos v�a CRUD antes de poder crear ciclos o procesos. // ver que tiene kir para saber como manejarlo 
> Rol **Superusuario** requerido para POST, PUT y PATCH.

### Tipos v�lidos (enum fijo en BD, solo estos 2):
| Valor | Descripci�n |
|---|---|
| `tradicional` | SINAES 2018 ? estructura DIMENSION ? COMPONENTE ? CRITERIO |
| `elemento_flexible` | SINAES 2026+ ? �rbol libre de ELEMENTO con padre_id |

---

### A1. Listar todos los modelos
```
GET /api/estructura/modelos
Authorization: Bearer <token>
```
**Respuesta 200** � lista vac�a si no se ha creado ninguno a�n:
```json
[]
```

---

### A2. Listar solo modelos activos
```
GET /api/estructura/modelos/activos
Authorization: Bearer <token>
```

---

### A3. Ver modelo espec�fico
```
GET /api/estructura/modelos/{id}
Authorization: Bearer <token>
```
**404 si no existe:**
```json
{ "message": "Modelo de estructura no encontrado." }
```

---

### A4. Crear modelo flexible
> **Importante:** El modelo `tradicional` (SINAES 2018) es creado automáticamente por el sistema en la migración (`php artisan migrate`), no requiere `--seed`.
> Por API solo se pueden crear modelos `elemento_flexible` — intentar crear `tipo: "tradicional"` retorna **422**.
>
> **¿Por qué el modelo tradicional no se crea desde el CRUD?**
> - Es un dato del sistema, no del usuario
> - El tipo `tradicional` está bloqueado activamente en `StructureModelRequest` — retorna 422, no es solo una convención
> - El insert está en la migración `007a` con `insertOrIgnore` — corre solo con `migrate`, cero acción manual
> - Sus `nombre`, `descripcion` y `version` sí son editables después vía `PUT /estructura/modelos/{id}`
>
> **¿Una carrera que solo usa el modelo flexible necesita que exista el tradicional?**
> - No funcionalmente: `DIMENSION`, `COMPONENTE` y `CRITERIO` son tablas independientes, sin FK a `MODELO_ESTRUCTURA`
> - El modelo tradicional existe para que se puedan crear ciclos de tipo tradicional — si una carrera nunca crea ese tipo de ciclo, no le afecta

```
POST /api/estructura/modelos
Authorization: Bearer <token>   (Superusuario)
Content-Type: application/json
```
```json
{
    "nombre": "SINAES 2026 - Estructura Flexible",
    "tipo": "elemento_flexible",
    "descripcion": "Nuevo modelo SINAES 2026 con estructura flexible basada en Pautas.",
    "version": "2026",
    "activo": true
}
```

**Respuesta 201:**
```json
{
    "message": "Modelo de estructura creado exitosamente.",
    "data": { "modelo_estructura_id": 2, "nombre": "SINAES 2026 - Estructura Flexible", "tipo": "elemento_flexible", ... }
}
```
> Guardar el `modelo_estructura_id` para usarlo al crear ciclos y elementos.

**Pruebas de error - validacion (422):**

| Caso | Body |
|------|------|
| Sin `nombre` | `{ "tipo": "elemento_flexible" }` |
| Sin `tipo` | `{ "nombre": "Test" }` |
| `tipo` = tradicional | `{ "nombre": "Test", "tipo": "tradicional" }` → 422 "El unico tipo que puede crear es: elemento_flexible." |
| `nombre` > 100 chars | string largo |
| `nombre` con simbolos (`@#$`) | 422 regex |
| `version` con simbolos (`@#$`) | 422 regex |

---

### A5. Editar modelo (solo nombre, descripcion, version)
```
PUT /api/estructura/modelos/{id}
Authorization: Bearer <token>   (Superusuario)
Content-Type: application/json
```
```json
{
    "nombre": "SINAES 2026 v2",
    "descripcion": "Descripci�n actualizada",
    "version": "2.1"
}
```
> `tipo` y `activo` **NO son editables** por este endpoint � son ignorados aunque se env�en.  
> Para `activo` usar el endpoint A6.

**Respuesta 200:**
```json
{
    "message": "Modelo de estructura actualizado exitosamente.",
    "data": { ... }
}
```

**Pruebas de error:**

| Caso | Resultado esperado |
|------|-------------------|
| ID inexistente | 404 `Modelo de estructura no encontrado.` |
| Body vac�o `{}` | 422 `No se enviaron campos v�lidos para actualizar.` |
| Enviar `tipo` en body | Ignorado � el tipo NO cambia |

---

### A6. Activar / Desactivar modelo
```
PATCH /api/estructura/modelos/{id}/active
Authorization: Bearer <token>   (Superusuario)
Content-Type: application/json
```
```json
{ "active": false }
```
Idempotente: si ya está en el estado solicitado, no hace nada.

**Respuesta 200:**
```json
{
    "message": "Modelo de estructura desactivado exitosamente.",
    "active": false
}
```

---

### A7. Eliminar modelo (con confirmación)
> Solo modelos `elemento_flexible`. El modelo `tradicional` (ID 1) nunca se puede eliminar.  
> **Elimina en cascada TODO:** ciclos, procesos, autoevaluaciones, compromisos, asignaciones, archivos físicos, aprobaciones y elementos.

**PASO 1 — Ver qué se eliminará** (enviar sin `confirmacion` o con valor incorrecto):
```
DELETE /api/estructura/modelos/{id}
Authorization: Bearer <token>   (Superusuario)
Content-Type: application/json
```
```json
{}
```
**Respuesta 422** — resumen de lo que se borrará:
```json
{
    "message": "Confirmación incorrecta. Envíe el nombre exacto del modelo en el campo \"confirmacion\" para confirmar la eliminación.",
    "modelo_nombre": "SINAES 2026 - Estructura Flexible",
    "advertencia": "Esta acción es irreversible y eliminará el modelo junto con los siguientes datos:",
    "datos_a_eliminar": {
        "ciclos_acreditacion": 2,
        "procesos": 4,
        "autoevaluaciones": 2,
        "compromisos_mejora": 2,
        "asignaciones_evidencia": 5,
        "solicitudes_ampliacion": 1,
        "aprobaciones": 3,
        "archivos": 6,
        "elementos": 12
    }
}
```

**PASO 2 — Confirmar con el nombre exacto del modelo:**
```
DELETE /api/estructura/modelos/{id}
Authorization: Bearer <token>   (Superusuario)
Content-Type: application/json
```
```json
{
    "confirmacion": "SINAES 2026 - Estructura Flexible"
}
```
**Respuesta 200:**
```json
{
    "message": "Modelo de estructura \"SINAES 2026 - Estructura Flexible\" eliminado exitosamente.",
    "datos_eliminados": {
        "ciclos_acreditacion": 2,
        "procesos": 4,
        "autoevaluaciones": 2,
        "compromisos_mejora": 2,
        "asignaciones_evidencia": 5,
        "solicitudes_ampliacion": 1,
        "aprobaciones": 3,
        "archivos": 6,
        "elementos": 12
    }
}
```

**Pruebas de error:**

| Caso | Resultado esperado |
|------|-------------------|
| ID inexistente | 404 `Modelo de estructura no encontrado.` |
| Modelo tradicional (ID 1) | 422 `El modelo tradicional del sistema no puede ser eliminado.` |
| `confirmacion` incorrecta | 422 con `datos_a_eliminar` |
| Sin header Authorization | 401 |

---

## M�DULO B � ELEMENTOS (modelo tipo `elemento_flexible`)

> Construyen el �rbol jer�rquico del modelo flexible.  
> `padre_id: null` = elemento ra�z.  
> `padre_id: {id}` = hijo de ese elemento.  
> Usar el `modelo_estructura_id` obtenido en A4.

---

### B1. Listar elementos
```
GET /api/estructura/elementos
Authorization: Bearer <token>
```
**Filtros opcionales:**

| Param | Ejemplo | Descripci�n |
|-------|---------|-------------|
| `tipo` | `?tipo=pauta` | Filtrar por tipo de elemento |
| `modelo_estructura_id` | `?modelo_estructura_id=1` | Filtrar por modelo |
| Combinado | `?tipo=pauta&modelo_estructura_id=1` | Ambos filtros |

---

### B2. Ver elemento espec�fico
```
GET /api/estructura/elementos/{id}
Authorization: Bearer <token>
```

---

### B3. Crear elemento raíz
```
POST /api/estructura/elementos
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{
    "modelo_estructura_id": 2,
    "padre_id": null,
    "tipo": "area",
    "categoria": "A",
    "nomenclatura": "AG-01",
    "descripcion": "Area principal de gestion academica",
    "activo": true
}
```
**Respuesta 201:**
```json
{
    "message": "Elemento creado correctamente.",
    "data": { "elemento_id": 1, ... }
}
```
> Guardar el `elemento_id` para crear hijos.

---

### B4. Crear elemento hijo
```
POST /api/estructura/elementos
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{
    "modelo_estructura_id": 2,
    "padre_id": 1,
    "tipo": "subarea",
    "categoria": "B",
    "nomenclatura": "AG-01-01",
    "activo": true
}
```

---

### B5. Crear pauta/hoja (nombre puede ser null)
```
POST /api/estructura/elementos
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{
    "modelo_estructura_id": 2,
    "padre_id": 2,
    "tipo": "pauta",
    "categoria": "C",
    "nomenclatura": "AG-01-01-01",
    "descripcion": "El programa cuenta con un perfil de egreso actualizado.",
    "activo": true
}
```

---

### B6. Editar elemento
```
PUT /api/estructura/elementos/{id}
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{
    "nomenclatura": "AG-01-v2",
    "descripcion": "Nueva descripción"
}
```
PATCH tambi�n funciona � solo se actualizan los campos enviados.

---

### B7. Activar / Desactivar elemento
```
PATCH /api/estructura/elementos/{id}/active
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{ "active": false }
```
**Respuesta 200:**
```json
{
    "message": "Elemento desactivado correctamente.",
    "active": false
}
```
> Solo devuelve el nuevo estado — no el objeto completo.

---

### B8. Eliminar elemento
```
DELETE /api/estructura/elementos/{id}
Authorization: Bearer <token>
```
> Solo funciona si el elemento **no tiene hijos** � eliminar de abajo hacia arriba.

**422 si tiene hijos:**
```json
{
    "message": "No se puede eliminar un elemento que tiene hijos. Elimine primero los elementos hijos."
}
```

---

## M�DULO C � PROCESOS

> Un proceso pertenece a un ciclo de acreditaci�n.  
> El modelo (tradicional/flexible) viene del ciclo, no del proceso directamente.  
> **Solo 2 tipos posibles** (enum fijo en BD).  
> No se eliminan f�sicamente � solo se activan/desactivan.

### Tipos de proceso v�lidos (enum fijo):
| Valor | Descripci�n |
|---|---|
| `Autoevaluación` | Proceso de autoevaluaci�n de la carrera |
| `Compromiso de mejora` | Proceso de seguimiento de mejoras |

---

### C1. Listar procesos
```
GET /api/estructura/procesos
Authorization: Bearer <token>
```
**Filtro opcional:**
```
GET /api/estructura/procesos?ciclo_acreditacion_id=1
```
Respuesta incluye ciclo ? modelo ? carrera ? sede:
```json
[
  {
    "proceso_id": 1,
    "tipo_proceso": "Autoevaluación",
    "fecha_inicio": "2026-01-15",
    "fecha_finalizacion": "2026-12-31",
    "activo": true,
    "accreditation_cycle": {
      "nombre": "Ciclo 2024-2028",
      "modelo_estructura": { "tipo": "elemento_flexible", "nombre": "SINAES 2026..." },
      "career_campus": { "career": { "nombre": "Ingenier�a en Sistemas" }, "campus": { ... } }
    }
  }
]
```

---

### C2. Ver proceso espec�fico
```
GET /api/estructura/procesos/{id}
Authorization: Bearer <token>
```
Devuelve el proceso con toda la cadena: ciclo ? modelo ? carrera ? sede.

---

### C3. Crear proceso
```
POST /api/estructura/procesos
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{
    "ciclo_acreditacion_id": 1,
    "tipo_proceso": "Autoevaluación",
    "fecha_inicio": "2026-01-15",
    "fecha_finalizacion": "2026-12-31",
    "activo": true
}
```
> `fecha_inicio` y `fecha_finalizacion` son **opcionales** (nullable).  
> Si se env�an ambas, `fecha_finalizacion` debe ser >= `fecha_inicio`.

**Respuesta 201** � solo campos del proceso (sin relaciones):
```json
{
    "message": "Proceso creado exitosamente.",
    "data": {
        "proceso_id": 1,
        "ciclo_acreditacion_id": 1,
        "tipo_proceso": "Autoevaluación",
        "fecha_inicio": "2026-01-15",
        "fecha_finalizacion": "2026-12-31",
        "activo": true
    }
}
```
> Para ver relaciones completas ? `GET /api/estructura/procesos/{id}`

**Pruebas de error (422):**

| Caso | Body |
|------|------|
| Sin `ciclo_acreditacion_id` | `{ "tipo_proceso": "Autoevaluación" }` |
| Ciclo inexistente | `{ "ciclo_acreditacion_id": 9999, "tipo_proceso": "Autoevaluación" }` |
| Sin `tipo_proceso` | `{ "ciclo_acreditacion_id": 1 }` |
| `tipo_proceso` inv�lido | `{ "tipo_proceso": "otro" }` ? "debe ser: Autoevaluación o Compromiso de mejora" |
| Fecha fin < fecha inicio | `{ "fecha_inicio": "2026-12-31", "fecha_finalizacion": "2026-01-01" }` |

---

### C4. Editar proceso
```
PATCH /api/estructura/procesos/{id}
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{
    "tipo_proceso": "Compromiso de mejora",
    "fecha_inicio": "2026-03-01",
    "fecha_finalizacion": "2027-03-01"
}
```
**Respuesta 200:**
```json
{
    "message": "Proceso actualizado exitosamente.",
    "data": { "proceso_id": 1, "tipo_proceso": "Compromiso de mejora", ... }
}
```

---

### C5. Activar / Desactivar proceso
```
PATCH /api/estructura/procesos/{id}/active
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{ "active": false }
```**Respuesta 200:**
```json
{
    "message": "Proceso desactivado exitosamente.",
    "active": false
}
```
> Solo devuelve el nuevo estado — no el objeto completo.

---

### C6. Eliminar proceso (con confirmación)
> **Elimina en cascada TODO lo asociado:** autoevaluaciones, compromisos de mejora, asignaciones de evidencias, solicitudes de ampliación, aprobaciones y archivos físicos del disco.  
> El campo `confirmacion` debe ser el **`tipo_proceso` exacto** del proceso a eliminar.

**PASO 1 — Ver qué se eliminará** (sin `confirmacion`):
```
DELETE /api/estructura/procesos/{id}
Authorization: Bearer <token>   (Superusuario)
Content-Type: application/json
```
```json
{}
```
**Respuesta 422:**
```json
{
    "message": "Confirmación incorrecta...",
    "datos_a_eliminar": {
        "autoevaluaciones": 1,
        "compromisos_mejora": 0,
        "asignaciones_evidencia": 3,
        "solicitudes_ampliacion": 1,
        "aprobaciones": 2,
        "archivos": 4
    }
}
```

**PASO 2 — Confirmar con el `tipo_proceso` exacto:**
```
DELETE /api/estructura/procesos/{id}
Authorization: Bearer <token>   (Superusuario)
Content-Type: application/json
```
```json
{
    "confirmacion": "Autoevaluación"
}
```
o
```json
{
    "confirmacion": "Compromiso de mejora"
}
```
**Respuesta 200:**
```json
{
    "message": "Proceso eliminado exitosamente.",
    "datos_eliminados": {
        "autoevaluaciones": 1,
        "compromisos_mejora": 0,
        "asignaciones_evidencia": 3,
        "solicitudes_ampliacion": 1,
        "aprobaciones": 2,
        "archivos": 4
    }
}
```

**Pruebas de error:**

| Caso | Resultado esperado |
|------|-------------------|
| ID inexistente | 404 |
| `confirmacion` con tipo incorrecto | 422 con `datos_a_eliminar` |
| Sin header Authorization | 401 |
| Sin permiso `procesos.delete` | 403 |

---

## Flujo completo para probar desde cero (migrate:fresh --seed)

```
PASO 1 — Login
  POST /api/auth/login  →  guardar token

PASO 2 — El modelo tradicional ya existe (creado por seeder al instalar)
  GET  /api/estructura/modelos          →  ver modelo tradicional (id: 1)
  POST /api/estructura/modelos          →  tipo: "elemento_flexible"  →  guardar modelo_estructura_id (ej: 2)

PASO 3 — Verificar modelos
  GET  /api/estructura/modelos          →  ver los 2 modelos
  GET  /api/estructura/modelos/activos  →  misma lista
  GET  /api/estructura/modelos/1        →  ver uno especifico
  PUT  /api/estructura/modelos/1        →  editar version: "2018.1"
  PUT  /api/estructura/modelos/1        →  enviar tipo: "otro"  →  debe ser ignorado
  PATCH /api/estructura/modelos/1/active { "active": false }  →  desactivar
  PATCH /api/estructura/modelos/1/active { "active": true }   →  reactivar

PASO 4 — Crear elementos (usar modelo_estructura_id del tipo elemento_flexible = 2)
  POST /api/estructura/elementos  →  padre_id: null, tipo: "area",    modelo_estructura_id: 2  →  guardar elemento_id (ej: 1)
  POST /api/estructura/elementos  →  padre_id: 1,    tipo: "subarea", modelo_estructura_id: 2  →  guardar elemento_id (ej: 2)
  POST /api/estructura/elementos  →  padre_id: 2,    tipo: "pauta",   modelo_estructura_id: 2  →  guardar elemento_id (ej: 3)
  POST /api/estructura/elementos  →  padre de modelo diferente        →  422 padre de otro modelo
  GET  /api/estructura/elementos?modelo_estructura_id=2               →  lista filtrada
  PATCH /api/estructura/elementos/1/active { "active": false }  →  desactiva 1 + hijos en cascada
  PATCH /api/estructura/elementos/1/active { "active": true }   →  activa 1 + hijos en cascada
  DELETE /api/estructura/elementos/1  →  422 tiene hijos
  DELETE /api/estructura/elementos/3  →  200 OK es hoja

PASO 5 — Crear ciclo (requiere modelo_estructura_id valido)
  POST a ciclos con modelo_estructura_id del Paso 2  →  guardar ciclo_acreditacion_id

PASO 6 — Crear y operar procesos
  POST /api/estructura/procesos  →  tipo: "Autoevaluación",       con fechas
  POST /api/estructura/procesos  →  tipo: "Compromiso de mejora", sin fechas (nullable)
  POST /api/estructura/procesos  →  tipo: "invalido"              →  422
  POST /api/estructura/procesos  →  fecha_fin < fecha_ini         →  422
  GET  /api/estructura/procesos?ciclo_acreditacion_id=1           →  filtrado
  GET  /api/estructura/procesos/1                                 →  detalle completo con relaciones
  PATCH /api/estructura/procesos/1        →  cambiar fechas
  PATCH /api/estructura/procesos/1/active →  { "active": false }

PASO 7 — Eliminar proceso
  DELETE /api/estructura/procesos/1  {}                             →  422 muestra datos_a_eliminar
  DELETE /api/estructura/procesos/1  { "confirmacion": "Autoevaluación" }   →  200 eliminado
  DELETE /api/estructura/procesos/1  { "confirmacion": "Compromiso de mejora" } →  422 tipo incorrecto

PASO 8 — Eliminar modelo (solo elemento_flexible, no el tradicional ID=1)
  DELETE /api/estructura/modelos/2  {}                                          →  422 muestra datos_a_eliminar (incluye ciclos, procesos, etc.)
  DELETE /api/estructura/modelos/2  { "confirmacion": "nombre incorrecto" }     →  422
  DELETE /api/estructura/modelos/2  { "confirmacion": "SINAES 2026 - Estructura Flexible" }  →  200 eliminado
  DELETE /api/estructura/modelos/1  { "confirmacion": "SINAES 2018 - Estructura Tradicional" } →  422 no se puede eliminar el tradicional
```

## Errores comunes

| C�digo | Causa probable |
|--------|---------------|
| 401 | Token faltante o expirado � hacer login de nuevo |
| 403 | Usuario sin rol Superusuario para endpoints protegidos |
| 404 | ID no existe en BD |
| 422 | Validaci�n fallida � ver campo `errors` en la respuesta para detalle |
| 500 | Error interno � revisar `storage/logs/laravel.log` |
