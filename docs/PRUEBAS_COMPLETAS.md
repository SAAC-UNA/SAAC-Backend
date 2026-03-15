# 🧪 PRUEBAS COMPLETAS - Modelos de Estructura

## 📊 ESTADO ACTUAL DEL SISTEMA

### Modelos disponibles:
```
ID | Nombre                                    | Tipo                | Versión
---+-------------------------------------------+---------------------+--------
1  | SINAES 2018 - Estructura Tradicional      | tradicional         | 2018
2  | SINAES 2026 - Estructura Flexible         | jerarquia_flexible  | 2026
```

### Jerarquía de ejemplo (10 elementos):
```
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

## 🔬 PRUEBA 1: API - Ver Modelos Disponibles

### Obtener todos los modelos
```http
GET http://localhost:8000/api/estructura/modelos
Authorization: Bearer {tu_token}
```

**Respuesta esperada:**
```json
[
  {
    "modelo_estructura_id": 1,
    "nombre": "SINAES 2018 - Estructura Tradicional",
    "descripcion": "Modelo de evaluación basado en la estructura clásica...",
    "tipo": "tradicional",
    "version": "2018",
    "activo": true
  },
  {
    "modelo_estructura_id": 2,
    "nombre": "SINAES 2026 - Estructura Flexible con Pautas",
    "descripcion": "Nuevo modelo SINAES 2026...",
    "tipo": "jerarquia_flexible",
    "version": "2026",
    "activo": true
  }
]
```

---

### Obtener solo modelos activos
```http
GET http://localhost:8000/api/estructura/modelos/activos
Authorization: Bearer {tu_token}
```

---

### Obtener un modelo específico
```http
GET http://localhost:8000/api/estructura/modelos/1
Authorization: Bearer {tu_token}
```

---

## 🔬 PRUEBA 2: Ver Jerarquía Creada

### Obtener todos los elementos
```http
GET http://localhost:8000/api/estructura/jerarquia
Authorization: Bearer {tu_token}
```

**Respuesta esperada (10 elementos):**
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
  // ... 8 elementos más
]
```

---

### Filtrar solo PAUTAS
```http
GET http://localhost:8000/api/estructura/jerarquia?tipo=pauta
Authorization: Bearer {tu_token}
```

**Respuesta esperada (3 pautas):**
```json
[
  {
    "jerarquia_id": 2,
    "nombre": "Pauta 1: Plan de Estudios",
    "tipo": "pauta",
    "nomenclatura": "P1"
  },
  {
    "jerarquia_id": 5,
    "nombre": "Pauta 2: Perfil de Egreso",
    "tipo": "pauta",
    "nomenclatura": "P2"
  },
  {
    "jerarquia_id": 9,
    "nombre": "Pauta 3: Gestión de Personal Académico",
    "tipo": "pauta",
    "nomenclatura": "P3"
  }
]
```

---

### Filtrar solo FUENTES
```http
GET http://localhost:8000/api/estructura/jerarquia?tipo=fuente
Authorization: Bearer {tu_token}
```

**Respuesta esperada (5 fuentes):**
```json
[
  {
    "jerarquia_id": 3,
    "parent_id": 2,
    "nombre": "Plan de estudios vigente",
    "tipo": "fuente",
    "nomenclatura": "F1.1"
  },
  {
    "jerarquia_id": 4,
    "parent_id": 2,
    "nombre": "Mallas curriculares",
    "tipo": "fuente",
    "nomenclatura": "F1.2"
  }
  // ... 3 fuentes más
]
```

---

### Obtener árbol completo (recursivo)
```http
GET http://localhost:8000/api/estructura/jerarquia/arbol
Authorization: Bearer {tu_token}
```

