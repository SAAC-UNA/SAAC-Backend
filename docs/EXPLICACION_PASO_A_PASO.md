# 🎓 EXPLICACIÓN PASO A PASO: ¿Cómo funciona todo?

## 🤔 TU PREGUNTA:
> "¿Cómo voy a crear/mezclar las cosas si no sé a qué modelo pertenece?"

## 💡 RESPUESTA CORTA:
**NO creas la estructura cuando creas el proceso. La estructura YA EXISTE.**

---

## 📚 PASO A PASO CON EJEMPLO REAL

### 🏗️ FASE 1: PREPARACIÓN (Solo UNA VEZ - Ya está hecha)

#### 1.1 El Administrador crea las DIMENSIONES (Modelo Tradicional)

Esto ya existe en tu base de datos desde hace tiempo:

```sql
-- Estas dimensiones YA EXISTEN (no se crean cada vez)
DIMENSION
├─ ID 1: "Información y Análisis"
├─ ID 2: "Currículo"
├─ ID 3: "Personal Académico"
└─ ID 4: "Estudiantes"

-- Cada dimensión tiene COMPONENTES, CRITERIOS, EVIDENCIAS
-- Todo esto YA ESTÁ CREADO
```

**¿Cuándo se creó esto?**
- Hace meses/años
- Un administrador las creó manualmente UNA SOLA VEZ
- Están en las tablas: DIMENSION, COMPONENTE, CRITERIO, EVIDENCIA

---

#### 1.2 El Administrador crea la JERARQUIA (Modelo Flexible)

Esto es lo NUEVO que acabamos de crear:

```sql
-- Esta jerarquía YA EXISTE (la creamos con el seeder)
JERARQUIA
├─ ID 1: "Formación Profesional" (dimension)
│  ├─ ID 2: "Pauta 1: Plan de Estudios" (pauta)
│  │  ├─ ID 3: "Plan de estudios vigente" (fuente)
│  │  └─ ID 4: "Mallas curriculares" (fuente)
│  ├─ ID 5: "Pauta 2: Perfil de Egreso" (pauta)
│  │  ├─ ID 6: "Documento de perfil" (fuente)
│  │  └─ ID 7: "Matriz de competencias" (fuente)
└─ ID 8: "Gestión Académica" (dimension)
   └─ ID 9: "Pauta 3: Personal" (pauta)
      └─ ID 10: "Currículos" (fuente)
```

**¿Cuándo se creó esto?**
- Ahora (con el seeder que ejecutamos)
- Un administrador puede agregar más elementos cuando quiera
- Están en la tabla: JERARQUIA

---

### 🎯 FASE 2: CREAR UN PROCESO (Esto lo hace el usuario final)

Ahora un coordinador de carrera quiere crear un proceso de autoevaluación.

#### Escenario A: Usuario elige MODELO TRADICIONAL

```
┌─────────────────────────────────────────┐
│ 📝 CREAR NUEVO PROCESO                  │
├─────────────────────────────────────────┤
│ Carrera: Ingeniería en Sistemas         │
│ Ciclo: 2026-I                           │
│ Tipo: Autoevaluación                    │
│                                         │
│ Modelo de estructura:                   │
│ ◉ SINAES 2018 - Tradicional  ← ELIGE    │
│ ○ SINAES 2026 - Flexible                │
│                                         │
│         [Crear Proceso]                 │
└─────────────────────────────────────────┘
```

**Lo que pasa en el backend:**

```php
// Se ejecuta SP_CREAR_PROCESO
INSERT INTO PROCESO (
    ciclo_acreditacion_id,
    tipo_proceso,
    modelo_estructura_id  // ← Aquí se guarda: 1
) VALUES (
    5,
    'Autoevaluacion',
    1  // ← MODELO 1 (tradicional)
);

// Resultado: proceso_id = 17
```

**Datos guardados:**

```sql
PROCESO (ID 17)
├─ ciclo_acreditacion_id: 5
├─ tipo_proceso: "Autoevaluacion"
├─ modelo_estructura_id: 1  ← ESTO ES LA CLAVE
└─ (relación con MODELO_ESTRUCTURA tabla)
```

