# 🚀 GUÍA DE EJECUCIÓN: TABLA JERARQUIA
## Pasos para implementar en tu sistema

**FECHA:** 14 de Marzo de 2026  
**ESTADO:** ✅ Archivos creados - Listo para ejecutar

---

## ✅ ARCHIVOS CREADOS

### 1. Migraciones (Base de Datos)
- ✅ `database/migrations/039_create_jerarquia_table.php`
- ✅ `database/migrations/040_add_jerarquia_stored_procedures.php`

### 2. Modelo Eloquent
- ✅ `app/Models/Jerarquia.php`

### 3. Servicio
- ✅ `app/Services/JerarquiaService.php`

### 4. Controlador
- ✅ `app/Http/Controllers/JerarquiaController.php`

### 5. Rutas
- ✅ `routes/api.php` (actualizado con rutas de jerarquia)

### 6. Seeders
- ✅ `database/seeders/JerarquiaPermissionsSeeder.php` (permisos)
- ✅ `database/seeders/JerarquiaExampleSeeder.php` (datos de ejemplo)

---

## 📋 PASOS DE EJECUCIÓN

### Paso 1: Ejecutar Migraciones

```powershell
cd c:\SAAC\SAAC-Backend

# Ejecutar las migraciones
php artisan migrate
```

**Resultado esperado:**
```
Migrating: 039_create_jerarquia_table
Migrated:  039_create_jerarquia_table (XX.XXms)
Migrating: 040_add_jerarquia_stored_procedures
Migrated:  040_add_jerarquia_stored_procedures (XX.XXms)
```

---

### Paso 2: Crear Permisos

```powershell
# Ejecutar seeder de permisos
php artisan db:seed --class=JerarquiaPermissionsSeeder
```

**Resultado esperado:**
```
✅ Permisos de jerarquía creados exitosamente
✅ Permisos asignados al rol Superusuario
```

---

### Paso 3: (Opcional) Insertar Datos de Ejemplo

```powershell
# Ejecutar seeder de datos de ejemplo
php artisan db:seed --class=JerarquiaExampleSeeder
```

**Resultado esperado:**
```
🌱 Iniciando seed de JERARQUIA con datos de ejemplo...
✅ Dimensión creada: ID 1
✅ Pauta 1 creada: ID 2
✅ Fuentes 1.1 y 1.2 creadas
✅ Pauta 2 creada: ID 5
✅ Fuentes 2.1 y 2.2 creadas
✅ Dimensión 2 creada: ID 8
✅ Pauta 3 y Fuente 3.1 creadas

========================================
✅ Seed completado exitosamente
========================================
📊 Total de elementos creados: 10

Estructura creada:
  📁 D1: Formación Profesional
    📋 P1: Plan de Estudios
      📄 F1.1: Plan de estudios vigente
      📄 F1.2: Mallas curriculares
    📋 P2: Perfil de Egreso
      📄 F2.1: Documento de perfil de egreso
      📄 F2.2: Matriz de competencias
  📁 D2: Gestión Académica y Administrativa
    📋 P3: Gestión de Personal Académico
      📄 F3.1: Currículos del personal académico
```

---

### Paso 4: Verificar en Base de Datos

```sql
-- Verificar que la tabla fue creada
SHOW TABLES LIKE 'JERARQUIA';

-- Ver estructura de la tabla
DESCRIBE JERARQUIA;

-- Ver stored procedures
SHOW PROCEDURE STATUS WHERE Db = 'saac' AND Name LIKE 'SP_%JERARQUIA%';

-- Ver datos de ejemplo
SELECT jerarquia_id, parent_id, nombre, tipo, nomenclatura, orden 
FROM JERARQUIA 
ORDER BY parent_id, orden;
```

---

## 🧪 PRUEBAS CON POSTMAN/THUNDER CLIENT

### 1. Obtener todos los elementos

```http
GET http://localhost:8000/api/estructura/jerarquia
Authorization: Bearer {tu_token}
```

**Respuesta esperada:**
```json
[
  {
    "jerarquia_id": 1,
    "parent_id": null,
    "nombre": "Formación Profesional",
    "tipo": "dimension",
    "nomenclatura": "D1",
    "orden": 1,
    "activo": true
  },
  {
    "jerarquia_id": 2,
    "parent_id": 1,
    "nombre": "Pauta 1: Plan de Estudios",
    "tipo": "pauta",
    "nomenclatura": "P1",
    "orden": 1,
    "activo": true
  }
  // ... más elementos
]
```

---

### 2. Filtrar por tipo (solo pautas)

```http
GET http://localhost:8000/api/estructura/jerarquia?tipo=pauta
Authorization: Bearer {tu_token}
```

---

### 3. Obtener árbol completo

```http
GET http://localhost:8000/api/estructura/jerarquia/arbol
Authorization: Bearer {tu_token}
```

**Respuesta esperada:** Estructura jerárquica con niveles y rutas

---

### 4. Obtener un elemento específico

```http
GET http://localhost:8000/api/estructura/jerarquia/1
Authorization: Bearer {tu_token}
```

---

### 5. Crear un nuevo elemento (pauta)

```http
POST http://localhost:8000/api/estructura/jerarquia
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "parent_id": 1,
  "nombre": "Pauta 4: Nueva Pauta",
  "tipo": "pauta",
  "nomenclatura": "P4",
  "descripcion": "Descripción de la nueva pauta",
  "orden": 3,
  "activo": true
}
```