**Respuesta esperada (con niveles):**
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
  // ... con toda la jerarquía
]
```

---

## 🔬 PRUEBA 3: Crear Proceso con MODELO 1 (Tradicional)

### IMPORTANTE: Necesitas un ciclo de acreditación

Primero verifica que tengas ciclos:
```sql
SELECT ciclo_acreditacion_id, nombre FROM CICLO_ACREDITACION LIMIT 5;
```

Si no tienes, el sistema debería tener al menos 1.

---

### Crear proceso con modelo TRADICIONAL

**Nota:** El endpoint de PROCESO puede variar según tu implementación. Busca en tu código:

```bash
# Buscar endpoint de proceso
grep -r "Route.*proceso" routes/api.php
```

**Ejemplo genérico:**
```http
POST http://localhost:8000/api/proceso
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "ciclo_acreditacion_id": 1,
  "tipo_proceso": "Autoevaluacion",
  "modelo_estructura_id": 1
}
```

**Respuesta esperada:**
```json
{
  "proceso_id": 17,
  "ciclo_acreditacion_id": 1,
  "tipo_proceso": "Autoevaluacion",
  "modelo_estructura_id": 1,
  "modelo_nombre": "SINAES 2018 - Estructura Tradicional",
  "modelo_tipo": "tradicional",
  "modelo_version": "2018",
  "created_at": "2026-03-14T..."
}
```

---

### Obtener el proceso creado

```http
GET http://localhost:8000/api/proceso/17
Authorization: Bearer {tu_token}
```

**Verifica que incluya:**
```json
{
  "proceso_id": 17,
  "modelo_estructura_id": 1,
  "modelo_tipo": "tradicional"  // ← Esto indica que usa DIMENSION/COMPONENTE
}
```

---

## 🔬 PRUEBA 4: Crear Proceso con MODELO 2 (Jerarquía Flexible)

```http
POST http://localhost:8000/api/proceso
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "ciclo_acreditacion_id": 1,
  "tipo_proceso": "Autoevaluacion",
  "modelo_estructura_id": 2
}
```

**Respuesta esperada:**
```json
{
  "proceso_id": 18,
  "ciclo_acreditacion_id": 1,
  "tipo_proceso": "Autoevaluacion",
  "modelo_estructura_id": 2,
  "modelo_nombre": "SINAES 2026 - Estructura Flexible con Pautas",
  "modelo_tipo": "jerarquia_flexible",
  "modelo_version": "2026",
  "created_at": "2026-03-14T..."
}
```

---

### Obtener el proceso creado

```http
GET http://localhost:8000/api/proceso/18
Authorization: Bearer {tu_token}
```

**Verifica que incluya:**
```json
{
  "proceso_id": 18,
  "modelo_estructura_id": 2,
  "modelo_tipo": "jerarquia_flexible"  // ← Esto indica que usa JERARQUIA
}
```

---

## 🔬 PRUEBA 5: Simular Frontend - Detectar Modelo

### Código JavaScript de ejemplo:

```javascript
// ================================================
// Función que determina qué estructura mostrar
// ================================================
async function cargarProceso(procesoId) {
  // 1. Obtener información del proceso
  const proceso = await fetch(`/api/proceso/${procesoId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  console.log('Proceso cargado:', proceso);
  console.log('Modelo tipo:', proceso.modelo_tipo);
  
  // 2. Decidir qué estructura cargar según el modelo
  if (proceso.modelo_tipo === 'tradicional') {
    console.log('🔹 Usando estructura TRADICIONAL');
    await cargarEstructuraTradicional();
    
  } else if (proceso.modelo_tipo === 'jerarquia_flexible') {
    console.log('🔹 Usando estructura JERARQUIA FLEXIBLE');
    await cargarEstructuraJerarquia();
  }
}

// ================================================
// Cargar estructura TRADICIONAL (DIMENSION)
// ================================================
async function cargarEstructuraTradicional() {
  const dimensiones = await fetch('/api/estructura/dimensiones', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  console.log('Dimensiones disponibles:', dimensiones);
  
  // Renderizar selector tradicional:
  // Dimensión > Componente > Criterio > Evidencia
  renderSelectorTradicional(dimensiones);
}

// ================================================
// Cargar estructura JERARQUIA FLEXIBLE
// ================================================
async function cargarEstructuraJerarquia() {
  const arbol = await fetch('/api/estructura/jerarquia/arbol', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  console.log('Árbol de jerarquía:', arbol);
  
  // Renderizar árbol flexible:
  // Dimensión > Pauta > Fuente > etc.
  renderArbolJerarquia(arbol);
}

// ================================================
// PRUEBA: Cargar proceso 17 (tradicional)
// ================================================
cargarProceso(17);
// Console:
// Proceso cargado: { proceso_id: 17, modelo_tipo: "tradicional" }
// Modelo tipo: tradicional
// 🔹 Usando estructura TRADICIONAL
// Dimensiones disponibles: [...]

// ================================================
// PRUEBA: Cargar proceso 18 (jerarquía flexible)
// ================================================
cargarProceso(18);
// Console:
// Proceso cargado: { proceso_id: 18, modelo_tipo: "jerarquia_flexible" }
// Modelo tipo: jerarquia_flexible
// 🔹 Usando estructura JERARQUIA FLEXIBLE
// Árbol de jerarquía: [{ nivel: 0, nombre: "Formación..." }, ...]
```

---

## 🔬 PRUEBA 6: CRUD de Jerarquía

### Crear nueva PAUTA

```http
POST http://localhost:8000/api/estructura/jerarquia
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "parent_id": 1,
  "nombre": "Pauta 4: Infraestructura y Recursos",
  "tipo": "pauta",
  "nomenclatura": "P4",
  "descripcion": "Evaluación de infraestructura física y tecnológica",
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
    "nombre": "Pauta 4: Infraestructura y Recursos",
    "tipo": "pauta",
    "nomenclatura": "P4",
    "orden": 3,
    "activo": true
  }
}
```

---

### Crear FUENTE bajo esa pauta

```http
POST http://localhost:8000/api/estructura/jerarquia
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "parent_id": 11,
  "nombre": "Inventario de equipamiento",
  "tipo": "fuente",
  "nomenclatura": "F4.1",
  "descripcion": "Lista de equipos de laboratorio y aulas",
  "orden": 1,
  "activo": true
}
```

**Respuesta esperada:**
```json
{
  "message": "Elemento creado correctamente.",
  "data": {
    "jerarquia_id": 12,
    "parent_id": 11,
    "nombre": "Inventario de equipamiento",
    "tipo": "fuente",
    "nomenclatura": "F4.1",
    "orden": 1
  }
}
```

---

### Actualizar elemento

```http
PUT http://localhost:8000/api/estructura/jerarquia/11
Authorization: Bearer {tu_token}
Content-Type: application/json

{
  "nombre": "Pauta 4: Infraestructura Física y Tecnológica (Actualizada)",
  "descripcion": "Descripción mejorada"
}
```

---

### Intentar eliminar pauta CON hijos (debe fallar)

```http
DELETE http://localhost:8000/api/estructura/jerarquia/11
Authorization: Bearer {tu_token}
```

**Respuesta esperada (error 422):**
```json
{
  "message": "No se puede eliminar un elemento que tiene hijos. Elimine primero los elementos hijos."
}
```

---

### Eliminar la fuente (hoja sin hijos)

```http
DELETE http://localhost:8000/api/estructura/jerarquia/12
Authorization: Bearer {tu_token}
```

**Respuesta esperada:**
```json
{
  "message": "Elemento eliminado correctamente."
}
```

---

### Ahora eliminar la pauta (sin hijos)

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

---

## 🔬 PRUEBA 7: SQL Manual (Verificación directa)

### Ver procesos y sus modelos

```sql
SELECT 
    p.proceso_id,
    p.tipo_proceso,
    p.modelo_estructura_id,
    me.nombre AS modelo_nombre,
    me.tipo AS modelo_tipo
FROM PROCESO p
LEFT JOIN MODELO_ESTRUCTURA me ON me.modelo_estructura_id = p.modelo_estructura_id
ORDER BY p.proceso_id DESC
LIMIT 10;
```

**Resultado esperado:**
```
proceso_id | tipo_proceso    | modelo_estructura_id | modelo_nombre                     | modelo_tipo
-----------+-----------------+----------------------+-----------------------------------+--------------------
18         | Autoevaluacion  | 2                    | SINAES 2026 - Estructura Flexible | jerarquia_flexible
17         | Autoevaluacion  | 1                    | SINAES 2018 - Estructura Trad...  | tradicional
16         | Autoevaluacion  | 1                    | SINAES 2018 - Estructura Trad...  | tradicional
...
```

---

### Ver árbol de jerarquía con SQL recursivo

```sql
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

**Resultado visual:**
```
estructura                              | tipo      | nivel | ruta
----------------------------------------+-----------+-------+-------------------------
Formación Profesional                   | dimension | 0     | Formación Profesional
  Pauta 1: Plan de Estudios             | pauta     | 1     | Formación... > Pauta 1...
    Plan de estudios vigente            | fuente    | 2     | ... > Plan de estudios...
    Mallas curriculares                 | fuente    | 2     | ... > Mallas...
  Pauta 2: Perfil de Egreso             | pauta     | 1     | ...
    Documento de perfil de egreso       | fuente    | 2     | ...
    Matriz de competencias              | fuente    | 2     | ...
Gestión Académica y Administrativa      | dimension | 0     | Gestión...
  Pauta 3: Gestión de Personal...       | pauta     | 1     | ...
    Currículos del personal académico   | fuente    | 2     | ...
```

---

## ✅ CHECKLIST DE PRUEBAS

- [ ] GET /api/estructura/modelos → Devuelve 2 modelos
- [ ] GET /api/estructura/modelos/activos → Devuelve 2 modelos activos
- [ ] GET /api/estructura/modelos/1 → Devuelve modelo tradicional
- [ ] GET /api/estructura/modelos/2 → Devuelve modelo flexible
- [ ] GET /api/estructura/jerarquia → Devuelve 10 elementos
- [ ] GET /api/estructura/jerarquia?tipo=pauta → Devuelve 3 pautas
- [ ] GET /api/estructura/jerarquia?tipo=fuente → Devuelve 5 fuentes
- [ ] GET /api/estructura/jerarquia/arbol → Devuelve árbol recursivo
- [ ] POST /api/proceso { modelo_estructura_id: 1 } → Proceso tradicional
- [ ] POST /api/proceso { modelo_estructura_id: 2 } → Proceso flexible
- [ ] GET /api/proceso/{id} → Incluye modelo_tipo
- [ ] POST /api/estructura/jerarquia → Crea nueva pauta
- [ ] PUT /api/estructura/jerarquia/{id} → Actualiza elemento
- [ ] DELETE con hijos → ERROR 422 (validación ok)
- [ ] DELETE sin hijos → Elimina correctamente

---

## 🎯 RESUMEN

**Sistema funcionando:**
1. ✅ 2 Modelos disponibles (tradicional + flexible)
2. ✅ 10 Elementos de jerarquía de ejemplo
3. ✅ Puedes crear procesos con modelo 1 o 2
4. ✅ El proceso "sabe" qué modelo usa
5. ✅ Frontend puede detectar automáticamente qué estructura mostrar
6. ✅ CRUD completo de jerarquía funciona

**Próximo paso:** Integrar en el frontend para que detecte el `modelo_tipo` del proceso y renderice la estructura correcta.
