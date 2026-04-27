# HU-026 — Publicación de Informe de Acreditación (Backend)

## Estado Actual (2026-04-22)

**Última actualización:** 2026-04-22
**Cambios recientes:**

- ✅ Eliminada restricción que impedía múltiples informes por proceso
- ✅ Modificado seeder para no crear informes falsos
- ✅ Sistema permite múltiples informes de acreditación por proceso/ciclo

## Endpoints

Base URL: `http://localhost:8000/api`

| Método | Endpoint                                      | Auth | Permiso requerido                 | Descripción                            |
| ------ | --------------------------------------------- | ---- | --------------------------------- | -------------------------------------- |
| GET    | `/informes-acreditacion`                      | No   | —                                 | Listado público de informes publicados |
| GET    | `/ciclos/{cycle}/informe`                     | No\* | `informes_acreditacion.view`\*    | Informe de un ciclo concreto           |
| POST   | `/ciclos/{cycle}/informe`                     | Sí   | `informes_acreditacion.publish`   | Publicar informe de acreditación       |
| PATCH  | `/informes-acreditacion/{report}/despublicar` | Sí   | `informes_acreditacion.unpublish` | Despublicar informe                    |

---

## Cuerpos de Request

### POST — Publicar informe

```json
{
    "proceso_id": 12,
    "archivo": "binary data (multipart/form-data)",
    "observaciones": "Informe aprobado en sesión ordinaria del 15 de abril."
}
```

| Campo           | Tipo    | Requerido | Notas                                     |
| --------------- | ------- | --------- | ----------------------------------------- |
| `proceso_id`    | integer | Sí        | ID del proceso de autoevaluación asociado |
| `archivo`       | file    | Sí        | El archivo PDF de la resolución           |
| `observaciones` | string  | No        | Nullable, max 500 chars                   |

### PATCH — Despublicar informe

```json
{
    "motivo": "Se detectó un error en la resolución indicada."
}
```

| Campo    | Tipo   | Requerido | Notas                   |
| -------- | ------ | --------- | ----------------------- |
| `motivo` | string | No        | Nullable, max 500 chars |

---

## Respuestas

### Publicar — 201 Created

```json
{
    "data": {
        "informe_archivo_id": 1,
        "estado": "publicado",
        "fecha_publicacion": "2026-04-18T10:00:00Z",
        "observaciones": "...",
        "proceso": { "proceso_id": 12, "nombre": "Autoevaluación 2026" },
        "archivo": {
            "nombre_original": "informe.pdf",
            "tipo_mime": "application/pdf",
            "tamanio": 102400,
            "is_publico": true,
            "url_publica": "..."
        },
        "publicado_por": { "usuario_id": 1, "nombre": "Admin UNA" },
        "created_at": "2026-04-18T10:00:00Z"
    }
}
```

### Listado público — 200 OK

```json
{
    "data": [
        {
            /* AccreditationReportResource */
        }
    ],
    "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 }
}
```

### Errores comunes

| Código | Causa                                                                                        |
| ------ | -------------------------------------------------------------------------------------------- |
| `401`  | Token requerido y no enviado                                                                 |
| `403`  | Sin permiso para la acción                                                                   |
| `404`  | Ciclo o informe no encontrado / ciclo sin informe                                            |
| `422`  | Validación fallida (ver campo `errors`) o regla de negocio no cumplida (ver campo `message`) |

**Nota:** El sistema permite múltiples informes por proceso. No hay restricción que impida publicar más de un informe por ciclo/proceso.

---

## Filtros del listado público

`GET /informes-acreditacion?carrera_id=2&sede_id=1&per_page=10`

| Parámetro    | Tipo    | Descripción                     |
| ------------ | ------- | ------------------------------- |
| `carrera_id` | integer | Filtra por carrera              |
| `sede_id`    | integer | Filtra por sede                 |
| `per_page`   | integer | Resultados por página (máx. 25) |

---

## Cambios Recientes (2026-04-22)

### Eliminación de Restricción de Múltiples Informes

**Problema:** El sistema originalmente impedía publicar múltiples informes por proceso/ciclo.

**Solución:** Se eliminó la validación que verificaba si ya existía un informe para el proceso. Ahora el sistema permite múltiples informes de acreditación por proceso.

