# Comandos de Verificación Rápida - JERARQUIA con modelo_estructura_id

## 🔍 Consultas SQL Directas

### Ver todas las jerarquías con sus modelos
```sql
SELECT 
    jerarquia_id,
    modelo_estructura_id,
    parent_id,
    nombre,
    tipo,
    categoria,
    nomenclatura
FROM JERARQUIA
ORDER BY modelo_estructura_id, jerarquia_id;
```

### Ver solo pautas con categoría
```sql
SELECT 
    j.jerarquia_id,
    j.nombre,
    j.categoria,
    j.modelo_estructura_id,
    m.nombre AS modelo_nombre
FROM JERARQUIA j
JOIN MODELO_ESTRUCTURA m ON j.modelo_estructura_id = m.modelo_estructura_id
WHERE j.tipo = 'pauta'
ORDER BY j.categoria, j.jerarquia_id;
```

### Contar jerarquías por modelo
```sql
SELECT 
    m.modelo_estructura_id,
    m.nombre AS modelo,
    COUNT(j.jerarquia_id) AS total_jerarquias,
    SUM(IF(j.tipo = 'dimension', 1, 0)) AS dimensiones,
    SUM(IF(j.tipo = 'pauta', 1, 0)) AS pautas,
    SUM(IF(j.tipo = 'fuente', 1, 0)) AS fuentes
FROM MODELO_ESTRUCTURA m
LEFT JOIN JERARQUIA j ON m.modelo_estructura_id = j.modelo_estructura_id
GROUP BY m.modelo_estructura_id, m.nombre;
```

### Ver distribución de categorías
```sql
SELECT 
    categoria,
    COUNT(*) AS cantidad,
    GROUP_CONCAT(DISTINCT nombre SEPARATOR ' | ') AS pautas
FROM JERARQUIA
WHERE tipo = 'pauta'
GROUP BY categoria
ORDER BY categoria;
```

---

## 🧪 Probar Stored Procedures

### SP_OBTENER_JERARQUIAS
```sql
-- Todas del modelo 2
CALL SP_OBTENER_JERARQUIAS(NULL, 2);

-- Solo pautas del modelo 2
CALL SP_OBTENER_JERARQUIAS('pauta', 2);

-- Solo dimensiones del modelo 2
CALL SP_OBTENER_JERARQUIAS('dimension', 2);

-- Todas sin filtro de modelo (backward compatible)
CALL SP_OBTENER_JERARQUIAS(NULL, NULL);
```

### SP_OBTENER_ARBOL_JERARQUIA
```sql
-- Árbol completo del modelo 2
CALL SP_OBTENER_ARBOL_JERARQUIA(NULL, 2);

-- Subárbol desde dimensión 11
CALL SP_OBTENER_ARBOL_JERARQUIA(11, 2);

-- Árbol completo sin filtro de modelo
CALL SP_OBTENER_ARBOL_JERARQUIA(NULL, NULL);
```

### SP_CREAR_JERARQUIA
```sql
-- Crear pauta con categoría A
CALL SP_CREAR_JERARQUIA(
    2,                              -- modelo_estructura_id
    11,                             -- parent_id (ID de dimensión)
    'Pauta de Prueba',              -- nombre
    'pauta',                        -- tipo
    'A',                            -- categoria
    'PTEST',                        -- nomenclatura
    'Descripción de prueba',        -- descripcion
    99,                             -- orden
    1                               -- activo
);

-- Crear fuente SIN categoría
CALL SP_CREAR_JERARQUIA(
    2,                              -- modelo_estructura_id
    12,                             -- parent_id (ID de pauta)
    'Fuente de Prueba',             -- nombre
    'fuente',                       -- tipo
    NULL,                           -- categoria (fuentes no tienen)
    'FTEST',                        -- nomenclatura
    'Documento de ejemplo',         -- descripcion
    99,                             -- orden
    1                               -- activo
);
```

### SP_ACTUALIZAR_JERARQUIA
```sql
-- Cambiar categoría de pauta de B a A
CALL SP_ACTUALIZAR_JERARQUIA(
    15,                             -- jerarquia_id
    11,                             -- parent_id
    'Pauta 2: Perfil de Egreso',    -- nombre
    'pauta',                        -- tipo
    'A',                            -- categoria (cambio de B a A)
    'P2',                           -- nomenclatura
    'Competencias del egresado',    -- descripcion
    2,                              -- orden
    1                               -- activo
);
```

### SP_BUSCAR_JERARQUIA
```sql
-- Buscar por ID
CALL SP_BUSCAR_JERARQUIA(12);
```