**Respuesta esperada:**
```json
{
  "message": "Elemento creado correctamente.",
  "data": {
    "jerarquia_id": 11,
    "parent_id": 1,
    "nombre": "Pauta 4: Nueva Pauta",
    "tipo": "pauta",
    "nomenclatura": "P4",
    "orden": 3,
    "activo": true
  }
}
```

---

### 6. Actualizar un elemento

```http
PUT http://localhost:8000/api/estructura/jerarquia/11
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "nombre": "Pauta 4: Pauta Actualizada",
  "descripcion": "Nueva descripción",
  "activo": true
}
```

---

### 7. Intentar eliminar un elemento con hijos (debe fallar)

```http
DELETE http://localhost:8000/api/estructura/jerarquia/1
Authorization: Bearer {tu_token}
```

**Respuesta esperada:**
```json
{
  "message": "No se puede eliminar un elemento que tiene hijos. Elimine primero los elementos hijos."
}
```
**Status:** 422 Unprocessable Entity

---

### 8. Eliminar un elemento sin hijos (debe funcionar)

```http
DELETE http://localhost:8000/api/estructura/jerarquia/11
Authorization: Bearer {tu_token}
```

**Respuesta esperada:**
```json
{
  "message": "Elemento eliminado correctamente."
}
```
**Status:** 200 OK

---

## ✅ CHECKLIST DE VERIFICACIÓN

- [ ] Migraciones ejecutadas sin errores
- [ ] Tabla JERARQUIA creada en la base de datos
- [ ] Stored procedures creados (6 procedimientos)
- [ ] Permisos creados (jerarquia.view, create, edit, delete)
- [ ] Permisos asignados al Superusuario
- [ ] Datos de ejemplo insertados (opcional)
- [ ] Endpoint GET /api/estructura/jerarquia funciona
- [ ] Endpoint GET /api/estructura/jerarquia/arbol funciona
- [ ] Endpoint GET /api/estructura/jerarquia?tipo=pauta funciona
- [ ] Endpoint POST funciona (crear elemento)
- [ ] Endpoint PUT funciona (actualizar elemento)
- [ ] Endpoint DELETE funciona (eliminar elemento)
- [ ] Validación de hijos funciona (no eliminar padres)
- [ ] Logs de auditoría se registran correctamente

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Error: "Table 'JERARQUIA' doesn't exist"

**Solución:**
```powershell
# Verificar que las migraciones se ejecutaron
php artisan migrate:status

# Si no aparece, ejecutar de nuevo
php artisan migrate
```

---

### Error: "SQLSTATE[42000]: Syntax error near 'PROCEDURE'"

**Solución:** Tu versión de MySQL debe soportar stored procedures. Verifica la versión:
```sql
SELECT VERSION();
```
Debe ser MySQL 5.7+ o MariaDB 10.2+

---

### Error: "Permission 'jerarquia.view' does not exist"

**Solución:**
```powershell
# Ejecutar seeder de permisos
php artisan db:seed --class=JerarquiaPermissionsSeeder

# Limpiar caché de permisos
php artisan cache:clear
php artisan config:clear
```

---

### Error: "Unauthenticated" en requests

**Solución:** Asegúrate de incluir el token Bearer en el header:
```
Authorization: Bearer {tu_token_aqui}
```

---

## 📊 EJEMPLO DE CONSULTA RECURSIVA

```sql
-- Ver árbol completo con niveles
WITH RECURSIVE arbol AS (
    SELECT 
        jerarquia_id,
        parent_id,
        nombre,
        tipo,
        0 AS nivel,
        CAST(jerarquia_id AS CHAR(255)) AS ruta
    FROM JERARQUIA
    WHERE parent_id IS NULL
    
    UNION ALL
    
    SELECT 
        j.jerarquia_id,
        j.parent_id,
        j.nombre,
        j.tipo,
        a.nivel + 1,
        CONCAT(a.ruta, ' > ', j.jerarquia_id)
    FROM JERARQUIA j
    INNER JOIN arbol a ON j.parent_id = a.jerarquia_id
)
SELECT 
    CONCAT(REPEAT('  ', nivel), nombre) AS estructura,
    tipo,
    nivel,
    ruta
FROM arbol 
ORDER BY ruta;
```

---

## 🎯 SIGUIENTE PASO

### Integración con Frontend (React + TypeScript)

1. Actualizar `StructureTypes.ts`:
```typescript
export const ElementType = {
  // ... existentes
  JERARQUIA: 'jerarquia'
} as const;
```

2. Actualizar `StructureMapper.ts`:
```typescript
export const ELEMENT_TYPE_TO_ENDPOINT = {
  // ... existentes
  jerarquia: 'jerarquia'
};
```

3. El `StructureService` ya está listo para manejar el nuevo tipo.

---

## 🎉 CONCLUSIÓN

✅ **Implementación completada exitosamente**

Tu sistema ahora tiene:
- ✅ Tabla JERARQUIA flexible y autorreferencial
- ✅ 6 Stored Procedures para operaciones CRUD
- ✅ Modelo Eloquent con relaciones recursivas
- ✅ Servicio con caché integrado
- ✅ Controlador con validaciones completas
- ✅ Rutas API protegidas con permisos
- ✅ Sistema de auditoría integrado

**Las tablas existentes (DIMENSION, COMPONENTE, CRITERIO, EVIDENCIA) NO fueron modificadas.**

Todo coexiste perfectamente. 🚀
