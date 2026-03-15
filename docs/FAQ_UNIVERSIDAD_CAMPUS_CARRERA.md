# 🎓 SAAC - Respuestas Rápidas sobre Universidad/Campus/Carrera

## ❓ ¿Cómo se maneja universidad, campus, carrera en el sistema?

### Estructura Básica

```
🏛️  UNIVERSIDAD (ej: Universidad Nacional)
     │
     ├─ 🏢 SEDE/CAMPUS (ej: Campus Central)
     │   │
     │   ├─ 🎓 CARRERA (ej: Ing. Sistemas)
     │   │   │
     │   │   └─ 🔄 CICLO_ACREDITACION (ej: 2026-2030)
     │   │       │
     │   │       └─ 📋 PROCESO (Autoevaluación)
     │   │           │
     │   │           └─ 📊 MODELO_ESTRUCTURA (SINAES 2018 o 2026)
     │   │               │
     │   │               └─ 🌳 ESTRUCTURA (Dimensiones/Jerarquías)
     │   │
     │   └─ 🎓 CARRERA (ej: Ing. Industrial)
     │       └─ ...
     │
     └─ 🏢 SEDE/CAMPUS (ej: Campus Norte)
         └─ 🎓 CARRERA (ej: Ing. Sistemas - MISMA carrera, diferente campus)
             └─ 🔄 CICLO DIFERENTE (cada campus tiene su propio ciclo)
```

---

## 🔑 Conceptos Clave

### 1. Universidad y Campus son INDEPENDIENTES de las Estructuras

**✅ Las estructuras (DIMENSION/JERARQUIA) son GLOBALES**

NO se crean por universidad, ni por campus, ni por carrera.

Se crean UNA VEZ y se REUTILIZAN en todos los procesos.

**Ejemplo:**

```
JERARQUIA: "D1: Formación Profesional → P1: Plan de Estudios"

Se usa en:
✓ UNA - Campus Central - Ing. Sistemas
✓ UNA - Campus Norte - Ing. Sistemas  
✓ UNA - Campus Central - Ing. Industrial
✓ UCR - Sede Guanacaste - Ing. Civil

MISMA estructura, diferentes procesos/evidencias
```

---

### 2. Cada Campus tiene sus PROPIOS Ciclos y Procesos

**❌ INCORRECTO:**
```
Carrera: Ing. Sistemas
└─ UN solo ciclo de acreditación para todos los campus
```

**✅ CORRECTO:**
```
Carrera: Ing. Sistemas - Campus Central
└─ Ciclo 2026-2030 (ID: 1)
    └─ Proceso Autoevaluación (ID: 10)

Carrera: Ing. Sistemas - Campus Norte
└─ Ciclo 2026-2030 (ID: 2) ← DIFERENTE ciclo
    └─ Proceso Autoevaluación (ID: 11) ← DIFERENTE proceso
```

**Esto permite:**
- Evaluaciones independientes por campus
- Evidencias específicas de cada campus
- Calendarios diferentes
- Resultados diferentes

---

### 3. Una Carrera puede estar en MÚLTIPLES Campus

**Relación N:N a través de CARRERA_SEDE**

```sql
CARRERA: Ing. Sistemas (ID: 1)
  ├─ CARRERA_SEDE (ID: 10) → SEDE: Campus Central
  ├─ CARRERA_SEDE (ID: 11) → SEDE: Campus Norte
  └─ CARRERA_SEDE (ID: 12) → SEDE: Campus Sur

CARRERA: Medicina (ID: 2)
  └─ CARRERA_SEDE (ID: 20) → SEDE: Campus Central (solo en un campus)
```

**Base de datos:**

| carrera_sede_id | carrera_id | sede_id | Interpretación |
|-----------------|------------|---------|----------------|
| 10 | 1 (Ing. Sistemas) | 1 (Central) | Sistemas ofrecida en Central |
| 11 | 1 (Ing. Sistemas) | 2 (Norte) | Sistemas ofrecida en Norte |
| 20 | 2 (Medicina) | 1 (Central) | Medicina solo en Central |

---

## 🎯 ¿Afecta Universidad/Campus a la funcionalidad?

### ❌ NO AFECTA a:

1. **Estructuras (DIMENSION/JERARQUIA)**
   - Las dimensiones, componentes, pautas y fuentes son las MISMAS
   - Se definen a nivel de MODELO_ESTRUCTURA, no por universidad

2. **Modelos de Estructura (SINAES 2018/2026)**
   - Los modelos son globales
   - Cualquier universidad puede usar cualquier modelo

3. **Nomenclatura y Categorías**
   - Las categorías (A, B, C, D) son las mismas independientemente del campus

### ✅ SÍ AFECTA a:

1. **Ciclos de Acreditación**
   - Cada CARRERA-SEDE tiene sus propios ciclos
   - Campus Central puede estar en Ciclo 2026-2030
   - Campus Norte puede estar en Ciclo 2023-2028

2. **Procesos**
   - Cada campus tiene sus propios procesos de autoevaluación
   - Pueden usar modelos diferentes (Central→SINAES 2018, Norte→SINAES 2026)

3. **Evidencias y Recursos**
   - Cada campus sube sus propias evidencias
   - Las evidencias NO se comparten entre campus (son específicas)

4. **Evaluación y Resultados**
   - Cada campus tiene su propia evaluación
   - Los resultados de acreditación son independientes por campus

