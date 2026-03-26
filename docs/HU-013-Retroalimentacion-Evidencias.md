# HU-013: Retroalimentación de Evidencias — Backend

## 📋 Descripción General

El encargado de acreditación puede revisar una evidencia enviada por un profesor
y marcarla como **observada** (requiere corrección) o **validada** (aprobada),
adjuntando obligatoriamente un comentario textual. La operación es atómica y dispara
notificaciones automáticas a todos los profesores con asignación activa sobre esa evidencia.

**Estado:** ✅ Completado  
**Branch:** `HU_013_Retroalimentacion_de_evidencias`  
**Commits:** `1c7965d` → `6f27a00` → `2a43a87`

---

## 🎯 Criterios de Aceptación Cumplidos

- ✅ Solo roles autorizados pueden retroalimentar (`Encargado de Acreditación`, `Administrador`, `Superusuario`)
- ✅ Solo se puede retroalimentar evidencias en estado revisable (bloquea `Pendiente`)
- ✅ Los únicos estados que el evaluador puede asignar son `Observada` y `Validada`
- ✅ El comentario es obligatorio (mín. 5 chars, máx. 800 chars)
- ✅ El cambio de estado y el comentario se guardan en una transacción atómica
- ✅ La acción queda registrada en bitácora vía `AuditLogService`
- ✅ Los profesores con asignación activa reciben notificación interna + email automáticamente
- ✅ La respuesta incluye la evidencia actualizada con sus `comentarios[]`

---

## 🔗 Endpoint

```http
POST /api/estructura/evidencias/{id}/retroalimentacion
Authorization: Bearer {token}
Content-Type: application/json
```

> Se usa **POST** (no PATCH) porque la operación **no es idempotente**: cada llamada
> crea un nuevo comentario en la tabla `COMENTARIO`, además de actualizar el estado.

### Body JSON

| Campo       | Tipo   | Requerido | Restricciones                        |
|-------------|--------|-----------|--------------------------------------|
| `estado`    | string | ✅        | Solo `"Observada"` o `"Validada"`    |
| `comentario`| string | ✅        | Mínimo 5 caracteres, máximo 800      |

### Ejemplo de Request

```json
{
    "estado": "Observada",
    "comentario": "Falta adjuntar el acta firmada del comité. Por favor corregir."
}
```

### Respuesta Exitosa — 200 OK

```json
{
    "data": {
        "evidencia_id": 12,
        "estado": "Observada",
        "nomenclatura": "E-C1-01",
        "descripcion": "Actas de reunión del comité curricular",
        "activo": true,
        "fecha_publicacion": "2026-03-01T10:00:00.000Z",
        "updated_at": "2026-03-22T15:30:00.000Z",
        "criterion": { ... },
        "responsables": [ ... ],
        "archivos_count": 2,
        "enlaces_count": 1,
        "comentarios": [
            {
                "id": 45,
                "texto": "Falta adjuntar el acta firmada del comité. Por favor corregir.",
                "usuario_id": 7,
                "autor": "María García",
                "fecha": "2026-03-22T15:30:00.000Z"
            }
        ]
    }
}
```

### Respuestas de Error

| Código | Caso                                                                    |
|--------|-------------------------------------------------------------------------|
| 401    | Token ausente o inválido                                                |
| 403    | El usuario no tiene rol autorizado                                      |
| 404    | Evidencia no encontrada                                                 |
| 422    | Validación fallida (campos inválidos) o estado no revisable (`pendiente`)|

---

## 🏗️ Archivos Implementados / Modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Http/Requests/RetroalimentacionRequest.php` | Nuevo | Valida `estado` y `comentario` del body |
| `app/Http/Controllers/EvidenceController.php` | Modificado | Método `retroalimentar()` |
| `app/Services/EvidenceService.php` | Modificado | Método `retroalimentar()` con transacción + notificación |
| `app/Models/Evidence.php` | Modificado | Añade `ESTADOS` con `Observada`/`Validada`, relación `comments()`, fix `activeAssignments()` |
| `app/Http/Resources/EvidenceResource.php` | Modificado | Añade campo `comentarios[]` en la respuesta |
| `routes/api.php` | Modificado | Registra la ruta `POST .../retroalimentacion` |

---

## ⚙️ Lógica de Negocio (EvidenceService::retroalimentar)