**Archivos modificados:**

- `app/Services/AccreditationReportService.php` - Eliminada validación de unicidad
- Documentación actualizada para reflejar que múltiples informes son permitidos

### Modificación del Seeder

**Problema:** El seeder creaba informes falsos/demo que poblaban la base de datos con datos de prueba.

**Solución:** Se modificó `AccreditationReportSeeder` para no crear ningún informe falso. La tabla `INFORME_ARCHIVO` queda vacía y requiere que todos los informes sean subidos manualmente.

**Comportamiento actual:**

```bash
php artisan db:seed --class=AccreditationReportSeeder
# Output: 📄 AccreditationReportSeeder — Saltando creación de informes de demostración.
#         ✅ 0 informe(s) de acreditación creado(s). Los informes deben subirse manualmente.
```

**Archivos modificados:**

- `database/seeders/AccreditationReportSeeder.php` - Removida lógica de creación de datos demo

---

## Cambios Estructurales (Simplificación)

Se eliminaron las siguientes columnas de la tabla `INFORME_ARCHIVO` (anteriormente `INFORME_ACREDITACION`) para simplificar el modelo:

- `ciclo_acreditacion_id` (se usa `proceso_id` para relacionar con el proceso)
- `archivo_id` (la metadata del archivo se guarda directamente en la tabla)
- `numero_resolucion`
- `fecha_resolucion`
- `vigencia_desde`
- `vigencia_hasta` (el link público ahora expira por defecto en 1 año)
- `esta_acreditada`

La tabla se renombró a `INFORME_ARCHIVO` para reflejar mejor su propósito de almacenar archivos asociados a informes.

---

## Archivos creados / modificados

| Archivo                                                                        | Acción     | Descripción                                          |
| ------------------------------------------------------------------------------ | ---------- | ---------------------------------------------------- |
| `database/migrations/2026_04_18_000001_create_accreditation_reports_table.php` | Creado     | Tabla `INFORME_ACREDITACION`                         |
| `database/migrations/2026_04_18_000002_add_publish_action_types.php`           | Creado     | Inserta tipos de acción en `TIPO_ACCION`             |
| `app/Models/AccreditationReport.php`                                           | Creado     | Modelo Eloquent con constantes de estado y helpers   |
| `app/Http/Controllers/AccreditationReportController.php`                       | Creado     | Controlador de los 4 endpoints                       |
| `app/Http/Requests/ListAccreditationReportsRequest.php`                        | Creado     | Validación de filtros del endpoint público           |
| `app/Http/Requests/PublishAccreditationReportRequest.php`                      | Creado     | Validación y reglas de negocio del publish           |
| `app/Http/Requests/UnpublishAccreditationReportRequest.php`                    | Creado     | Validación del motivo de despublicación              |
| `app/Http/Resources/AccreditationReportResource.php`                           | Creado     | Transformación JSON del modelo                       |
| `app/Policies/AccreditationReportPolicy.php`                                   | Creado     | Autorización (view / publish / unpublish / download) |
| `app/Services/AccreditationReportService.php`                                  | Creado     | Lógica de negocio (publish / unpublish / queries)    |
| `app/Services/AccreditationReportService.php`                                  | Modificado | Eliminada restricción de múltiples informes          |
| `database/seeders/AccreditationReportSeeder.php`                               | Modificado | Removida creación de datos demo                      |
| `config/permissions.php`                                                       | Modificado | Módulo `informes_acreditacion` con 4 permisos        |
| `routes/api.php`                                                               | Modificado | 4 rutas HU-026 registradas                           |

---

## Bitácora (Fase 6) — Decisiones de diseño

Esta sección documenta los cambios y el **razonamiento** de cada decisión para que el equipo tenga contexto completo.

### Por qué se creó una migración separada para los tipos de acción

El catálogo `TIPO_ACCION` es poblado por `ActionTypeSeeder`, que solo corre en `db:seed`. Sin embargo, en entornos que ya tienen la BD creada (staging, producción) no se re-ejecuta el seeder completo. Para garantizar que `publicar` y `despublicar` existan en **cualquier entorno** sin tener que correr seeds manualmente, se creó la migración `2026_04_18_000002_add_publish_action_types.php`.