### SP_ELIMINAR_JERARQUIA
```sql
-- Eliminar (solo si no tiene hijos)
CALL SP_ELIMINAR_JERARQUIA(21);
```

---

## 📡 Probar API Endpoints (con curl o Postman)

### GET - Listar jerarquías filtradas por modelo

**Todas del modelo 2:**
```bash
GET http://localhost/api/estructura/jerarquia?modelo_estructura_id=2
```

**Solo pautas del modelo 2:**
```bash
GET http://localhost/api/estructura/jerarquia?tipo=pauta&modelo_estructura_id=2
```

**Respuesta esperada:**
```json
{
  "data": [
    {
      "jerarquia_id": 12,
      "modelo_estructura_id": 2,
      "parent_id": 11,
      "nombre": "Pauta 1: Plan de Estudios",
      "tipo": "pauta",
      "categoria": "A",
      "nomenclatura": "P1",
      "descripcion": "...",
      "orden": 1,
      "activo": true
    }
  ]
}
```

### POST - Crear nueva pauta con categoría

**Request:**
```json
POST http://localhost/api/estructura/jerarquia
Content-Type: application/json

{
  "modelo_estructura_id": 2,
  "parent_id": 11,
  "nombre": "Pauta 4: Vinculación Externa",
  "tipo": "pauta",
  "categoria": "B",
  "nomenclatura": "P4",
  "descripcion": "Proyectos con comunidad",
  "orden": 4
}
```

**Respuesta esperada (201 Created):**
```json
{
  "data": {
    "jerarquia_id": 22,
    "modelo_estructura_id": 2,
    "categoria": "B",
    ...
  }
}
```

### PUT - Actualizar categoría de pauta

**Request:**
```json
PUT http://localhost/api/estructura/jerarquia/15
Content-Type: application/json

{
  "categoria": "A"
}
```

**Respuesta esperada (200 OK):**
```json
{
  "data": {
    "jerarquia_id": 15,
    "categoria": "A",
    ...
  }
}
```

### GET - Árbol jerárquico filtrado por modelo

**Árbol completo del modelo 2:**
```bash
GET http://localhost/api/estructura/jerarquia/arbol?modelo_estructura_id=2
```

**Subárbol desde dimensión 11:**
```bash
GET http://localhost/api/estructura/jerarquia/arbol?root_id=11&modelo_estructura_id=2
```

**Respuesta esperada:**
```json
[
  {
    "jerarquia_id": 11,
    "nombre": "Formación Profesional",
    "tipo": "dimension",
    "categoria": null,
    "nivel": 0,
    "children": [
      {
        "jerarquia_id": 12,
        "nombre": "Pauta 1: Plan de Estudios",
        "tipo": "pauta",
        "categoria": "A",
        "nivel": 1,
        "children": [...]
      }
    ]
  }
]
```

---

## 🐘 Comandos PHP Artisan

### Ejecutar migración
```bash
php artisan migrate
```

### Re-ejecutar seeder
```bash
php artisan db:seed --class=JerarquiaExampleSeeder
```

### Verificar datos con script PHP
```bash
# Ver todas las jerarquías con modelo y categoría
php verify_jerarquia.php

# Probar stored procedures
php test_sp_jerarquia.php
```

### Acceder a Tinker (consola interactiva)
```bash
php artisan tinker

# Dentro de tinker:
DB::table('JERARQUIA')->select('jerarquia_id', 'nombre', 'modelo_estructura_id', 'categoria')->get();
```

---

## ✅ Verificaciones de Integridad

### Verificar FK constraints
```sql
-- Todo debe tener un modelo válido
SELECT COUNT(*) AS sin_modelo
FROM JERARQUIA
WHERE modelo_estructura_id IS NULL;
-- Resultado esperado: 0

-- Todos los modelos existen
SELECT j.jerarquia_id, j.nombre, j.modelo_estructura_id
FROM JERARQUIA j
LEFT JOIN MODELO_ESTRUCTURA m ON j.modelo_estructura_id = m.modelo_estructura_id
WHERE m.modelo_estructura_id IS NULL;
-- Resultado esperado: 0 filas
```

### Verificar categorías solo en pautas
```sql
-- Dimensiones y fuentes NO deben tener categoría
SELECT jerarquia_id, nombre, tipo, categoria
FROM JERARQUIA
WHERE tipo IN ('dimension', 'fuente')
  AND categoria IS NOT NULL;
-- Resultado esperado: 0 filas

-- Pautas PUEDEN tener categoría (pero no es obligatorio)
SELECT jerarquia_id, nombre, categoria
FROM JERARQUIA
WHERE tipo = 'pauta'
ORDER BY categoria;
```

