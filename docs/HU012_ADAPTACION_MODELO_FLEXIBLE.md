# HU-012 — Adaptación al Modelo Flexible de Estructura

## Contexto

El sistema SAAC maneja dos modelos de acreditación SINAES en paralelo:

- **Modelo Tradicional**: jerarquía fija `DIMENSION → COMPONENTE → CRITERIO → EVIDENCIA`
- **Modelo Flexible**: árbol libre `MODELO_ESTRUCTURA → ELEMENTO (self-ref via padre_id)`

El modelo flexible existe porque SINAES cambia los tipos y nombres de evaluación cada cierto número de años. Con la estructura rígida anterior, cada cambio requería migraciones y código nuevo. Con el modelo flexible, solo se configuran nuevos `ELEMENTO` sin tocar código.

El punto de conexión entre ambos modelos es `CICLO_ACREDITACION.modelo_estructura_id`.

---

## Problema que se resuelve

La tabla `EVIDENCIA` solo tenía `criterio_id NOT NULL` — era imposible asociar una evidencia al modelo flexible. El filtrado de HU-012 tampoco permitía buscar por `elemento_id`.

---

## Cambios realizados

### 1. Migración `043_add_elemento_id_to_evidencia_table.php`

- Agrega `elemento_id BIGINT UNSIGNED NULL` con FK → `ELEMENTO.elemento_id` (`onDelete set null`)
- Cambia `criterio_id` a nullable para permitir evidencias flexibles

**Regla de negocio (excluyente):**
```
Evidencia tradicional:  criterio_id = X,    elemento_id = null
Evidencia flexible:     criterio_id = null,  elemento_id = Y
```

### 2. Modelo `Evidence.php`

- Agrega `elemento_id` al `$fillable`
- Nueva relación `elemento()` → `belongsTo(StructureElement::class, 'elemento_id', 'elemento_id')`

### 3. `FilterEvidenceRequest.php`

- Nueva regla `elemento_id`: nullable, integer, exists en `ELEMENTO`
- Mensaje de error en español
- Atributo legible `'elemento_id' => 'elemento'`

### 4. `EvidenceService.php`

- `WITH_BASE` actualizado: agrega `'elemento'` al eager loading (retorna `null` en evidencias tradicionales sin romper nada)
- `filterEvidences()`: extrae `$elementoId` de los filtros y aplica `WHERE elemento_id = ?`

### 5. `EvidenceResource.php`

- Expone `elemento_id` como campo plano
- Nuevo bloque `elemento` con `$this->when(...)`: solo aparece en la respuesta si la relación está cargada Y la evidencia es flexible

### 6. `RolesAndPermissionsSeeder.php`

- **Antes**: solo asignaba `admin.super` al Superusuario — causaba 403 en rutas con `permission:modelos.view`, `permission:elemento.create`, etc.
- **Después**: lee `config/permissions.modules` y genera todos los permisos automáticamente. Superusuario recibe `syncPermissions` con todos ellos, cumpliendo `all_permissions = true` del config.

---

## Pruebas realizadas en Postman

| Endpoint | Resultado esperado | ✅ |
|----------|-------------------|-----|
| `GET /filter` (sin params) | 13 evidencias tradicionales, `elemento_id: null` | ✅ |
| `GET /filter?criterio_id=1` | 2 evidencias del criterio 1.1.1 | ✅ |
| `GET /filter?elemento_id=999` | 422 — elemento no existe | ✅ |
| `GET /filter?elemento_id=1` | `data: [], total: 0` sin error | ✅ |

---

## Pendiente (sprint futuro)

Cuando se necesiten evidencias flexibles reales:
1. Crear evidencias con `elemento_id` y `criterio_id = null` vía `POST /api/estructura/evidencias`
2. El filtro `?elemento_id=X` empezará a devolver resultados automáticamente
3. `EvidenceRequest` deberá validar la exclusividad `criterio_id XOR elemento_id`