La migración es **idempotente**: verifica con `EXISTS` antes de insertar, por lo que no falla si los registros ya están (ej. si alguien sí corrió el seeder en ese entorno).

Se actualizó también el `ActionTypeSeeder` para que los entornos que hacen `migrate:fresh --seed` queden igualmente consistentes.

### Por qué los logs van DESPUÉS del try/catch, no dentro

```php
// ✅ Correcto — solo se loguea si la operación fue exitosa
try {
    $report = $this->service->publishReport(...);
} catch (\InvalidArgumentException $exception) {
    return response()->json(['message' => $exception->getMessage()], 422);
}
AuditLogService::log('publicar', "...", 'Informe Acreditación');
```

Si el log estuviera dentro del try, cualquier excepción del service interrumpiría la operación antes de llegar al log — lo cual es correcto. Pero si estuviera antes del `return` dentro del try, una excepción en el log mismo podría ocultar errores. Colocarlo **después del try/catch** es el patrón estándar en el proyecto: solo se registra en bitácora cuando la acción **completó exitosamente**.

### Por qué el texto del log usa `nombre_original` en lugar del ID de BD

El campo `detalle` del log se muestra tal cual en el modal de bitácora del frontend (`AuditLogDetailModal.tsx`). El nombre original del archivo es un identificador **legible para humanos**.

El patrón del proyecto sí incluye IDs en el detalle (ej. `"Se eliminó el ciclo... (ID: {$id})"`), lo cual es válido para trazabilidad interna — la bitácora solo es accesible para usuarios con `bitacora.view`. Para HU-026 se optó por el nombre del archivo porque aporta más contexto sin sacrificar trazabilidad.

### Tipos de acción en BD (post HU-026)

| Descripción              | Badge frontend   | Color   |
| ------------------------ | ---------------- | ------- |
| `crear`                  | Crear            | Teal    |
| `editar`                 | Editar           | Warning |
| `eliminar`               | Eliminar         | Error   |
| `consultar`              | Consultar        | Slate   |
| `login`                  | Login            | Verde   |
| `logout`                 | Logout           | Error   |
| `login_fallido`          | Login fallido    | Rose    |
| `activar`                | Activar          | Teal    |
| `desactivar`             | Desactivar       | Gris    |
| `asignar_rol`            | Asignar rol      | Morado  |
| `asignar_permisos`       | Asignar permisos | Indigo  |
| `exportar`               | Exportar         | Teal    |
| `asignar`                | Asignar          | Info    |
| `notificar`              | Notificar        | Info    |
| `notificar_fallido`      | Notif. fallida   | Rose    |
| `retroalimentar`         | Retroalimentar   | Morado  |
| `publicar` _(HU-026)_    | Publicar         | Verde   |
| `despublicar` _(HU-026)_ | Despublicar      | Warning |

---

## Cambios en el Frontend (SAAC-Frontend)

### Archivo modificado

**`src/Constants/StatusBadges.ts`** — constante `AUDIT_ACTION_BADGE`

### Qué se cambió

Se agregaron 5 entradas nuevas al mapa `AUDIT_ACTION_BADGE`:

```ts
// Antes — solo existían estas entradas:
exportar:   { label: 'Exportar', colorClasses: 'bg-teal-ring text-teal' },
asignar:    { label: 'Asignar',  colorClasses: 'bg-info-ring text-info' },
// (fin del objeto)

// Después — se agregaron:
notificar:         { label: 'Notificar',      colorClasses: 'bg-info-ring text-info' },
notificar_fallido: { label: 'Notif. fallida', colorClasses: 'bg-rose-ring text-rose' },
retroalimentar:    { label: 'Retroalimentar', colorClasses: 'bg-morado-ring text-morado' },
publicar:          { label: 'Publicar',       colorClasses: 'bg-verde-ring text-verde' },
despublicar:       { label: 'Despublicar',    colorClasses: 'bg-warning-ring text-warning' },
```

### Por qué se hizo este cambio

El componente `AuditLogDetailModal.tsx` usa este mapa para renderizar el **badge del tipo de acción** en la bitácora:

````tsx
<StatusBadge
  label={AUDIT_ACTION_BADGE[log.tipo_accion.descripcion.toLowerCase()]?.label
```

---

## Barrido de seguridad — eliminación de fuga de mensajes de excepción

**Commit:** `63e2458`
**Fecha:** 2026-04-18

### Problema

Múltiples controladores devolvían `$e->getMessage()` / `$qe->getMessage()` directamente en la respuesta JSON de errores 500, exponiendo trazas SQL internas, nombres de tablas, columnas y mensajes de excepción del servidor al cliente.

### Archivos corregidos (14)

| Archivo | Cambios |
|---------|---------|
| `app/Services/AccreditationCycleService.php` | `throw new \Exception(...)` → `throw new \InvalidArgumentException(...)` para regla de negocio |
| `app/Http/Controllers/AccreditationCycleController.php` | Catch 3 niveles: `\InvalidArgumentException` → 422, `\Exception` → `Log::error` + 500 genérico |
| `app/Http/Controllers/CampusController.php` | Elimina `'error' => $e->getMessage()` del fallback 500 de `QueryException`; agrega `Log::error` |
| `app/Http/Controllers/CareerController.php` | Ídem |
| `app/Http/Controllers/DimensionController.php` | Ídem |
| `app/Http/Controllers/CriterionController.php` | Ídem |
| `app/Http/Controllers/ComponentController.php` | Ídem |
| `app/Http/Controllers/UniversityController.php` | Ídem |
| `app/Http/Controllers/ImprovementCommitmentController.php` | Elimina `'details' => $exception->getMessage()` de 3 bloques `QueryException` |
| `app/Http/Controllers/CriterionApprovalController.php` | 7 bloques catch: agrega `\InvalidArgumentException` + `\LogicException` → 422, `\Exception` → `Log::error` + 500 genérico |
| `app/Http/Controllers/ElementApprovalController.php` | Ídem — 4 bloques catch corregidos |
| `app/Http/Controllers/EvidenceAssignmentController.php` | Elimina `'error' => $e->getMessage()` del catch genérico |
| `app/Http/Controllers/EvidenceController.php` | Elimina `'error' => $qe->getMessage()` del fallback 500 de `QueryException` |
| `app/Http/Controllers/FileController.php` | Loops de upload: split `\InvalidArgumentException` (mensaje de negocio → visible) / `\Exception` genérico (→ `Log::error` + mensaje genérico) |

### Patrón aplicado

```php
} catch (\InvalidArgumentException $e) {
    // Mensaje de negocio controlado — seguro exponerlo
    return response()->json(['message' => $e->getMessage()], 422);
} catch (\LogicException $e) {
    return response()->json(['message' => $e->getMessage()], 422);
} catch (\Exception $e) {
    // Traza interna → solo al log, nunca al cliente
    \Log::error('Descripción del contexto', ['error' => $e->getMessage()]);
    return response()->json(['message' => 'Mensaje genérico para el usuario.'], 500);
}
````

### Resultado

- Ya no hay ningún `$e->getMessage()` filtrándose en respuestas 500 del API.
- Los errores de negocio (validaciones, reglas de dominio) siguen siendo legibles por el frontend vía 422.
- Los errores inesperados quedan registrados internamente (Laravel Log) sin exponer detalles al cliente.
  ?? log.tipo_accion.descripcion}
  colorClasses={AUDIT_ACTION_BADGE[log.tipo_accion.descripcion.toLowerCase()]?.colorClasses
  ?? 'bg-slate-light text-slate'}
  />

```

Si un tipo de acción **no está en el mapa**, el componente tiene un fallback: muestra el string crudo de la BD con el color por defecto (`bg-slate-light text-slate`). Funciona, pero todos los tipos no mapeados se ven idénticos sin distinción visual.

**El problema concreto:** al agregar HU-026, los tipos `publicar` y `despublicar` se crearían en la BD pero el frontend no los conocía — ambos aparecerían con badge gris genérico. Lo mismo ocurría con `notificar`, `notificar_fallido` y `retroalimentar`, que ya existían en BD (agregados en HU-013/HU-018) pero nunca se mapearon en el frontend.

**La solución:** agregar todos los tipos faltantes al mapa con colores semánticamente coherentes:
- `publicar` → verde (acción de activación/disponibilidad)
- `despublicar` → warning/amarillo (acción reversible de retiro)
- `notificar` → info/azul (acción informativa)
- `notificar_fallido` → rose (acción con error)
- `retroalimentar` → morado (acción de revisión/evaluación)

> **Nota para el equipo:** cada vez que se agregue un nuevo tipo de acción al `ActionTypeSeeder` o mediante migración, también debe agregarse su entrada en `AUDIT_ACTION_BADGE`. De lo contrario el badge aparece en gris sin distinción.

---

## Fase 8 — Tests automatizados

### Resultado final: 28/28 ✅

```