La operación ejecuta **5 pasos** en orden:

### Pasos 1–4 — Transacción atómica (`DB::transaction`)

1. **Actualiza el estado** → `$evidence->update(['estado' => $data['estado']])`
2. **Guarda el comentario** → `Comment::create(...)` con relación polimórfica a `Evidence`
3. **Registra en bitácora** → `AuditLogService::log('retroalimentar', ..., 'Evidencias', $reviewer->usuario_id)`
4. **Invalida el caché** → `Cache::forget('evidencias.all')`
5. **Retorna** la evidencia fresca con `[...WITH_BASE, 'comments.user']`

> Si cualquiera de los pasos 1–4 falla, la transacción hace rollback completo.

### Paso 5 — Notificación (fuera de la transacción)

- **Aislado en `try/catch(\Throwable)`** para que un fallo de email/SMTP no revierta el cambio de estado ya guardado.
- Carga `activeAssignments.user` de la evidencia para obtener los profesores destinatarios.
- Llama a `NotificationService::createMany(...)` con:
  - `tipo_evento`: `TIPO_DEVOLUCION_OBSERVACION` si estado es `Observada` (evento crítico → `CANAL_AMBOS` nativo); `TIPO_APROBACION_EVIDENCIA` si es `Validada`
  - `forzar_email: true` en ambos casos — garantiza notificación interna (campana) + email
- Si falla, el error se loguea en `laravel.log` sin interrumpir la respuesta al cliente.

---

## 🔒 Control de Acceso

### Roles Autorizados

| Rol                      | ¿Puede retroalimentar? |
|--------------------------|------------------------|
| Superusuario             | ✅                     |
| Administrador            | ✅                     |
| Encargado de Acreditación| ✅                     |
| Profesor                 | ❌ (403)               |
| Evaluador                | ❌ (403)               |

### Estados Revisables

| Estado de la evidencia | ¿Se puede retroalimentar? |
|------------------------|---------------------------|
| `Pendiente`            | ❌ — lanza 422             |
| `En Proceso`           | ✅                        |
| `Completado`           | ✅                        |
| `Vencido`              | ✅ (HU de ampliación de plazo lo permite) |
| `Aprobado`             | ✅                        |
| `Rechazado`            | ✅                        |
| `Observada`            | ✅                        |
| `Validada`             | ✅                        |

---

## 🛠️ Bugs Corregidos Durante el Desarrollo

### Bug 1 — Columna `activo` inexistente en `EVIDENCIA_ASIGNACION`

- **Síntoma**: `activeAssignments()` generaba `Unknown column 'activo'` → HTTP 500 al notificar.
- **Causa**: La query usaba `->where('activo', true)` pero la tabla `EVIDENCIA_ASIGNACION` no tiene esa columna.
- **Fix** (`6f27a00`): Cambiado a `->whereIn('estado', ['Pendiente', 'En Progreso'])`, consistente con el resto del código.

### Bug 2 — HTTP 500 después de un commit exitoso

- **Síntoma**: El estado y el comentario se guardaban correctamente, pero el endpoint retornaba 500.
- **Causa**: El paso 5 (notificación) no tenía `try/catch`. La excepción del Bug 1 propagaba hacia arriba.
- **Fix** (`6f27a00`): Envuelto el paso 5 en `try/catch(\Throwable)` con `logger()->error(...)`.

---

## 📬 Notificaciones Generadas

| Estado asignado | `tipo_evento`                | Canal resultante | Email |
|-----------------|------------------------------|------------------|-------|
| `Observada`     | `TIPO_DEVOLUCION_OBSERVACION` | `CANAL_AMBOS`    | ✅    |
| `Validada`      | `TIPO_APROBACION_EVIDENCIA`  | `CANAL_AMBOS`*   | ✅    |

*`TIPO_APROBACION_EVIDENCIA` no está en `$eventosCriticos` nativo, pero `forzar_email: true` en `determinarCanal()` usa lógica OR, forzando `CANAL_AMBOS` igualmente.

Las notificaciones se persisten en la tabla `NOTIFICACION` (campana interna) y envían email vía `TestNotificationMail` (SMTP Gmail, puerto 587 TLS).

---

## 🧪 Pruebas en Postman

### Caso exitoso — `observada`

