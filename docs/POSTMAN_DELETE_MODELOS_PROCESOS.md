# DELETE Modelos y Procesos — Guía Postman

## Concepto: Por qué proceso usa cascade DB y modelo elimina manualmente

### La clave está en las migraciones

Las Foreign Keys en la base de datos tienen dos comportamientos posibles:

**`onDelete('cascade')`** → MySQL elimina automáticamente los hijos cuando se borra el padre.  
**`onDelete('restrict')`** → MySQL **bloquea** la eliminación si hay hijos. La query falla con error FK.

```
MODELO_ESTRUCTURA ──(restrict)──► CICLO_ACREDITACION ──(restrict)──► PROCESO
                                                                          │
                                                               (cascade)──┼──► AUTOEVALUACION
                                                               (cascade)──┴──► COMPROMISO_MEJORA
```

#### Para PROCESO (cascade desde migración 010 y 011):
```php
// 010_create_autoevaluations_table.php
$table->foreignId('proceso_id')
    ->constrained('PROCESO', 'proceso_id')
    ->onDelete('cascade');   // ← MySQL borra AUTOEVALUACION automáticamente

// 011_create_improvement_commitments_table.php
$table->foreignId('proceso_id')
    ->constrained('PROCESO', 'proceso_id')
    ->onDelete('cascade');   // ← MySQL borra COMPROMISO_MEJORA automáticamente
```

Por eso `ProcessService::delete()` solo hace `$process->delete()` — MySQL se encarga del resto:
```php
public function delete(Process $process): void
{
    $process->delete(); // MySQL automáticamente borra AUTOEVALUACION y COMPROMISO_MEJORA
}
```

#### Para MODELO_ESTRUCTURA (restrict desde migraciones 008 y 039):
```php
// 008_create_accreditation_cycles_table.php
->onDelete('restrict');  // ← MySQL BLOQUEA si hay ciclos que apuntan al modelo

// 039_create_elemento_table.php
->onDelete('restrict');  // ← MySQL BLOQUEA si hay elementos que apuntan al modelo
```

Por eso `StructureModelService::deleteWithCascade()` debe eliminar en orden manual dentro de una transacción:
```php
DB::transaction(function () use ($model) {
    // 1. Procesos (sus hijos AUTOEVALUACION/COMPROMISO_MEJORA los borra MySQL vía cascade)
    Process::whereIn('ciclo_acreditacion_id', $cicloIds)->delete();
    // 2. Ahora sí podemos borrar los ciclos (ya no tienen procesos apuntando)
    AccreditationCycle::whereIn('ciclo_acreditacion_id', $cicloIds)->delete();
    // 3. Elementos (nullify padre_id primero por la FK auto-referenciada)
    DB::table('ELEMENTO')->where('modelo_estructura_id', $model->modelo_estructura_id)->update(['padre_id' => null]);
    DB::table('ELEMENTO')->where('modelo_estructura_id', $model->modelo_estructura_id)->delete();
    // 4. Ahora podemos borrar el modelo (ya no tiene ciclos ni elementos apuntando)
    $model->delete();
});
```

> **GitHub hace exactamente esto**: cuando eliminas un repositorio, GitHub te pide escribir el nombre exacto (`tu-usuario/nombre-repo`). La lógica de eliminación en cascada ocurre en el backend — la confirmación es solo para evitar borrados accidentales.

---

## Permisos por rol

| Acción | Superusuario | Administrador | Encargado Acreditación | Profesor |
|--------|:---:|:---:|:---:|:---:|
| `modelos.view` | ✅ | ✅ | ✅ | ❌ |
| `modelos.create` | ✅ | ✅ | ❌ | ❌ |
| `modelos.edit` | ✅ | ✅ | ❌ | ❌ |
| `modelos.delete` | ✅ | ❌ | ❌ | ❌ |
| `procesos.view` | ✅ | ✅ | ✅ | ❌ |
| `procesos.create` | ✅ | ✅ | ✅ | ❌ |
| `procesos.edit` | ✅ | ✅ | ✅ | ❌ |
| `procesos.delete` | ✅ | ❌ | ❌ | ❌ |