---

#### Escenario B: Usuario elige MODELO FLEXIBLE

```
┌─────────────────────────────────────────┐
│ 📝 CREAR NUEVO PROCESO                  │
├─────────────────────────────────────────┤
│ Carrera: Ingeniería en Sistemas         │
│ Ciclo: 2026-I                           │
│ Tipo: Autoevaluación                    │
│                                         │
│ Modelo de estructura:                   │
│ ○ SINAES 2018 - Tradicional             │
│ ◉ SINAES 2026 - Flexible     ← ELIGE    │
│                                         │
│         [Crear Proceso]                 │
└─────────────────────────────────────────┘
```

**Lo que pasa en el backend:**

```php
INSERT INTO PROCESO (
    ciclo_acreditacion_id,
    tipo_proceso,
    modelo_estructura_id  // ← Aquí se guarda: 2
) VALUES (
    5,
    'Autoevaluacion',
    2  // ← MODELO 2 (flexible)
);

// Resultado: proceso_id = 18
```

---

### 🖥️ FASE 3: TRABAJAR EN EL PROCESO (Frontend detecta qué mostrar)

#### Usuario abre el Proceso 17 (Tradicional)

**1. Frontend carga el proceso:**

```javascript
// GET /api/proceso/17
const proceso = await fetch('/api/proceso/17').then(r => r.json());

// Respuesta:
{
  "proceso_id": 17,
  "tipo_proceso": "Autoevaluacion",
  "modelo_estructura_id": 1,
  "modelo_tipo": "tradicional",      // ← BACKEND LO DICE
  "modelo_nombre": "SINAES 2018 - Estructura Tradicional"
}
```

**2. Frontend detecta el tipo:**

```javascript
if (proceso.modelo_tipo === 'tradicional') {
    console.log('🔹 Voy a mostrar selector TRADICIONAL');
    
    // Carga las dimensiones que YA EXISTEN
    const dimensiones = await fetch('/api/estructura/dimensiones')
        .then(r => r.json());
    
    // Muestra:
    // [1] Información y Análisis
    // [2] Currículo
    // [3] Personal Académico
    // [4] Estudiantes
    
    renderSelectorTradicional(dimensiones);
}
```

**3. Usuario crea una evidencia:**

```
┌─────────────────────────────────────────┐
│ 📄 CREAR EVIDENCIA                      │
├─────────────────────────────────────────┤
│ Dimensión:    [2] Currículo      ↓      │ ← Elige de lista existente
│ Componente:   [5] Plan Estudios  ↓      │ ← Elige de lista existente
│ Criterio:     [12] Vigencia      ↓      │ ← Elige de lista existente
│                                         │
│ Nombre: Plan de estudios 2026           │
│ Archivo: [Subir PDF]                    │
│                                         │
│         [Guardar Evidencia]             │
└─────────────────────────────────────────┘
```

**Lo que se guarda:**

```sql
INSERT INTO EVIDENCIA (
    proceso_id,
    criterio_id,      -- ← Referencia al CRITERIO que ya existía
    nombre,
    archivo
) VALUES (
    17,               -- El proceso que estamos trabajando
    12,               -- Criterio que usuario eligió (ya existía)
    'Plan de estudios 2026',
    'plan2026.pdf'
);
```

---

#### Usuario abre el Proceso 18 (Flexible)

**1. Frontend carga el proceso:**

```javascript
// GET /api/proceso/18
const proceso = await fetch('/api/proceso/18').then(r => r.json());

// Respuesta:
{
  "proceso_id": 18,
  "modelo_estructura_id": 2,
  "modelo_tipo": "jerarquia_flexible",   // ← DIFERENTE
  "modelo_nombre": "SINAES 2026 - Estructura Flexible"
}
```

**2. Frontend detecta el tipo:**