```http
POST /api/estructura/evidencias/12/retroalimentacion
Authorization: Bearer {token_encargado}
Content-Type: application/json

{
    "estado": "Observada",
    "comentario": "Falta la firma del coordinador en el documento principal."
}
```

**Verificar en respuesta:**
- `data.estado` = `"Observada"`
- `data.comentarios[0].texto` contiene el comentario enviado
- `data.comentarios[0].autor` contiene el nombre del evaluador

### Caso de error — estado no revisable

```http
POST /api/estructura/evidencias/{id_pendiente}/retroalimentacion
Authorization: Bearer {token_encargado}

{ "estado": "Validada", "comentario": "test de validación" }
```

**Respuesta esperada:** `422` con mensaje indicando que la evidencia está en `Pendiente`.

### Caso de error — sin autorización

```http
POST /api/estructura/evidencias/12/retroalimentacion
Authorization: Bearer {token_profesor}

{ "estado": "Validada", "comentario": "prueba" }
```

**Respuesta esperada:** `403 No autorizado para retroalimentar evidencias.`

---

## ⚠️ Alcance y Compatibilidad con el Modelo Flexible

### HU-013 aplica únicamente al modelo `tradicional`

El sistema soporta dos modelos de estructura:

| Modelo | Tipo | Jerarquía |
|--------|------|-----------|
| SINAES 2018 | `tradicional` | DIMENSION → COMPONENTE → CRITERIO → **EVIDENCIA** |
| SINAES 2026 | `elemento_flexible` | ELEMENTO (árbol libre: dimensión → pauta → **fuente**) |

HU-013 opera sobre la tabla `EVIDENCIA`, que tiene `criterio_id` como FK estructural. **No existe ninguna relación en BD entre `EVIDENCIA` y `ELEMENTO`** — son mundos completamente separados.

### Por qué no rompe nada del modelo flexible

El código de `retroalimentar()` opera directamente sobre una instancia de `Evidence` ya encontrada. No hace ninguna consulta a `DIMENSION`, `COMPONENTE`, `CRITERIO` ni a `ELEMENTO`. Por tanto, **no hay ningún punto de intersección ni riesgo de colisión** con el modelo flexible.

### Qué son las "fuentes" en el modelo flexible

En el modelo `elemento_flexible`, los nodos hoja del árbol se llaman `fuente` (tipo libre del elemento). Son el equivalente conceptual a una evidencia del modelo tradicional:

```
MODELO TRADICIONAL                     MODELO FLEXIBLE
──────────────────                     ───────────────
CRITERIO                               ELEMENTO (tipo='pauta', categoria=A/B/C/D)
  └── EVIDENCIA  ← entidad operativa       └── ELEMENTO (tipo='fuente')  ← solo metadata
        ├── estado                                    (sin estado, sin archivos,
        ├── EVIDENCIA_ASIGNACION                       sin asignaciones en BD)
        └── ARCHIVO
```

La diferencia clave es que `fuente` es **solo descriptiva** — define qué tipo de fuente de información se debe recopilar en esa pauta, pero no es una entidad operativa. No tiene estado, ni archivos adjuntos, ni asignaciones de responsables en la base de datos actual.

### Lo que faltaría para que HU-013 aplique al flexible

Para que la retroalimentación tenga sentido en el modelo flexible, primero habría que construir la infraestructura operativa equivalente:

| Paso | Qué crear | Equivalente tradicional |
|------|-----------|------------------------|
| 1 | Tabla `ELEMENTO_ASIGNACION` (FK a `elemento_id` + `usuario_id` + `estado`) | `EVIDENCIA_ASIGNACION` |
| 2 | Tabla `ELEMENTO_ARCHIVO` (FK a `elemento_id` + ruta) | `ARCHIVO` con `evidencia_id` |
| 3 | Campo `estado` en `ELEMENTO` o tabla `ELEMENTO_ESTADO` | Campo `estado` en `EVIDENCIA` |
| 4 | Endpoint `POST /estructura/elementos/{id}/retroalimentacion` | El de HU-013 |

La lógica de servicio de HU-013 (comentario polimórfico, bitácora, `NotificationService`) es **reutilizable** tal cual — `Comment::create()` usa relación polimórfica y puede apuntar a `StructureElement` igual que apunta a `Evidence`.
