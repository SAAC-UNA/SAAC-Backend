# Checklist de correcciones — Modelos, Elementos y Procesos

> Correr `php artisan migrate:fresh --seed` antes de empezar.

---

## 1. Modelo tradicional en migración (no en seeder)

**¿Qué se cambió?**
- El `INSERT` del modelo SINAES 2018 tradicional se movió de `StructureModelSeeder` a la migración `007a` con `insertOrIgnore`.
- `StructureModelSeeder.php` fue eliminado.
- Se removió de `DatabaseSeeder.php`.

**Prueba:**
```
GET /api/estructura/modelos
```
- [ ] Responde 200 con el modelo tradicional (id:1, tipo: "tradicional") sin necesidad de correr seeders manualmente.
- [ ] Si se corre `migrate:fresh` sin `--seed`, el modelo tradicional igual aparece en el GET.

---

## 2. Nombre único en MODELO_ESTRUCTURA

**¿Qué se cambió?**
- Migración `007a`: columna `nombre` con `->unique()`.
- `StructureModelRequest`: regla `Rule::unique` en POST y en PUT (con ignore del propio ID).

**Prueba POST — duplicado:**
```json
POST /api/estructura/modelos
{ "nombre": "SINAES 2018 - Estructura Tradicional", "tipo": "elemento_flexible" }
```
- [ ] 422 con mensaje: `"Ya existe un modelo de estructura con ese nombre."`

**Prueba POST — nombre válido:**
```json
POST /api/estructura/modelos
{ "nombre": "SINAES 2026 Flexible", "tipo": "elemento_flexible", "version": "2026" }
```
- [ ] 201 OK, guardar `modelo_estructura_id` (ej: 2)

**Prueba PUT — mismo nombre (debe pasar):**
```json
PUT /api/estructura/modelos/2
{ "nombre": "SINAES 2026 Flexible" }
```
- [ ] 200 OK (se ignora a sí mismo en la unicidad)

**Prueba PUT — nombre de otro modelo:**
```json
PUT /api/estructura/modelos/2
{ "nombre": "SINAES 2018 - Estructura Tradicional" }
```
- [ ] 422 con mensaje: `"Ya existe un modelo de estructura con ese nombre."`

---

## 3. Validaciones regex en MODELO_ESTRUCTURA

**Prueba `nombre` con símbolos prohibidos:**
```json
POST /api/estructura/modelos
{ "nombre": "Modelo @#$ Raro", "tipo": "elemento_flexible" }
```
- [ ] 422: `"El nombre solo puede contener letras, números, espacios, puntos y guiones."`

**Prueba `version` con símbolos prohibidos:**
```json
POST /api/estructura/modelos
{ "nombre": "Modelo Valido", "tipo": "elemento_flexible", "version": "2026@!" }
```
- [ ] 422: `"La versión solo puede contener letras, números, puntos y guiones."`

**Prueba `descripcion` con símbolos prohibidos:**
```json
POST /api/estructura/modelos
{ "nombre": "Modelo Valido", "tipo": "elemento_flexible", "descripcion": "Texto con @#$%" }
```
- [ ] 422: `"La descripción contiene caracteres no permitidos."`

**Prueba campos válidos:**
```json
POST /api/estructura/modelos
{ "nombre": "SINAES 2030 - Nuevo", "tipo": "elemento_flexible", "version": "2030.1", "descripcion": "Descripcion con puntos, comas y guiones." }
```
- [ ] 201 OK

---

## 4. Validaciones regex en ELEMENTO

**Requiere modelo_estructura_id = 2 (flexible). Crear primero si no existe.**

**Prueba `tipo` con símbolos:**
```json
POST /api/estructura/elementos
{ "modelo_estructura_id": 2, "tipo": "area@!", "nomenclatura": "AG-01" }
```
- [ ] 422: `"El tipo solo puede contener letras y espacios (ej: area, subarea, pauta)."`

**Prueba `nomenclatura` con símbolos prohibidos:**
```json
POST /api/estructura/elementos
{ "modelo_estructura_id": 2, "tipo": "area", "nomenclatura": "AG@01" }
```
- [ ] 422: `"La nomenclatura solo puede contener letras, números, puntos, guiones y guiones bajos."`

**Prueba `nomenclatura` válida (con guión):**
```json
POST /api/estructura/elementos
{ "modelo_estructura_id": 2, "tipo": "area", "nomenclatura": "AG-01", "activo": true }
```
- [ ] 201 OK, guardar `elemento_id` (ej: 1)

**Prueba `descripcion` con símbolos:**
```json
POST /api/estructura/elementos
{ "modelo_estructura_id": 2, "padre_id": 1, "tipo": "pauta", "descripcion": "Texto con @#$%" }
```
- [ ] 422: `"La descripción contiene caracteres no permitidos."`

**Prueba `descripcion` válida:**
```json
POST /api/estructura/elementos
{ "modelo_estructura_id": 2, "padre_id": 1, "tipo": "subarea", "nomenclatura": "AG-01-01", "descripcion": "Descripcion valida con puntos y comas." }
```
- [ ] 201 OK

---

## 5. Tipo "tradicional" bloqueado en CRUD

```json
POST /api/estructura/modelos
{ "nombre": "Otro Tradicional", "tipo": "tradicional" }
```
- [ ] 422: `"El único tipo de modelo que puede crear es: elemento_flexible."`

---

## 6. Elemento rechaza modelo tradicional (tipo != elemento_flexible)

```json
POST /api/estructura/elementos
{ "modelo_estructura_id": 1, "tipo": "area", "nomenclatura": "X-01" }
```
- [ ] 422: `"El modelo de estructura debe ser de tipo elemento_flexible para crear elementos."`

---

## 7. Elemento rechaza padre de otro modelo

Asumiendo elemento_id:1 pertenece a modelo_estructura_id:2:
```json
POST /api/estructura/elementos
{ "modelo_estructura_id": 2, "padre_id": 99, "tipo": "subarea" }
```
- [ ] 422 padre no existe

Crear elemento en modelo 3 (si existe), luego intentar usarlo como padre en modelo 2:
- [ ] 422: `"El elemento padre debe pertenecer al mismo modelo de estructura."`

---

## Errores que NO deben aparecer

- [ ] No hay referencias a `StructureModelSeeder` en el código (fue eliminado)
- [ ] `GET /api/estructura/modelos` nunca devuelve lista vacía tras `migrate:fresh`
- [ ] Los campos `nombre` y `orden` no aparecen en ninguna respuesta de ELEMENTO
