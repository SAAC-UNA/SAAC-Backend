# 🧪 GUÍA DE PRUEBAS - Sistema de Jerarquía y Modelos

## ✅ ESTADO ACTUAL

**Migraciones ejecutadas:**
- ✅ 039_create_jerarquia_table
- ✅ 041_create_modelo_estructura_table  
- ✅ 042_add_modelo_estructura_stored_procedures

**Seeders ejecutados:**
- ✅ JerarquiaPermissionsSeeder (4 permisos creados)
- ✅ JerarquiaExampleSeeder (10 elementos de ejemplo)

---

## 📊 VERIFICAR BASE DE DATOS

### 1. Conectar a MySQL

```powershell
docker exec -it saac-una mysql -u root -p
# Password: (el que tengas configurado)
USE saac;
```

---

### 2. Verificar tabla JERARQUIA

```sql
-- Ver todos los registros
SELECT jerarquia_id, parent_id, nombre, tipo, nomenclatura, orden 
FROM JERARQUIA 
ORDER BY parent_id, orden;
```

**Resultado esperado (10 registros):**
```
┌──────────────┬───────────┬────────────────────────────────┬───────────┬──────────────┬───────┐
│ jerarquia_id │ parent_id │ nombre                         │ tipo      │ nomenclatura │ orden │
├──────────────┼───────────┼────────────────────────────────┼───────────┼──────────────┼───────┤
│ 1            │ NULL      │ Formación Profesional          │ dimension │ D1           │ 1     │
│ 8            │ NULL      │ Gestión Académica y Admin.     │ dimension │ D2           │ 2     │
│ 2            │ 1         │ Pauta 1: Plan de Estudios      │ pauta     │ P1           │ 1     │
│ 5            │ 1         │ Pauta 2: Perfil de Egreso      │ pauta     │ P2           │ 2     │
│ 3            │ 2         │ Plan de estudios vigente       │ fuente    │ F1.1         │ 1     │
│ 4            │ 2         │ Mallas curriculares            │ fuente    │ F1.2         │ 2     │
│ 6            │ 5         │ Documento perfil egreso        │ fuente    │ F2.1         │ 1     │
│ 7            │ 5         │ Matriz de competencias         │ fuente    │ F2.2         │ 2     │
│ 9            │ 8         │ Pauta 3: Gestión Personal      │ pauta     │ P3           │ 1     │
│ 10           │ 9         │ Currículos personal académico  │ fuente    │ F3.1         │ 1     │
└──────────────┴───────────┴────────────────────────────────┴───────────┴──────────────┴───────┘
```

---

### 3. Verificar tabla MODELO_ESTRUCTURA

```sql
-- Ver modelos disponibles
SELECT * FROM MODELO_ESTRUCTURA;
```

**Resultado esperado (2 registros):**
```
┌──────────────────────┬──────────────────────────────────────────┬─────────────────────┬─────────┬────────┐
│ modelo_estructura_id │ nombre                                   │ tipo                │ version │ activo │
├──────────────────────┼──────────────────────────────────────────┼─────────────────────┼─────────┼────────┤
│ 1                    │ SINAES 2018 - Estructura Tradicional     │ tradicional         │ 2018    │ 1      │
│ 2                    │ SINAES 2026 - Estructura Flexible        │ jerarquia_flexible  │ 2026    │ 1      │
└──────────────────────┴──────────────────────────────────────────┴─────────────────────┴─────────┴────────┘
```

---

### 4. Verificar campo en PROCESO

```sql
-- Ver estructura de PROCESO
DESCRIBE PROCESO;
```

**Debe incluir:**
```
modelo_estructura_id | bigint unsigned | NO  |     | 1       |  
```

---

### 5. Verificar Stored Procedures

```sql
-- Ver SPs de JERARQUIA
SHOW PROCEDURE STATUS WHERE Name LIKE '%JERARQUIA%';
```

**Debe mostrar 6 procedimientos:**
- SP_OBTENER_JERARQUIAS
- SP_BUSCAR_JERARQUIA
- SP_CREAR_JERARQUIA
- SP_ACTUALIZAR_JERARQUIA
- SP_ELIMINAR_JERARQUIA
- SP_OBTENER_ARBOL_JERARQUIA

