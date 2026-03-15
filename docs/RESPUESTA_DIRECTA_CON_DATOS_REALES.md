# 🎯 RESPUESTA DIRECTA: ¿Cómo funciona con DATOS REALES?

## 📊 LO QUE ACABAS DE VER

Tu base de datos tiene **AHORA MISMO**:

### ✅ 2 Modelos disponibles:

```
[1] SINAES 2018 - Estructura Tradicional (tipo: tradicional)
[2] SINAES 2026 - Estructura Flexible (tipo: jerarquia_flexible)
```

### ✅ 4 Dimensiones TRADICIONALES (ya creadas hace tiempo):

```
[1] Relación con el contexto
[2] Recursos
[3] Proceso educativo
[4] Resultados
```

### ✅ 10 Elementos de JERARQUÍA (recién creados):

```
📁 [1] Formación Profesional
   📋 [2] Pauta 1: Plan de Estudios
      📄 [3] Plan de estudios vigente
      📄 [4] Mallas curriculares
   📋 [5] Pauta 2: Perfil de Egreso
      📄 [6] Documento de perfil de egreso
      📄 [7] Matriz de competencias
📁 [8] Gestión Académica y Administrativa
   📋 [9] Pauta 3: Gestión de Personal Académico
      📄 [10] Currículos del personal académico
```

### ✅ 16 Procesos existentes:

```
Procesos 12-16: Todos tienen modelo_estructura_id = 1 (tradicional)
```

---

## 🤔 TU PREGUNTA: "¿Cómo voy a crear/mezclar las cosas si no sé a qué modelo pertenece?"

## 💡 RESPUESTA CON DATOS REALES:

### Ejemplo 1: Usuario crea NUEVO proceso con modelo TRADICIONAL

```
┌─────────────────────────────────────────┐
│ 📝 FORMULARIO: Crear Proceso            │
├─────────────────────────────────────────┤
│ Carrera: Ingeniería en Sistemas         │
│ Ciclo: 2026-I                           │
│ Tipo: Autoevaluación                    │
│                                         │
│ Modelo de estructura:                   │
│ ◉ SINAES 2018 - Tradicional    ← ELIGE  │
│ ○ SINAES 2026 - Flexible                │
│                                         │
│         [Crear Proceso]                 │
└─────────────────────────────────────────┘
```

**LO QUE PASA:**

```sql
-- Se crea el proceso con modelo 1
INSERT INTO PROCESO (
    ciclo_acreditacion_id,
    tipo_proceso,
    modelo_estructura_id
) VALUES (
    5,
    'Autoevaluacion',
    1  ← MODELO TRADICIONAL
);

-- Resultado: proceso_id = 17
```

**AHORA el usuario trabaja en ese proceso:**

```
┌─────────────────────────────────────────┐
│ 📄 Crear Evidencia (Proceso 17)         │
├─────────────────────────────────────────┤
│ El sistema detectó: modelo_tipo = "tradicional"
│                                         │
│ Dimensión:                              │
│ ○ [1] Relación con el contexto  ← YA EXISTÍAN
│ ◉ [2] Recursos                          │
│ ○ [3] Proceso educativo                 │
│ ○ [4] Resultados                        │
│                                         │
│ (Usuario selecciona [2] Recursos)       │
│                                         │
│ Componente:  [Carga componentes de      │
│               la dimensión 2]           │
│                                         │
│ Criterio:    [Carga criterios del       │
│               componente seleccionado]  │
│                                         │
│ Nombre: Presupuesto 2026                │
│ Archivo: [presupuesto.pdf]              │
│                                         │
│         [Guardar Evidencia]             │
└─────────────────────────────────────────┘
```

**¿De dónde salieron las dimensiones?**
```
NO las creaste ahora.
YA EXISTÍAN en la tabla DIMENSION desde hace tiempo.
Solo las estás USANDO.
```

---

### Ejemplo 2: Usuario crea NUEVO proceso con modelo FLEXIBLE

```
┌─────────────────────────────────────────┐
│ 📝 FORMULARIO: Crear Proceso            │
├─────────────────────────────────────────┤
│ Carrera: Ingeniería en Sistemas         │
│ Ciclo: 2026-I                           │
│ Tipo: Autoevaluación                    │
│                                         │
│ Modelo de estructura:                   │
│ ○ SINAES 2018 - Tradicional             │
│ ◉ SINAES 2026 - Flexible       ← ELIGE  │
│                                         │
│         [Crear Proceso]                 │
└─────────────────────────────────────────┘
```

**LO QUE PASA:**

```sql
-- Se crea el proceso con modelo 2
INSERT INTO PROCESO (
    ciclo_acreditacion_id,
    tipo_proceso,
    modelo_estructura_id
) VALUES (
    5,
    'Autoevaluacion',
    2  ← MODELO FLEXIBLE
);

-- Resultado: proceso_id = 18
```

