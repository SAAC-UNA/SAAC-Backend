# Postman — Aprobación de Elementos (HU-010 modelo flexible)

> **Comportamiento CASCADE:** Al aprobar o rechazar un nodo, el sistema crea
> automáticamente un registro `APROBACION_ELEMENTO` para ese nodo **y todos
> sus descendientes activos**. El backend es agnóstico al nivel — el frontend
> decide en qué nivel mostrar el botón.

---

## Auth: login primero

```
POST /api/auth/login
Body: { "cedula": "222222222", "password": "password123" }
```
Copia el `token` y ponlo en el header de todas las demás:
```
Authorization: Bearer <token>
```

---

## Usuarios disponibles

| Nombre | Cédula | Password | Rol |
|---|---|---|---|
| Naydelin Nayeli Jiron Castellon | 801490957 | password123 | Superusuario |
| Jose Andres Jara Arias | 208330811 | password123 | Administrador |
| Encargado Acreditación Test | 222222222 | password123 | Encargado de Acreditación |
| Marisol Hidalgo Murillo | 118620669 | password123 | Profesor |

> Los roles **Superusuario**, **Administrador** y **Encargado de Acreditación** pueden aprobar/rechazar.  
> El **Profesor** solo puede ver.

---

## Árbol de elementos creado por el seeder

IDs reales (última ejecución de `AprobacionElementosTestSeeder`):

```
TEST-D1 (dimension) → ID: 5   ← aprueba/rechaza TODO el árbol (cascada)
  └── TEST-P1  (pauta)  → ID: 6   ← aprueba/rechaza pauta + F1.1 + F1.2
        ├── TEST-F1.1 (fuente) → ID: 7   ← aprueba/rechaza solo esta fuente
        └── TEST-F1.2 (fuente) → ID: 8   ← aprueba/rechaza solo esta fuente
```

| ID | Nodo | Nodos afectados al aprobar | Registros creados |
|---|---|---|---|
| 7 | TEST-F1.1 (fuente) | Solo F1.1 | 1 |
| 8 | TEST-F1.2 (fuente) | Solo F1.2 | 1 |
| 6 | TEST-P1 (pauta) | P1 + F1.1 + F1.2 | 3 |
| 5 | TEST-D1 (dimension) | D1 + P1 + F1.1 + F1.2 | 4 |

> Re-ejecutar el seeder actualiza los IDs. Para ver los vigentes:
> ```sql
> SELECT elemento_id, nomenclatura, tipo, padre_id
> FROM ELEMENTO WHERE nomenclatura LIKE 'TEST-%' ORDER BY elemento_id;
> ```

---

## Flujo de prueba recomendado

**Paso 0 — limpiar aprobaciones previas de los nodos TEST** (necesario para
que los escenarios de éxito no devuelvan 422):

```sql
DELETE FROM APROBACION_ELEMENTO
WHERE elemento_id IN (SELECT elemento_id FROM ELEMENTO WHERE nomenclatura LIKE 'TEST-%');
```

Luego ejecutar las pruebas en el orden que se describe abajo.

---

## Endpoints

### 1. Listar todas las aprobaciones de elementos

```
GET /api/aprobaciones-elementos
```

**Respuesta esperada:** array con los registros de `APROBACION_ELEMENTO`.

---

### 2. Ver una aprobación específica

```
GET /api/aprobaciones-elementos/3
```

Cambia `3` por el `aprobacion_elemento_id` real devuelto por el seeder.

---

### 3. Aprobar una fuente sola (sin cascada)

Aprobar `TEST-F1.1` (ID: 7) — nodo hoja, afecta solo a sí mismo.

```
POST /api/elementos/7/aprobar
Content-Type: application/json
Authorization: Bearer <token>

{
  "proceso_id": 1,
  "comentario": "Documentación completa y verificada"
}
```

**Respuesta 201:**
```json
{
  "success": true,
  "message": "Elemento aprobado exitosamente.",
  "data": {
    "aprobacion_elemento_id": 5,
    "elemento_id": 7,
    "proceso_id": 1,
    "estado": "aprobado",
    "comentario": "Documentación completa y verificada"
  }
}
```

> Solo se crea **1** registro en `APROBACION_ELEMENTO`.

---

### 4. Aprobar una pauta (cascada a sus fuentes)

Aprobar `TEST-P1` (ID: 6) — afecta pauta + F1.1 + F1.2.

> Primero limpia las aprobaciones previas del paso anterior (SQL del Paso 0), si no TEST-F1.1 ya está aprobada y el cascade la sobreescribirá igualmente con `updateOrCreate`.

```
POST /api/elementos/6/aprobar
Content-Type: application/json
Authorization: Bearer <token>

{
  "proceso_id": 1,
  "comentario": "Pauta completa — todas las fuentes verificadas"
}
```