### Verificar valores de categoría válidos
```sql
-- Solo A, B, C, D o NULL permitidos
SELECT jerarquia_id, nombre, categoria
FROM JERARQUIA
WHERE categoria NOT IN ('A', 'B', 'C', 'D')
  AND categoria IS NOT NULL;
-- Resultado esperado: 0 filas (error si hay resultados)
```

---

## 🎯 Casos de Uso Comunes

### Crear estructura completa para nuevo modelo

```sql
-- 1. Crear un nuevo modelo de estructura
INSERT INTO MODELO_ESTRUCTURA (nombre, descripcion, tipo_estructura, activo)
VALUES ('SINAES 2030', 'Modelo renovado SINAES', 'jerarquia_flexible', 1);
-- Supongamos que obtiene ID = 3

-- 2. Crear dimensión raíz
CALL SP_CREAR_JERARQUIA(
    3, NULL, 'Innovación Educativa', 'dimension', NULL, 'D1', 'Transformación digital', 1, 1
);
-- Retorna ID = 23

-- 3. Crear pauta bajo la dimensión
CALL SP_CREAR_JERARQUIA(
    3, 23, 'Pauta 1: Aulas Virtuales', 'pauta', 'A', 'P1', 'Infraestructura digital', 1, 1
);
-- Retorna ID = 24

-- 4. Crear fuente bajo la pauta
CALL SP_CREAR_JERARQUIA(
    3, 24, 'Plataforma LMS', 'fuente', NULL, 'F1.1', 'Evidencia de uso', 1, 1
);
```

### Migrar categorías masivamente
```sql
-- Reasignar todas las pautas de categoría B a categoría A
UPDATE JERARQUIA
SET categoria = 'A', updated_at = NOW()
WHERE tipo = 'pauta'
  AND categoria = 'B'
  AND modelo_estructura_id = 2;
```

### Clonar estructura de un modelo a otro
```sql
-- Ver GUIA_IMPLEMENTACION_JERARQUIA.md para procedimiento completo
-- (Requiere lógica recursiva para mantener relaciones parent_id)
```

---

## 📊 Reportes Útiles

### Tabla de categorías con puntajes
```sql
SELECT 
    categoria,
    COUNT(*) AS cantidad_pautas,
    CASE categoria
        WHEN 'A' THEN 2.50
        WHEN 'B' THEN 2.00
        WHEN 'C' THEN 1.50
        WHEN 'D' THEN 1.00
        ELSE 0
    END AS puntaje_max_conformidad
FROM JERARQUIA
WHERE tipo = 'pauta'
GROUP BY categoria
ORDER BY categoria;
```

### Comparación de modelos
```sql
SELECT 
    m.nombre AS modelo,
    COUNT(DISTINCT CASE WHEN j.tipo = 'dimension' THEN j.jerarquia_id END) AS dimensiones,
    COUNT(DISTINCT CASE WHEN j.tipo = 'pauta' THEN j.jerarquia_id END) AS pautas,
    COUNT(DISTINCT CASE WHEN j.tipo = 'fuente' THEN j.jerarquia_id END) AS fuentes,
    COUNT(DISTINCT CASE WHEN j.categoria = 'A' THEN j.jerarquia_id END) AS pautas_A,
    COUNT(DISTINCT CASE WHEN j.categoria = 'B' THEN j.jerarquia_id END) AS pautas_B,
    COUNT(DISTINCT CASE WHEN j.categoria = 'C' THEN j.jerarquia_id END) AS pautas_C,
    COUNT(DISTINCT CASE WHEN j.categoria = 'D' THEN j.jerarquia_id END) AS pautas_D
FROM MODELO_ESTRUCTURA m
LEFT JOIN JERARQUIA j ON m.modelo_estructura_id = j.modelo_estructura_id
GROUP BY m.modelo_estructura_id, m.nombre;
```

---

## 🚨 Troubleshooting

### Error: "modelo_estructura_id cannot be null"
**Causa:** Intentando crear jerarquía sin especificar modelo  
**Solución:** Siempre incluir `modelo_estructura_id` en inserts/SP calls

### Error: "categoria must be A, B, C or D"
**Causa:** Valor inválido para ENUM  
**Solución:** Usar solo: 'A', 'B', 'C', 'D' o NULL

### Error: "Cannot delete or update a parent row"
**Causa:** Intentando eliminar modelo con jerarquías asociadas  
**Solución:** Eliminar jerarquías primero, o usar CASCADE en FK

### Árbol vacío en API
**Causa:** Filtro de `modelo_estructura_id` incorrecto  
**Solución:** Verificar que existan jerarquías con ese modelo_estructura_id

---

**Última actualización:** 14 de marzo de 2026  
**Versión:** 1.0