```javascript
if (proceso.modelo_tipo === 'jerarquia_flexible') {
    console.log('🔹 Voy a mostrar árbol JERARQUICO');
    
    // Carga la jerarquía que YA EXISTE
    const arbol = await fetch('/api/estructura/jerarquia/arbol')
        .then(r => r.json());
    
    // Muestra (árbol):
    // 📁 [1] Formación Profesional
    //   📋 [2] Pauta 1: Plan de Estudios
    //     📄 [3] Plan de estudios vigente
    //     📄 [4] Mallas curriculares
    //   📋 [5] Pauta 2: Perfil de Egreso
    //     📄 [6] Documento de perfil
    //     📄 [7] Matriz de competencias
    // 📁 [8] Gestión Académica
    //   📋 [9] Pauta 3: Personal
    //     📄 [10] Currículos
    
    renderArbolJerarquia(arbol);
}
```

**3. Usuario crea una evidencia:**

```
┌─────────────────────────────────────────┐
│ 📄 CREAR EVIDENCIA                      │
├─────────────────────────────────────────┤
│ Estructura (árbol):                     │
│                                         │
│ 📁 Formación Profesional                │
│   📋 Pauta 1: Plan de Estudios          │
│     ▸ 📄 Plan vigente                   │
│     ▸ 📄 Mallas curriculares            │
│   ▸ 📋 Pauta 2: Perfil                  │
│ ▸ 📁 Gestión Académica                  │
│                                         │
│ Seleccionado:                           │
│ 📄 [3] Plan de estudios vigente  ✓      │
│                                         │
│ Nombre: Plan estudios 2026 actualizado│
│ Archivo: [Subir PDF]                    │
│                                         │
│         [Guardar Evidencia]             │
└─────────────────────────────────────────┘
```

**Lo que se guarda (NUEVO modelo):**

```sql
-- Opción 1: Si ya tienes tabla EVIDENCIA_JERARQUIA
INSERT INTO EVIDENCIA_JERARQUIA (
    proceso_id,
    jerarquia_id,     -- ← Referencia a JERARQUIA (en lugar de criterio_id)
    nombre,
    archivo
) VALUES (
    18,               -- El proceso flexible
    3,                -- Elemento de jerarquía seleccionado (ya existía)
    'Plan estudios 2026 actualizado',
    'plan2026_v2.pdf'
);

-- Opción 2: Si reutilizas tabla EVIDENCIA (añadiendo jerarquia_id)
INSERT INTO EVIDENCIA (
    proceso_id,
    criterio_id,      -- NULL (porque no usa modelo tradicional)
    jerarquia_id,     -- 3 (nuevo campo que agregaste)
    nombre,
    archivo
) VALUES (
    18, NULL, 3, 'Plan estudios 2026 actualizado', 'plan2026_v2.pdf'
);
```

---

## 🔑 RESUMEN DE LA RESPUESTA A TU PREGUNTA

### ❓ "¿Cómo voy a crear/mezclar las cosas si no sé a qué modelo pertenece?"

**RESPUESTA:**

1. **NO creas la estructura cuando creas el proceso**
   - La estructura (DIMENSION/JERARQUIA) YA EXISTE
   - Un administrador la creó UNA SOLA VEZ

2. **Cuando creas un PROCESO:**
   - Solo eliges: modelo_estructura_id = 1 o 2
   - Es como elegir "¿qué plantilla quieres usar?"

3. **El sistema detecta automáticamente:**
   - Si proceso.modelo_tipo === 'tradicional' → usa DIMENSION
   - Si proceso.modelo_tipo === 'jerarquia_flexible' → usa JERARQUIA

4. **NO hay "mezcla":**
   - Un proceso SOLO usa UN modelo
   - Proceso 17 usa modelo 1 → solo ve dimensiones
   - Proceso 18 usa modelo 2 → solo ve jerarquía
   - Nunca ves ambos al mismo tiempo

---

## 📊 DIAGRAMA COMPLETO DEL FLUJO