**AHORA el usuario trabaja en ese proceso:**

```
┌─────────────────────────────────────────┐
│ 📄 Crear Evidencia (Proceso 18)         │
├─────────────────────────────────────────┤
│ El sistema detectó: modelo_tipo = "jerarquia_flexible"
│                                         │
│ Estructura de pautas (árbol):           │
│                                         │
│ 📁 Formación Profesional       ← YA EXISTÍAN
│   📋 Pauta 1: Plan de Estudios          │
│     ▸ 📄 Plan vigente                   │
│     ▸ 📄 Mallas curriculares            │
│   ▸ 📋 Pauta 2: Perfil                  │
│ ▸ 📁 Gestión Académica                  │
│                                         │
│ (Usuario selecciona un elemento)        │
│                                         │
│ Seleccionado:                           │
│ ✓ 📄 [3] Plan de estudios vigente       │
│                                         │
│ Nombre: Plan estudios 2026              │
│ Archivo: [plan2026.pdf]                 │
│                                         │
│         [Guardar Evidencia]             │
└─────────────────────────────────────────┘
```

**¿De dónde salió la jerarquía?**
```
NO la creaste ahora.
YA EXISTÍA en la tabla JERARQUIA (la creamos con el seeder).
Solo la estás USANDO.
```

---

## 🔑 LA CLAVE: NO SE MEZCLA NADA

### ❌ ESTO NO PASA:

```
"¿Qué pasa si tengo procesos de ambos modelos?"

NO SE MEZCLAN.

Proceso 17 (modelo 1):
  → Solo ve DIMENSIONES [1,2,3,4]
  → Solo puede crear evidencias vinculadas a CRITERIOS
  → Frontend muestra selector tradicional

Proceso 18 (modelo 2):
  → Solo ve JERARQUÍA [1,2,3,4,5,6,7,8,9,10]
  → Solo puede crear evidencias vinculadas a JERARQUIA
  → Frontend muestra árbol flexible

NUNCA VES AMBOS AL MISMO TIEMPO.
```

### ✅ ESTO SÍ PASA:

```
Cada proceso "sabe" qué modelo usa:

proceso.modelo_estructura_id = 1 → usa DIMENSION/COMPONENTE/CRITERIO
proceso.modelo_estructura_id = 2 → usa JERARQUIA

El frontend pregunta:
  "¿Qué modelo usas?"
  
Y el backend responde:
  "Uso modelo 1 (tradicional)" o "Uso modelo 2 (flexible)"
  
Frontend renderiza la UI correcta automáticamente.
```

---

## 📊 FLUJO VISUAL CON TUS DATOS REALES

### Escenario: Coordinador crea proceso TRADICIONAL

```
PASO 1: Crear proceso
┌─────────────────┐
│ Frontend        │
│ [Formulario]    │
└────────┬────────┘
         │ POST /api/proceso
         │ { modelo_estructura_id: 1 }
         ↓
┌─────────────────┐
│ Backend         │
│ Guarda proceso  │
│ proceso_id = 17 │
│ modelo_id = 1   │
└────────┬────────┘
         │ Respuesta:
         │ { proceso_id: 17,
         │   modelo_tipo: "tradicional" }
         ↓
┌─────────────────┐
│ Frontend        │
│ Redirige a      │
│ /proceso/17     │
└─────────────────┘
```

```
PASO 2: Trabajar en proceso 17
┌─────────────────┐
│ Frontend        │
│ Carga proceso   │
└────────┬────────┘
         │ GET /api/proceso/17
         ↓
┌─────────────────┐
│ Backend         │
│ Devuelve:       │
│ modelo_tipo:    │
│ "tradicional"   │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Frontend        │
│ if (modelo_tipo │
│  === 'trad...') │
│                 │
│ cargarDimen...()│
└────────┬────────┘
         │ GET /api/estructura/dimensiones
         ↓
┌─────────────────┐
│ Backend         │
│ Devuelve:       │
│ [1] Relación... │
│ [2] Recursos    │
│ [3] Proceso...  │
│ [4] Resultados  │ ← LAS QUE YA EXISTÍAN
└────────┬────────┘
         ↓
┌─────────────────┐
│ Frontend        │
│ Muestra selector│
│ de 4 dimensiones│
│                 │
│ Usuario trabaja │
└─────────────────┘
```

---

### Escenario: Coordinador crea proceso FLEXIBLE