---

## Autenticación (para todas las peticiones)

```
POST {{base_url}}/api/login
Content-Type: application/json

{
  "username": "superusuario",
  "password": "tu_password"
}
```

Guarda el token recibido como variable `{{token}}`:
```
Authorization: Bearer {{token}}
```

---

## 1. DELETE Modelo de Estructura

### Endpoint
```
DELETE {{base_url}}/api/estructura/modelos/{id}
Authorization: Bearer {{token}}
Content-Type: application/json
```

---

### Caso A — Sin body (sin confirmación)

**Request:**
```
DELETE {{base_url}}/api/estructura/modelos/2
Authorization: Bearer {{token}}
Content-Type: application/json

{}
```

**Response esperada `422`:**
```json
{
    "message": "Confirmación incorrecta. Envíe el nombre exacto del modelo en el campo \"confirmacion\" para confirmar la eliminación.",
    "modelo_nombre": "SINAES 2026 - Estructura Flexible",
    "advertencia": "Esta acción es irreversible y eliminará permanentemente los siguientes datos asociados:",
    "datos_a_eliminar": {
        "ciclos_acreditacion": 2,
        "procesos": 4,
        "elementos": 18
    }
}
```

> El sistema muestra exactamente cuántos registros serían eliminados antes de confirmar.

---

### Caso B — Intentar eliminar el modelo TRADICIONAL

**Request:**
```
DELETE {{base_url}}/api/estructura/modelos/1
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "confirmacion": "SINAES 2018 - Modelo Tradicional"
}
```

**Response esperada `422`:**
```json
{
    "message": "El modelo tradicional del sistema no puede ser eliminado."
}
```

> Sin importar qué se envíe en `confirmacion`, el modelo de tipo `tradicional` nunca se puede eliminar.

---

### Caso C — Confirmación incorrecta

**Request:**
```
DELETE {{base_url}}/api/estructura/modelos/2
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "confirmacion": "SINAES 2026"
}
```

**Response esperada `422`:**
```json
{
    "message": "Confirmación incorrecta. Envíe el nombre exacto del modelo en el campo \"confirmacion\" para confirmar la eliminación.",
    "modelo_nombre": "SINAES 2026 - Estructura Flexible",
    "advertencia": "Esta acción es irreversible y eliminará permanentemente los siguientes datos asociados:",
    "datos_a_eliminar": {
        "ciclos_acreditacion": 2,
        "procesos": 4,
        "elementos": 18
    }
}
```

---

### Caso D — Confirmación correcta (eliminación exitosa)

Copia el valor exacto de `modelo_nombre` del 422 anterior:

**Request:**
```
DELETE {{base_url}}/api/estructura/modelos/2
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "confirmacion": "SINAES 2026 - Estructura Flexible"
}
```

**Response esperada `200`:**
```json
{
    "message": "Modelo de estructura \"SINAES 2026 - Estructura Flexible\" eliminado exitosamente.",
    "datos_eliminados": {
        "ciclos_acreditacion": 2,
        "procesos": 4,
        "elementos": 18
    }
}
```

**Orden real de eliminación en DB:**
1. PROCESO (4 registros) → MySQL borra en cascada AUTOEVALUACION y COMPROMISO_MEJORA
2. CICLO_ACREDITACION (2 registros)
3. ELEMENTO — se pone `padre_id = null` primero para romper la FK circular, luego se eliminan (18 registros)
4. MODELO_ESTRUCTURA (1 registro)

---

### Caso E — No autorizado (Administrador intenta eliminar)

**Response esperada `403`:**
```json
{
    "message": "This action is unauthorized."
}
```

---

## 2. DELETE Proceso

### Endpoint
```
DELETE {{base_url}}/api/estructura/procesos/{id}
Authorization: Bearer {{token}}
Content-Type: application/json
```

---

### Caso A — Sin confirmación

**Request:**
```
DELETE {{base_url}}/api/estructura/procesos/3
Authorization: Bearer {{token}}
Content-Type: application/json

{}
```