**Respuesta 201** (retorna el registro raíz de la cadena):
```json
{
  "success": true,
  "message": "Elemento aprobado exitosamente.",
  "data": {
    "aprobacion_elemento_id": 6,
    "elemento_id": 6,
    "proceso_id": 1,
    "estado": "aprobado",
    "comentario": "Pauta completa — todas las fuentes verificadas"
  }
}
```

> Se crean/actualizan **3** registros en `APROBACION_ELEMENTO`
> (pauta ID 6, fuente ID 7, fuente ID 8), todos con el mismo `comentario`.

**Verificar en BD:**
```sql
SELECT ae.aprobacion_elemento_id, e.nomenclatura, ae.estado
FROM APROBACION_ELEMENTO ae
JOIN ELEMENTO e ON e.elemento_id = ae.elemento_id
WHERE ae.proceso_id = 1
ORDER BY ae.aprobacion_elemento_id;
```

---

### 5. Aprobar la dimensión raíz (cascada total)

Aprobar `TEST-D1` (ID: 5) — afecta dimensión + pauta + F1.1 + F1.2.

```
POST /api/elementos/5/aprobar
Content-Type: application/json
Authorization: Bearer <token>

{
  "proceso_id": 1,
  "comentario": "Dimensión completa — aprobación global"
}
```

**Respuesta 201:**
```json
{
  "success": true,
  "message": "Elemento aprobado exitosamente.",
  "data": {
    "aprobacion_elemento_id": 9,
    "elemento_id": 5,
    "estado": "aprobado"
  }
}
```

> Se crean/actualizan **4** registros (D1, P1, F1.1, F1.2).

---

### 6. Rechazar una fuente sola ❌

```
POST /api/elementos/8/rechazar
Content-Type: application/json
Authorization: Bearer <token>

{
  "proceso_id": 1,
  "comentario": "Falta firma del responsable académico",
  "fecha_limite": "2026-04-30"
}
```

**Respuesta 201:**
```json
{
  "success": true,
  "message": "Elemento rechazado exitosamente.",
  "data": {
    "aprobacion_elemento_id": 10,
    "elemento_id": 8,
    "estado": "rechazado",
    "comentario": "Falta firma del responsable académico"
  }
}
```

---

### 7. Rechazar una pauta (cascada a sus fuentes) ❌

```
POST /api/elementos/6/rechazar
Content-Type: application/json
Authorization: Bearer <token>

{
  "proceso_id": 1,
  "comentario": "Documentación insuficiente en todas las fuentes",
  "fecha_limite": "2026-05-15"
}
```

> Se crean/actualizan **3** registros (P1, F1.1, F1.2) todos como `rechazado`.

---

## Casos de error — 422

### Elemento ya en ese estado (valida solo el nodo raíz)

Si `TEST-F1.1` (ID: 7) ya está aprobada:

```
POST /api/elementos/7/aprobar
Body: { "proceso_id": 1 }
```
```json
{ "success": false, "message": "El elemento ya está aprobado." }
```

> La validación de estado duplicado aplica **solo al nodo raíz** de la
> petición. Si un descendiente ya tiene ese estado, el cascade lo
> sobreescribe con `updateOrCreate` sin error.

---

### Elemento no existe

```
POST /api/elementos/99999/aprobar
Body: { "proceso_id": 1 }
```
```json
{ "success": false, "message": "El elemento especificado no existe." }
```

---

### Sin autenticación — 401

```
POST /api/elementos/7/aprobar   (sin header Authorization)
```
```json
{ "message": "Unauthenticated." }
```

---

### Sin permiso (rol Profesor) — 403

Hacer login con Marisol (cédula `118620669`) e intentar aprobar:

```
POST /api/elementos/7/aprobar
Body: { "proceso_id": 1 }
```
```json
{ "success": false, "message": "No tienes permiso para aprobar este elemento." }
```

---

## Diferencia con el sistema de Criterios (modelo tradicional)

| | Modelo Tradicional (`/criterios`) | Modelo Flexible (`/elementos`) |
|---|---|---|
| Unidad aprobada | Criterio SINAES | Elemento (cualquier nivel) |
| Tabla | `APROBACION_CRITERIO` | `APROBACION_ELEMENTO` |
| Cascada | Aprueba sus `APROBACION_EVIDENCIA` | Aprueba todos los descendientes activos |
| Restricción | Ninguna sobre nivel | Ninguna (backend agnóstico al nivel) |
| Asignaciones | Actualiza `EVIDENCIA_ASIGNACION` al rechazar | TODO HU-009: actualizará `ELEMENTO_ASIGNACION` |

---

## Limpiar y volver a probar

**Solo las aprobaciones TEST:**
```sql
DELETE FROM APROBACION_ELEMENTO
WHERE elemento_id IN (SELECT elemento_id FROM ELEMENTO WHERE nomenclatura LIKE 'TEST-%');
```

**Re-ejecutar el seeder completo** (recrea árbol + asignaciones + aprobaciones de ejemplo):
```
php artisan db:seed --class=AprobacionElementosTestSeeder
```
