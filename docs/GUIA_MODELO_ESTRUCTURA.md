# 🎯 GUÍA: Sistema de Modelos de Estructura

## ¿QUÉ PROBLEMA RESUELVE?

Antes: No sabías si un proceso debía usar DIMENSION/COMPONENTE (viejo) o JERARQUIA (nuevo).

Ahora: Cada PROCESO tiene un `modelo_estructura_id` que define QUÉ jerarquía usar.

---

## 📋 TABLAS INVOLUCRADAS

### 1. MODELO_ESTRUCTURA (Nueva)
Define los modelos de jerarquía disponibles:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| modelo_estructura_id | BIGINT | Primary Key |
| nombre | VARCHAR(100) | "SINAES 2018 - Estructura Tradicional" |
| descripcion | TEXT | Descripción del modelo |
| tipo | VARCHAR(30) | `tradicional` o `jerarquia_flexible` |
| version | VARCHAR(20) | "2018", "2026" |
| activo | TINYINT | 1 = disponible, 0 = deshabilitado |

**Datos iniciales:**
```sql
ID | nombre                                      | tipo                | version
---+---------------------------------------------+---------------------+--------
1  | SINAES 2018 - Estructura Tradicional        | tradicional         | 2018
2  | SINAES 2026 - Estructura Flexible con Pautas| jerarquia_flexible  | 2026
```

---

### 2. PROCESO (Modificada)
Ahora incluye el campo:

```sql
ALTER TABLE PROCESO 
ADD COLUMN modelo_estructura_id BIGINT DEFAULT 1;
```

**Ejemplo de datos:**
```sql
proceso_id | ciclo_acreditacion_id | tipo_proceso    | modelo_estructura_id
-----------+-----------------------+-----------------+---------------------
1          | 1                     | Autoevaluacion  | 1  ← Usa DIMENSION/COMPONENTE
2          | 1                     | Acreditacion    | 1  ← Usa DIMENSION/COMPONENTE
3          | 2                     | Autoevaluacion  | 2  ← Usa JERARQUIA flexible
```

---

## 💻 CÓMO FUNCIONA EN CÓDIGO

### Backend: Crear Proceso con Modelo

```php
// Crear proceso con modelo TRADICIONAL (SINAES 2018)
DB::select('CALL SP_CREAR_PROCESO(?, ?, ?)', [
    $ciclo_id,
    'Autoevaluacion',
    1  // ← modelo_estructura_id = 1 (tradicional)
]);

// Crear proceso con modelo NUEVO (SINAES 2026)
DB::select('CALL SP_CREAR_PROCESO(?, ?, ?)', [
    $ciclo_id,
    'Autoevaluacion',
    2  // ← modelo_estructura_id = 2 (jerarquia_flexible)
]);
```

---

### Backend: Obtener Proceso (incluye info del modelo)

```php
$proceso = DB::select('CALL SP_BUSCAR_PROCESO(?)', [1])[0];

echo $proceso->proceso_id;           // → 1
echo $proceso->modelo_estructura_id; // → 1
echo $proceso->modelo_nombre;        // → "SINAES 2018 - Estructura Tradicional"
echo $proceso->modelo_tipo;          // → "tradicional"
echo $proceso->modelo_version;       // → "2018"
```

---

### Frontend: Determinar qué estructura mostrar

```javascript
// Al cargar un proceso
fetch(`/api/proceso/${procesoId}`)
  .then(r => r.json())
  .then(proceso => {
    console.log('Proceso:', proceso);
    console.log('Modelo:', proceso.modelo_tipo);
    
    // Decidir qué componente renderizar
    if (proceso.modelo_tipo === 'tradicional') {
      return <EstructuraTradicional 
        procesoId={proceso.proceso_id} 
      />;
    } else if (proceso.modelo_tipo === 'jerarquia_flexible') {
      return <EstructuraJerarquia 
        procesoId={proceso.proceso_id} 
      />;
    }
  });
```

---

## 🎨 EJEMPLO COMPLETO: Crear Asignación

### Flujo 1: Proceso con Modelo TRADICIONAL

```javascript
// 1. Usuario abre el proceso
const proceso = await fetch('/api/proceso/1').then(r => r.json());
// proceso.modelo_tipo = 'tradicional'

// 2. Frontend muestra estructura DIMENSION > COMPONENTE > CRITERIO
const dimensiones = await fetch('/api/estructura/dimensiones').then(r => r.json());

// 3. Usuario navega y selecciona evidencia
const evidenciaSeleccionada = {
  evidencia_id: 5,
  criterio_id: 3,
  criterio: {
    nomenclatura: 'CR1.1',
    componente: {
      nombre: 'Componente 1.1',
      dimension: {
        nombre: 'Dimensión 1'
      }
    }
  }
};

// 4. Crear asignación
await fetch('/api/asignaciones', {
  method: 'POST',
  body: JSON.stringify({
    proceso_id: 1,
    evidencia_id: 5,  // ← Evidencia del modelo viejo
    usuario_id: 10
  })
});

// 5. Mostrar ruta
console.log('Ruta:', 
  evidenciaSeleccionada.criterio.componente.dimension.nombre,
  '>',
  evidenciaSeleccionada.criterio.componente.nombre,
  '>',
  evidenciaSeleccionada.criterio.nomenclatura
);
// → "Dimensión 1 > Componente 1.1 > CR1.1"
```