php artisan test tests/Unit/AccreditationReportTest.php tests/Feature/AccreditationReportFeatureTest.php --no-coverage

````

| Suite | Archivo | Tests | Estado |
|-------|---------|-------|--------|
| Unit | `tests/Unit/AccreditationReportTest.php` | 10 | ✅ Todos pasan |
| Feature | `tests/Feature/AccreditationReportFeatureTest.php` | 18 | ✅ Todos pasan |

### Configuración del entorno de tests

El proyecto usa un archivo `.env.testing` local (ignorado por git) para no exponer credenciales en `phpunit.xml`. El template versionado está en `.env.testing.example`:

```ini
# .env.testing — copiar a .env.testing (local, no versionar)
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307          # Puerto Docker del servicio saac_mysql
DB_DATABASE=saac_testing
DB_USERNAME=root
DB_PASSWORD=root
````

La base de datos `saac_testing` debe existir antes de correr los tests:

```sql
CREATE DATABASE IF NOT EXISTS saac_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Correcciones realizadas durante la Fase 8

| Archivo                                                             | Problema                                                                                                                               | Solución                                                                                       |
| ------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| `database/factories/FileFactory.php`                                | `usuario_id=1` y `proceso_id=1` hardcodeados (FK violations)                                                                           | Reemplazados con `User::factory()` y `Process::factory()`                                      |
| `tests/Feature/AccreditationReportFeatureTest.php` (setUp)          | `encargado` y `profesor` no pertenecían a la carrera del ciclo → `BaseCareer` scope los invisibilizaba (404)                           | Se agrega `$usuario->careers()->attach($carreraId)` en `setUp()`                               |
| `database/seeders/RolesAndPermissionsSeeder.php`                    | Rol "Encargado de Acreditación" no tenía permisos `informes_acreditacion.*` → tests de publish devolvían 403                           | Se agregaron `informes_acreditacion.view`, `.publish`, `.download` al rol                      |
| `app/Policies/AccreditationReportPolicy.php`                        | `unpublish()` verificaba `!$report->isPublished()` en la Policy (responsabilidad del Service) → bloqueaba el test de unpublish con 403 | Se eliminó la verificación de estado de la Policy; solo verifica permiso                       |
| `app/Http/Requests/PublishAccreditationReportRequest.php`           | `$file->process` retornaba `null` por `BaseCareer` global scope → error 500 en validación                                              | Se agregó `!$file->process` en el guard del método `validateFileBelongsToCycle()`              |
| `tests/Feature/AccreditationReportFeatureTest.php` (publishPayload) | File creado con `tipo_mime=null` → fallaba validación MIME en `PublishAccreditationReportRequest`                                      | Se fijó `tipo_mime='application/pdf'` y `nombre_original='resolucion-sinaes.pdf'` en el helper |

---

## Pendiente — Frontend

### `src/Constants/StatusBadges.ts`

Los cambios descritos en la sección "Cambios en el Frontend" están preparados localmente en la rama `development` pero **aún no se han commiteado**. El equipo frontend aún no ha iniciado la integración de HU-026; los badges se commitearán cuando el frontend arranque ese trabajo formalmente.

| Tipo de acción      | Label          | Color     | Estado              |
| ------------------- | -------------- | --------- | ------------------- |
| `notificar`         | Notificar      | Info/azul | ⏳ Pendiente commit |
| `notificar_fallido` | Notif. fallida | Rose      | ⏳ Pendiente commit |
| `retroalimentar`    | Retroalimentar | Morado    | ⏳ Pendiente commit |
| `publicar`          | Publicar       | Verde     | ⏳ Pendiente commit |
| `despublicar`       | Despublicar    | Warning   | ⏳ Pendiente commit |