```
┌──────────────────────────────────────────────────────────────┐
│ ADMINISTRADOR (hace esto UNA VEZ)                            │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ Crea DIMENSIONES (tabla DIMENSION)                           │
│   ├─ Dimensión 1: Información y Análisis                    │
│   ├─ Dimensión 2: Currículo                                 │
│   └─ Dimensión 3: Personal                                  │
│                                                              │
│ Crea JERARQUÍA (tabla JERARQUIA)                             │
│   ├─ [1] Formación Profesional (dimension)                  │
│   │   ├─ [2] Pauta 1: Plan (pauta)                          │
│   │   │   ├─ [3] Plan vigente (fuente)                      │
│   │   │   └─ [4] Mallas (fuente)                            │
│   └─ [8] Gestión Académica (dimension)                      │
│                                                              │
└──────────────────────────────────────────────────────────────┘
                          │
                          ↓
┌──────────────────────────────────────────────────────────────┐
│ COORDINADOR DE CARRERA (hace esto cuando inicia proceso)     │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ Crea PROCESO                                                 │
│   ├─ Carrera: Ingeniería                                    │
│   ├─ Ciclo: 2026-I                                          │
│   └─ Modelo: [Elige 1 o 2]  ← SOLO ELIGE, NO CREA           │
│                                                              │
│ Si eligió modelo 1:                                          │
│   → modelo_estructura_id = 1                                 │
│   → modelo_tipo = "tradicional"                              │
│                                                              │
│ Si eligió modelo 2:                                          │
│   → modelo_estructura_id = 2                                 │
│   → modelo_tipo = "jerarquia_flexible"                       │
│                                                              │
└──────────────────────────────────────────────────────────────┘
                          │
                          ↓
┌──────────────────────────────────────────────────────────────┐
│ FRONTEND (detecta automáticamente)                           │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ Lee: proceso.modelo_tipo                                     │
│                                                              │
│ if (modelo_tipo === 'tradicional') {                         │
│     // Muestra selector de:                                 │
│     // Dimensión > Componente > Criterio                    │
│     cargarDimensiones();  ← Usa estructura que ya existe    │
│ }                                                            │
│                                                              │
│ if (modelo_tipo === 'jerarquia_flexible') {                 │
│     // Muestra árbol de:                                    │
│     // Dimensión > Pauta > Fuente > ...                     │
│     cargarJerarquia();    ← Usa estructura que ya existe    │
│ }                                                            │
│                                                              │
└──────────────────────────────────────────────────────────────┘
                          │
                          ↓
┌──────────────────────────────────────────────────────────────┐
│ USUARIO TRABAJANDO EN PROCESO                                │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ Crea EVIDENCIAS                                              │
│   ├─ Selecciona elemento de la estructura que ya existe     │
│   ├─ Sube archivo                                           │
│   └─ Guarda evidencia vinculada al proceso                  │
│                                                              │
│ Si es proceso modelo 1:                                      │
│   → Evidencia vinculada a criterio_id                        │
│                                                              │
│ Si es proceso modelo 2:                                      │
│   → Evidencia vinculada a jerarquia_id                       │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## ✅ CONCLUSIÓN

**Tu confusión era pensar que:**
```
Cada proceso crea su propia estructura
```

**La realidad es:**
```
La estructura YA EXISTE → El proceso solo la REFERENCIA
```

**Analogía del mundo real:**

Es como un formulario de impuestos:

- **MODELO 1 (tradicional):** Formulario D-101 clásico
- **MODELO 2 (flexible):** Formulario D-150 nuevo

Cuando vas a declarar impuestos:
1. NO inventas un formulario nuevo cada vez
2. Solo eliges: "¿Qué formulario voy a llenar?" (D-101 o D-150)
3. El formulario ya tiene sus campos definidos
4. Solo llenas los datos

Lo mismo con tu sistema:
1. NO creas dimensiones/pautas cada vez
2. Solo eliges: "¿Qué modelo voy a usar?" (1 o 2)
3. La estructura ya está definida
4. Solo creas evidencias vinculadas a esa estructura

---

## 🎯 DATO IMPORTANTE

**¿Qué pasa con procesos viejos que no tienen modelo_estructura_id?**

```sql
SELECT * FROM PROCESO WHERE modelo_estructura_id IS NULL;
```

Esos procesos siguen funcionando con el modelo tradicional por defecto.
El sistema es **retrocompatible**.

---

¿Ahora tiene más sentido? 🤓