---

### Flujo 2: Proceso con Modelo JERARQUIA_FLEXIBLE

```javascript
// 1. Usuario abre el proceso
const proceso = await fetch('/api/proceso/3').then(r => r.json());
// proceso.modelo_tipo = 'jerarquia_flexible'

// 2. Frontend muestra árbol de JERARQUIA
const arbol = await fetch('/api/estructura/jerarquia/arbol').then(r => r.json());

// 3. Usuario navega por pautas y fuentes
const jerarquiaSeleccionada = {
  jerarquia_id: 3,
  tipo: 'fuente',
  nombre: 'Plan de estudios vigente',
  nomenclatura: 'F1.1',
  parent: {
    jerarquia_id: 2,
    tipo: 'pauta',
    nombre: 'Pauta 1: Plan de Estudios',
    parent: {
      jerarquia_id: 1,
      tipo: 'dimension',
      nombre: 'Formación Profesional'
    }
  }
};

// 4. Crear asignación (si vinculas EVIDENCIA con JERARQUIA)
await fetch('/api/asignaciones', {
  method: 'POST',
  body: JSON.stringify({
    proceso_id: 3,
    evidencia_id: 20,       // ← Evidencia que tiene jerarquia_id = 3
    usuario_id: 10
  })
});

// 5. Mostrar ruta jerárquica
console.log('Ruta:', 
  jerarquiaSeleccionada.parent.parent.nombre,
  '>',
  jerarquiaSeleccionada.parent.nombre,
  '>',
  jerarquiaSeleccionada.nombre
);
// → "Formación Profesional > Pauta 1: Plan de Estudios > Plan de estudios vigente"
```

---

## 🔀 COMPONENTE FRONTEND: Selector Automático

```tsx
// SelectorEstructura.tsx
import { useEffect, useState } from 'react';

interface Props {
  procesoId: number;
  onSelectEvidencia: (evidencia: any) => void;
}

export const SelectorEstructura = ({ procesoId, onSelectEvidencia }: Props) => {
  const [proceso, setProceso] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Cargar proceso para saber qué modelo usa
    fetch(`/api/proceso/${procesoId}`)
      .then(r => r.json())
      .then(data => {
        setProceso(data);
        setLoading(false);
      });
  }, [procesoId]);

  if (loading) return <div>Cargando...</div>;

  // Renderizar componente según tipo de modelo
  return (
    <div className="selector-estructura">
      <div className="badge">
        Modelo: {proceso.modelo_nombre} ({proceso.modelo_version})
      </div>

      {proceso.modelo_tipo === 'tradicional' && (
        <EstructuraTradicionalSelector 
          procesoId={procesoId}
          onSelect={onSelectEvidencia}
        />
      )}

      {proceso.modelo_tipo === 'jerarquia_flexible' && (
        <EstructuraJerarquiaSelector 
          procesoId={procesoId}
          onSelect={onSelectEvidencia}
        />
      )}
    </div>
  );
};
```

---

## 🚦 MIGRACIONES A EJECUTAR

```powershell
# 1. Crear tabla MODELO_ESTRUCTURA y agregar campo a PROCESO
php artisan migrate --path=database/migrations/041_create_modelo_estructura_table.php

# 2. Actualizar stored procedures
php artisan migrate --path=database/migrations/042_add_modelo_estructura_stored_procedures.php
```

---

## ✅ CHECKLIST DE VERIFICACIÓN

Después de migrar:

```sql
-- 1. Verificar que existen los 2 modelos
SELECT * FROM MODELO_ESTRUCTURA;

-- 2. Verificar que PROCESO tiene el campo
DESCRIBE PROCESO;

-- 3. Verificar que procesos existentes usan modelo 1 (por defecto)
SELECT proceso_id, tipo_proceso, modelo_estructura_id FROM PROCESO;

-- 4. Probar stored procedure
CALL SP_OBTENER_MODELOS_ACTIVOS();
CALL SP_BUSCAR_PROCESO(1);
```

---

## 🎯 RESUMEN

**Antes:**
- No sabías si usar DIMENSION o JERARQUIA
- Todo estaba mezclado

**Ahora:**
- Cada PROCESO define su `modelo_estructura_id`
- Frontend pregunta: "¿Qué modelo usa este proceso?"
- Renderiza la estructura correcta automáticamente

**Ventajas:**
✅ Puedes tener procesos con modelo viejo y nuevo simultáneamente
✅ Migración gradual (no rompes nada existente)
✅ Escalable (puedes agregar SINAES 2030, 2034, etc.)
✅ Documentado (cada modelo tiene nombre y descripción)