**Response esperada `422`:**
```json
{
    "message": "Confirmación incorrecta. Envíe el tipo exacto del proceso en el campo \"confirmacion\" para confirmar la eliminación.",
    "tipo_proceso": "Autoevaluación",
    "advertencia": "Esta acción es irreversible y eliminará permanentemente el proceso y todos sus datos asociados.",
    "datos_a_eliminar": {
        "autoevaluaciones": 1,
        "compromisos_mejora": 0
    }
}
```

> La confirmación para proceso es por `tipo_proceso` porque el proceso no tiene campo `nombre`.  
> Los valores posibles son exactamente: `"Autoevaluación"` o `"Compromiso de mejora"`.

---

### Caso B — Confirmación incorrecta

**Request:**
```
DELETE {{base_url}}/api/estructura/procesos/3
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "confirmacion": "autoevaluacion"
}
```

**Response esperada `422`:** (igual que Caso A, la comparación es case-sensitive)

---

### Caso C — Confirmación correcta

**Request:**
```
DELETE {{base_url}}/api/estructura/procesos/3
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "confirmacion": "Autoevaluación"
}
```

**Response esperada `200`:**
```json
{
    "message": "Proceso \"Autoevaluación\" eliminado exitosamente.",
    "datos_eliminados": {
        "autoevaluaciones": 1,
        "compromisos_mejora": 0
    }
}
```

**Orden real de eliminación en DB:**
1. PROCESO (1 registro)
2. AUTOEVALUACION y COMPROMISO_MEJORA → eliminados automáticamente por MySQL (`onDelete: cascade`)

---

### Caso con "Compromiso de mejora"

**Request:**
```
DELETE {{base_url}}/api/estructura/procesos/5
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "confirmacion": "Compromiso de mejora"
}
```

**Response esperada `200`:**
```json
{
    "message": "Proceso \"Compromiso de mejora\" eliminado exitosamente.",
    "datos_eliminados": {
        "autoevaluaciones": 0,
        "compromisos_mejora": 1
    }
}
```

---

## 3. Flujo recomendado para probar en Postman

```
1. Login → obtener token
2. GET /api/estructura/modelos          → ver IDs disponibles
3. GET /api/estructura/procesos         → ver IDs disponibles
4. DELETE /procesos/{id} sin body       → ver advertencia con datos_a_eliminar
5. DELETE /procesos/{id} con confirmacion incorrecta → 422
6. DELETE /procesos/{id} con confirmacion exacta     → 200
7. GET /api/estructura/procesos         → confirmar que ya no aparece
8. DELETE /modelos/1 con cualquier body → 422 (tradicional bloqueado siempre)
9. DELETE /modelos/{flexible_id} sin body → ver advertencia
10. DELETE /modelos/{flexible_id} con nombre exacto  → 200
11. GET /api/estructura/modelos         → confirmar que ya no aparece
```

---

## 4. Resumen de archivos modificados

| Archivo | Cambio |
|---------|--------|
| `app/Services/ProcessService.php` | Creado desde cero. Contiene `getAll`, `findById`, `create`, `update`, `toggleActive`, `getDeleteSummary`, `delete` |
| `app/Controllers/ProcessController.php` | Refactorizado para usar `ProcessService`. `destroy()` ahora incluye `datos_a_eliminar` en el 422 y `datos_eliminados` en el 200 |
| `app/Services/StructureModelService.php` | Añadido `getDeleteSummary()` y `deleteWithCascade()` con transacción manual en orden FK |
| `app/Controllers/StructureModelController.php` | Añadido `destroy()` con bloqueo de tipo `tradicional` y confirmación por nombre |
| `config/permissions.php` | Nuevo módulo `modelos` con `view/create/edit/delete`. `procesos` añade `delete`. Administrador obtiene `modelos.view/create/edit`. Encargado obtiene `modelos.view` |
| `routes/api.php` | Rutas de modelos cambiadas de `role:Superusuario` a `permission:modelos.X`. DELETE procesos usa `permission:procesos.delete` |