```sql
-- Ver SPs de MODELO_ESTRUCTURA
SHOW PROCEDURE STATUS WHERE Name LIKE '%MODELO%';
```

**Debe mostrar 3 procedimientos:**
- SP_OBTENER_MODELOS_ESTRUCTURA
- SP_OBTENER_MODELOS_ACTIVOS
- SP_BUSCAR_MODELO_ESTRUCTURA

---

## 🌐 PROBAR ENDPOINTS (API)

### 1. Obtener todos los elementos de JERARQUIA

```bash
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
    "descripcion": "Dimensión orientada a la formación integral del estudiante",
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

### 2. Filtrar solo PAUTAS

```bash
GET http://localhost:8000/api/estructura/jerarquia?tipo=pauta
Authorization: Bearer {tu_token}
```

**Respuesta esperada (3 pautas):**
```json
[
  {
    "jerarquia_id": 2,
    "tipo": "pauta",
    "nombre": "Pauta 1: Plan de Estudios"
  },
  {
    "jerarquia_id": 5,
    "tipo": "pauta",
    "nombre": "Pauta 2: Perfil de Egreso"
  },
  {
    "jerarquia_id": 9,
    "tipo": "pauta",
    "nombre": "Pauta 3: Gestión de Personal Académico"
  }
]
```

---

### 3. Obtener ÁRBOL completo

```bash
GET http://localhost:8000/api/estructura/jerarquia/arbol
Authorization: Bearer {tu_token}
```

**Respuesta esperada (con niveles y rutas):**
```json
[
  {
    "jerarquia_id": 1,
    "parent_id": null,
    "nombre": "Formación Profesional",
    "tipo": "dimension",
    "nivel": 0,
    "ruta": "1"
  },
  {
    "jerarquia_id": 2,
    "parent_id": 1,
    "nombre": "Pauta 1: Plan de Estudios",
    "tipo": "pauta",
    "nivel": 1,
    "ruta": "1->2"
  },
  {
    "jerarquia_id": 3,
    "parent_id": 2,
    "nombre": "Plan de estudios vigente",
    "tipo": "fuente",
    "nivel": 2,
    "ruta": "1->2->3"
  }
  // ... toda la jerarquía recursiva
]
```

---

### 4. Crear un nuevo elemento (PAUTA)

```bash
POST http://localhost:8000/api/estructura/jerarquia
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "parent_id": 1,
  "nombre": "Pauta 4: Nueva Pauta de Prueba",
  "tipo": "pauta",
  "nomenclatura": "P4",
  "descripcion": "Esta es una pauta de prueba",
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
    "nombre": "Pauta 4: Nueva Pauta de Prueba",
    "tipo": "pauta",
    "nomenclatura": "P4",
    "orden": 3,
    "activo": true,
    "created_at": "2026-03-14T10:30:00.000000Z",
    "updated_at": "2026-03-14T10:30:00.000000Z"
  }
}
```

---

### 5. Actualizar elemento

```bash
PUT http://localhost:8000/api/estructura/jerarquia/11
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "nombre": "Pauta 4: Pauta Modificada",
  "descripcion": "Descripción actualizada",
  "activo": true
}
```

---

### 6. Intentar eliminar elemento CON hijos (debe fallar)

```bash
DELETE http://localhost:8000/api/estructura/jerarquia/1
Authorization: Bearer {tu_token}
```

**Respuesta esperada (error 422):**
```json
{
  "message": "No se puede eliminar un elemento que tiene hijos. Elimine primero los elementos hijos."
}
```

---

### 7. Eliminar elemento SIN hijos

```bash
DELETE http://localhost:8000/api/estructura/jerarquia/11
Authorization: Bearer {tu_token}
```

**Respuesta esperada:**
```json
{
  "message": "Elemento eliminado correctamente."
}
```

---

## 🧪 PROBAR MODELO_ESTRUCTURA

### 1. Ver proceso existente

Primero, verifica si tienes procesos en tu sistema:

```sql
SELECT proceso_id, tipo_proceso, modelo_estructura_id FROM PROCESO LIMIT 5;
```

---

### 2. Crear proceso con modelo TRADICIONAL

```bash
POST http://localhost:8000/api/proceso
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "ciclo_acreditacion_id": 1,
  "tipo_proceso": "Autoevaluacion",
  "modelo_estructura_id": 1
}
```

**Respuesta debe incluir:**
```json
{
  "proceso_id": X,
  "modelo_estructura_id": 1,
  "modelo_nombre": "SINAES 2018 - Estructura Tradicional",
  "modelo_tipo": "tradicional",
  "modelo_version": "2018"
}
```

---

### 3. Crear proceso con modelo JERARQUIA FLEXIBLE

```bash
POST http://localhost:8000/api/proceso
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "ciclo_acreditacion_id": 1,
  "tipo_proceso": "Autoevaluacion",
  "modelo_estructura_id": 2
}
```

**Respuesta debe incluir:**
```json
{
  "proceso_id": Y,
  "modelo_estructura_id": 2,
  "modelo_nombre": "SINAES 2026 - Estructura Flexible con Pautas",
  "modelo_tipo": "jerarquia_flexible",
  "modelo_version": "2026"
}
```

---

## 🔍 PRUEBAS AVANZADAS

### Consulta recursiva (árbol completo)

```sql
-- Ver árbol con indentación
WITH RECURSIVE arbol AS (
    SELECT 
        jerarquia_id,
        parent_id,
        nombre,
        tipo,
        0 AS nivel,
        CAST(nombre AS CHAR(500)) AS ruta
    FROM JERARQUIA
    WHERE parent_id IS NULL
    
    UNION ALL
    
    SELECT 
        j.jerarquia_id,
        j.parent_id,
        j.nombre,
        j.tipo,
        a.nivel + 1,
        CONCAT(a.ruta, ' > ', j.nombre)
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

**Resultado:**
```
estructura                              | tipo      | nivel | ruta
---------------------------------------+-----------+-------+------------------
Formación Profesional                  | dimension | 0     | Formación...
  Pauta 1: Plan de Estudios            | pauta     | 1     | Formación... > Pauta 1...
    Plan de estudios vigente           | fuente    | 2     | ... > Plan...
    Mallas curriculares                | fuente    | 2     | ... > Mallas...
  Pauta 2: Perfil de Egreso            | pauta     | 1     | ...
    Documento perfil egreso            | fuente    | 2     | ...
    Matriz de competencias             | fuente    | 2     | ...
```

---

## ✅ CHECKLIST FINAL

- [ ] Tabla JERARQUIA creada con 10 registros
- [ ] Tabla MODELO_ESTRUCTURA creada con 2 registros
- [ ] PROCESO tiene campo `modelo_estructura_id`
- [ ] 6 Stored Procedures de JERARQUIA disponibles
- [ ] 3 Stored Procedures de MODELO_ESTRUCTURA disponibles
- [ ] Endpoint GET /api/estructura/jerarquia funciona
- [ ] Endpoint GET /api/estructura/jerarquia/arbol funciona
- [ ] Endpoint POST /api/estructura/jerarquia funciona
- [ ] Endpoint PUT /api/estructura/jerarquia/{id} funciona
- [ ] Endpoint DELETE /api/estructura/jerarquia/{id} funciona
- [ ] Validación de hijos funciona (no permite eliminar padres)
- [ ] Puedes crear procesos con modelo_estructura_id = 1 ó 2

---

## 🎯 PRÓXIMOS PASOS

Una vez que todo funcione:

1. **Frontend:** Crear componente selector que detecte `modelo_tipo` del proceso
2. **Evidencias:** Decidir si vincular tabla EVIDENCIA con JERARQUIA (campo opcional `jerarquia_id`)
3. **Migración gradual:** Convertir procesos viejos a usar el nuevo modelo

---

## 📞 COMANDOS ÚTILES

```powershell
# Ver migraciones
php artisan migrate:status

# Rollback si algo sale mal
php artisan migrate:rollback

# Ejecutar seeder específico
php artisan db:seed --class=JerarquiaExampleSeeder

# Ver rutas API
php artisan route:list --path=estructura
```

---

¡Sistema listo para usar! 🚀