```
PASO 1: Crear proceso
┌─────────────────┐
│ Frontend        │
│ [Formulario]    │
└────────┬────────┘
         │ POST /api/proceso
         │ { modelo_estructura_id: 2 }
         ↓
┌─────────────────┐
│ Backend         │
│ Guarda proceso  │
│ proceso_id = 18 │
│ modelo_id = 2   │
└────────┬────────┘
         │ Respuesta:
         │ { proceso_id: 18,
         │   modelo_tipo: "jerarquia_flexible" }
         ↓
┌─────────────────┐
│ Frontend        │
│ Redirige a      │
│ /proceso/18     │
└─────────────────┘
```

```
PASO 2: Trabajar en proceso 18
┌─────────────────┐
│ Frontend        │
│ Carga proceso   │
└────────┬────────┘
         │ GET /api/proceso/18
         ↓
┌─────────────────┐
│ Backend         │
│ Devuelve:       │
│ modelo_tipo:    │
│ "jerarquia_...  │
│         flexible"
└────────┬────────┘
         ↓
┌─────────────────┐
│ Frontend        │
│ if (modelo_tipo │
│  === 'jeraq...') │
│                 │
│ cargarJerarq...()│
└────────┬────────┘
         │ GET /api/estructura/jerarquia/arbol
         ↓
┌─────────────────┐
│ Backend         │
│ Devuelve:       │
│ 📁 [1] Form...  │
│   📋 [2] Pau... │
│     📄 [3] ...  │
│ (10 elementos)  │ ← LOS QUE YA EXISTÍAN
└────────┬────────┘
         ↓
┌─────────────────┐
│ Frontend        │
│ Muestra árbol   │
│ de 10 elementos │
│                 │
│ Usuario trabaja │
└─────────────────┘
```

---

## ✅ RESUMEN FINAL

### ❓ Tu pregunta:
> "¿Cómo voy a crear/mezclar las cosas si no sé a qué modelo pertenece?"

### ✅ Respuesta:

1. **NO creas estructuras cuando creas procesos**
   - Las estructuras YA EXISTEN
   - DIMENSION: 4 elementos (ya existían)
   - JERARQUIA: 10 elementos (recién creados)

2. **Solo ELIGES qué modelo usar**
   - Al crear proceso: seleccionas modelo 1 o 2
   - Es como elegir "plantilla A" o "plantilla B"

3. **El sistema detecta automáticamente**
   - Backend: guarda modelo_estructura_id
   - Backend: devuelve modelo_tipo en la respuesta
   - Frontend: lee modelo_tipo y renderiza UI correcta

4. **NO hay mezcla**
   - Proceso con modelo 1 → solo usa DIMENSION
   - Proceso con modelo 2 → solo usa JERARQUIA
   - Nunca ves ambos juntos

5. **Los procesos viejos siguen funcionando**
   - Tus 16 procesos actuales usan modelo 1
   - Nuevos procesos pueden usar modelo 2
   - Conviven sin problemas

---

## 🎯 PARA CONFIRMAR QUE ENTENDISTE:

**Pregunta:** "Si creo un proceso nuevo mañana, ¿tengo que crear las dimensiones o pautas?"

**Respuesta:** NO. Ya existen. Solo eliges cuál modelo usar (1 o 2).

**Pregunta:** "Si tengo 2 procesos, uno con modelo 1 y otro con modelo 2, ¿se mezclan?"

**Respuesta:** NO. Cada proceso usa SU modelo. Son independientes.

**Pregunta:** "¿Dónde están las estructuras almacenadas?"

**Respuesta:** 
- Modelo 1: tablas DIMENSION, COMPONENTE, CRITERIO (ya existían)
- Modelo 2: tabla JERARQUIA (recién creada, 10 elementos)

**Pregunta:** "¿Cómo sabe el frontend qué mostrar?"

**Respuesta:** Lee `proceso.modelo_tipo` y renderiza la UI correcta.

---

## 🚀 PRÓXIMO PASO PARA TI

1. **Ver los modelos disponibles:**
   ```bash
   php ver_estado_sistema.php
   ```

2. **Modificar el formulario de crear proceso (Frontend):**
   ```tsx
   <select name="modelo_estructura_id">
     <option value="1">SINAES 2018 - Tradicional</option>
     <option value="2">SINAES 2026 - Flexible</option>
   </select>
   ```

3. **En tu página de proceso (Frontend):**
   ```tsx
   function ProcesoPagina({ procesoId }) {
     const [proceso, setProceso] = useState(null);
     
     useEffect(() => {
       fetch(`/api/proceso/${procesoId}`)
         .then(r => r.json())
         .then(setProceso);
     }, [procesoId]);
     
     if (!proceso) return <Loading />;
     
     // AQUÍ ESTÁ LA CLAVE:
     if (proceso.modelo_tipo === 'tradicional') {
       return <VistaTradicional proceso={proceso} />;
     } else {
       return <VistaJerarquica proceso={proceso} />;
     }
   }
   ```

---

¿Ahora sí quedó claro? 😊