---

## 📊 Ejemplo Práctico Completo

### Escenario

**Universidad Nacional** tiene:
- Campus Central
- Campus Regional Norte

Ambos campus ofrecen **Ingeniería en Sistemas**

### Configuración en Base de Datos

```
UNIVERSIDAD
  universidad_id: 1
  nombre: "Universidad Nacional"

SEDE (Campus Central)
  sede_id: 1
  universidad_id: 1
  nombre: "Campus Central"

SEDE (Campus Norte)
  sede_id: 2
  universidad_id: 1
  nombre: "Campus Regional Norte"

CARRERA
  carrera_id: 1
  nombre: "Ingeniería en Sistemas"

CARRERA_SEDE (Sistemas en Central)
  carrera_sede_id: 10
  carrera_id: 1
  sede_id: 1

CARRERA_SEDE (Sistemas en Norte)
  carrera_sede_id: 11
  carrera_id: 1
  sede_id: 2

CICLO_ACREDITACION (Central)
  ciclo_acreditacion_id: 100
  carrera_sede_id: 10
  nombre: "Ciclo 2026-2030"

CICLO_ACREDITACION (Norte)
  ciclo_acreditacion_id: 101
  carrera_sede_id: 11
  nombre: "Ciclo 2026-2030"

PROCESO (Autoevaluación Central - SINAES 2018)
  proceso_id: 1000
  ciclo_acreditacion_id: 100
  modelo_estructura_id: 1 (SINAES 2018 Tradicional)

PROCESO (Autoevaluación Norte - SINAES 2026)
  proceso_id: 1001
  ciclo_acreditacion_id: 101
  modelo_estructura_id: 2 (SINAES 2026 Flexible)
```

### Resultado

**Campus Central usa:**
- Modelo SINAES 2018 (Tradicional)
- Estructura: DIMENSION → COMPONENTE → CRITERIO
- Sus propias evidencias
- Su propio resultado de acreditación

**Campus Norte usa:**
- Modelo SINAES 2026 (Flexible)
- Estructura: DIMENSION → PAUTA → FUENTE
- Sus propias evidencias (diferentes a Central)
- Su propio resultado de acreditación (diferente a Central)

**PERO ambos comparten:**
- ✅ La misma carrera (Ing. Sistemas)
- ✅ La misma universidad
- ✅ Las mismas estructuras predefinidas (solo seleccionan cuál usar)

---

## 🚀 Flujo de Creación (Resumen Super Rápido)

### Para crear un proceso de acreditación:

1. **Crear Universidad** (si no existe)
   ```sql
   CALL SP_CREAR_UNIVERSIDAD('Universidad Nacional', 1);
   ```

2. **Crear Campus** (si no existe)
   ```sql
   CALL SP_CREAR_SEDE(1, 'Campus Central', 1);
   ```

3. **Crear Carrera** (si no existe)
   ```sql
   CALL SP_CREAR_CARRERA('Ing. Sistemas', 1);
   ```

4. **Asociar Carrera con Campus**
   ```sql
   CALL SP_CREAR_CARRERA_SEDE(1, 1);
   ```

5. **Crear Ciclo de Acreditación**
   ```sql
   CALL SP_CREAR_CICLO_ACREDITACION(1, 'Ciclo 2026-2030');
   ```

6. **Crear Proceso seleccionando modelo**
   ```sql
   -- Opción A: Usar SINAES 2018
   CALL SP_CREAR_PROCESO(1, 'Autoevaluacion', 1);
   
   -- Opción B: Usar SINAES 2026
   CALL SP_CREAR_PROCESO(1, 'Autoevaluacion', 2);
   ```

7. **Usar las estructuras predefinidas**
   ```sql
   -- Si usas modelo 1 (Tradicional):
   SELECT * FROM DIMENSION WHERE modelo_estructura_id = 1;
   
   -- Si usas modelo 2 (Flexible):
   CALL SP_OBTENER_JERARQUIAS(NULL, 2);
   ```

---

## ✅ Conclusión

### ¿Universidad/Campus afectan en algo?

**SÍ, pero solo a nivel organizacional:**
- ✅ Cada campus tiene ciclos/procesos/evidencias SEPARADOS
- ✅ Permite evaluaciones independientes
- ✅ Resultados independientes por campus

**NO afectan a las estructuras:**
- ❌ Las estructuras (dimensiones, pautas) son GLOBALES
- ❌ NO se duplican por universidad o campus
- ❌ Solo se SELECCIONA qué modelo usar

### Analogía

Piensa en las estructuras como **plantillas de Word**:

- ✅ Las plantillas están en un servidor compartido (globales)
- ✅ Campus Central abre la plantilla "SINAES_2018.docx"
- ✅ Campus Norte abre la plantilla "SINAES_2026.docx"
- ✅ Cada uno llena su propio documento (evidencias)
- ❌ NO se crea una plantilla nueva por cada campus

---

**Para más detalles, consulta:**
- [GUIA_COMPLETA_FLUJO_SISTEMA.md](./GUIA_COMPLETA_FLUJO_SISTEMA.md) - Documentación técnica completa
- [CAMBIOS_MODELO_CATEGORIA_JERARQUIA.md](./CAMBIOS_MODELO_CATEGORIA_JERARQUIA.md) - Implementación de modelo_estructura_id

**Última actualización:** 14 de marzo de 2026
