# Sistema de Modelos, Jerarquía y Proceso — SAAC

## Resumen de cambios recientes
Migraciones `2026_03_14_000001` y `2026_03_14_000002` agregaron:
- `modelo_estructura_id` en tabla `JERARQUIA`
- `categoria` (A/B/C/D) en tabla `JERARQUIA`
- `modelo_estructura_id` en tabla `PROCESO`
- Stored procedures actualizados para filtrar por modelo y tipo

---

## 1. Tabla MODELO_ESTRUCTURA

Contiene **dos modelos predefinidos** insertados directamente en la migración `041_create_modelo_estructura_table.php`. No se pueden crear ni editar por API — son fijos del sistema.

| ID | nombre                                    | tipo                | version | activo |
|----|-------------------------------------------|---------------------|---------|--------|
| 1  | SINAES 2018 - Estructura Tradicional      | `tradicional`       | 2018    | true   |
| 2  | SINAES 2026 - Estructura Flexible con Pautas | `jerarquia_flexible` | 2026 | true   |

### Campos

```
MODELO_ESTRUCTURA
├── modelo_estructura_id  PK
├── nombre                VARCHAR(100)
├── descripcion           TEXT nullable
├── tipo                  VARCHAR(30)   → 'tradicional' | 'jerarquia_flexible'
├── version               VARCHAR(20)   → '2018' | '2026'
├── activo                BOOLEAN
└── timestamps
```

### Rutas disponibles (solo lectura y toggle)

```
GET    /api/estructura/modelos              → listar todos
GET    /api/estructura/modelos/activos      → solo activos
GET    /api/estructura/modelos/{id}         → uno específico
PATCH  /api/estructura/modelos/{id}/toggle  → activar/desactivar  [requiere: modelos.toggle]
```

> `POST` y `PUT/PATCH` están intencionalmente comentados. Los modelos no se crean ni editan desde la API.

---

## 2. Tabla JERARQUIA

Estructura **árbol autorreferenciada** para el modelo SINAES 2026 (`jerarquia_flexible`). Cada nodo puede ser padre de otros nodos del mismo modelo.

### Campos

```
JERARQUIA
├── jerarquia_id          PK
├── modelo_estructura_id  FK → MODELO_ESTRUCTURA  ← NUEVO (migración _000001)
├── parent_id             FK → JERARQUIA (autorreferencia), nullable
├── nombre                VARCHAR(100)
├── tipo                  VARCHAR(30)   → libre: 'dimension', 'pauta', 'fuente', etc.
├── categoria             ENUM(A,B,C,D) nullable  ← NUEVO (migración _000001)
│                         → Solo relevante cuando tipo='pauta'
│                         → A = mayor importancia, D = menor
├── nomenclatura          VARCHAR(20)  nullable  → ej: 'D1', 'P2.3'
├── descripcion           TEXT         nullable
├── orden                 INTEGER      → posición entre hermanos (mismo parent_id)
├── activo                BOOLEAN
└── timestamps
```

### Relaciones entre nodos

```
parent_id = NULL           → nodo raíz (nivel 0)
parent_id = 5              → hijo del nodo ID=5 (nivel 1, 2, 3...)

Ejemplo árbol SINAES 2026:
  Gestión Académica  (tipo=dimension, parent=null)
    └── Planificación  (tipo=pauta, parent=1, categoria=A)
          └── Plan de estudios actualizado  (tipo=fuente, parent=5)
```

### Reglas de validación (JerarquiaRequest)

| Campo                | Requerido | Validación extra                          |
|----------------------|-----------|-------------------------------------------|
| `modelo_estructura_id` | Sí (create) | Debe existir en `MODELO_ESTRUCTURA`    |
| `parent_id`          | No        | Si se da, debe existir; no puede ser el mismo ID |
| `nombre`             | Sí        | max 100 chars                             |
| `tipo`               | Sí        | max 30 chars, libre                       |
| `categoria`          | No        | Solo `A`, `B`, `C`, `D`                  |
| `nomenclatura`       | No        | max 20 chars                              |
| `orden`              | No        | entero ≥ 0                               |
| `activo`             | No        | boolean                                   |

### Rutas disponibles

```
GET    /api/estructura/jerarquia                     → listar [?tipo=pauta&modelo_estructura_id=2]
GET    /api/estructura/jerarquia/{id}                → uno específico
POST   /api/estructura/jerarquia                     → crear   [requiere: jerarquia.create]
PUT|PATCH /api/estructura/jerarquia/{id}             → editar  [requiere: jerarquia.edit]
DELETE /api/estructura/jerarquia/{id}                → eliminar [requiere: jerarquia.delete]
                                                        ⚠ Falla si tiene hijos
PATCH  /api/estructura/jerarquia/{id}/active         → activar/desactivar [requiere: jerarquia.edit]
                                                        → Desactivar aplica cascada a todos los hijos
```

> `GET /api/estructura/jerarquia/arbol` está comentado — funcionalidad de árbol visual para futuro.

---

## 3. Tabla PROCESO — campo modelo_estructura_id

Cada proceso ahora tiene un FK al modelo de estructura. Determina **qué tipo de estructura cargar** para ese proceso.

```
PROCESO
├── proceso_id
├── ciclo_acreditacion_id   FK → CICLO_ACREDITACION
├── modelo_estructura_id    FK → MODELO_ESTRUCTURA  ← NUEVO (default=1)
├── tipo_proceso
└── ...
```

- Default = `1` (SINAES 2018 tradicional) para procesos existentes sin asignar
- Al crear un proceso nuevo, el frontend envía `modelo_estructura_id: 1` o `2`

---

## 4. Cómo el sistema sabe qué estructura cargar

```php
$proceso = Process::find($id);
$modelo  = $proceso->modeloEstructura;  // relación BelongsTo

if ($modelo->esTradicional()) {
    // ID=1 → Carga DIMENSION / COMPONENTE / CRITERIO (tablas fijas)

} elseif ($modelo->esJerarquiaFlexible()) {
    // ID=2 → Carga JERARQUIA WHERE modelo_estructura_id = 2
}
```

### Métodos del modelo ModeloEstructura

```php
$modelo->esTradicional()        // tipo === 'tradicional'
$modelo->esJerarquiaFlexible()  // tipo === 'jerarquia_flexible'
```

---

## 5. Stored Procedures (SP) en BD

Actualizados en migración `_000001` para soportar los nuevos campos:

| SP                           | Parámetros                                           | Uso                                           |
|------------------------------|------------------------------------------------------|-----------------------------------------------|
| `SP_OBTENER_JERARQUIAS`      | `p_tipo VARCHAR`, `p_modelo_estructura_id BIGINT`    | Listado con filtros opcionales                |
| `SP_BUSCAR_JERARQUIA`        | `p_id BIGINT`                                       | Buscar por ID                                 |
| `SP_CREAR_JERARQUIA`         | 9 parámetros (modelo_id, parent, nombre, tipo, categoria, nomenclatura, descripcion, orden, activo) | Insertar |
| `SP_ACTUALIZAR_JERARQUIA`    | 9 parámetros (id + los mismos)                      | Actualizar                                    |
| `SP_ELIMINAR_JERARQUIA`      | `p_id BIGINT`                                       | Eliminar por ID                               |
| `SP_OBTENER_ARBOL_JERARQUIA` | `p_root_id BIGINT`, `p_modelo_estructura_id BIGINT` | Árbol recursivo (preparado, no expuesto aún)  |

### Lógica de SP_OBTENER_JERARQUIAS

```
tipo=NULL  && modelo=NULL  → todos activos
tipo=NULL  && modelo=X     → filtrar solo por modelo
tipo=Y     && modelo=NULL  → filtrar solo por tipo
tipo=Y     && modelo=X     → filtrar por ambos  ← caso más común
```

---

## 6. Flujo completo del superusuario

```
1. Consultar modelos disponibles
   GET /api/estructura/modelos/activos
   → [{ id:1, tipo:'tradicional' }, { id:2, tipo:'jerarquia_flexible' }]

2. Crear proceso eligiendo modelo
   POST /api/estructura/procesos
   body: { ciclo_acreditacion_id: X, tipo_proceso: "autoevaluacion", modelo_estructura_id: 2 }

3a. Si eligió modelo 1 (tradicional) → trabaja con DIMENSION/COMPONENTE/CRITERIO
3b. Si eligió modelo 2 (jerarquia_flexible) → crea nodos en JERARQUIA:

   POST /api/estructura/jerarquia
   body: {
     "modelo_estructura_id": 2,
     "parent_id": null,
     "nombre": "Gestión Académica",
     "tipo": "dimension",
     "nomenclatura": "D1",
     "orden": 1
   }

   POST /api/estructura/jerarquia
   body: {
     "modelo_estructura_id": 2,
     "parent_id": 1,          ← hijo de "Gestión Académica"
     "nombre": "Planificación curricular",
     "tipo": "pauta",
     "categoria": "A",        ← categoría A = mayor importancia
     "nomenclatura": "P1.1",
     "orden": 1
   }
```

---

## 7. Permisos requeridos (roles)

| Acción                      | Permiso             |
|-----------------------------|---------------------|
| Ver jerarquías              | `jerarquia.view`    |
| Crear nodo jerarquía        | `jerarquia.create`  |
| Editar / activar nodo       | `jerarquia.edit`    |
| Eliminar nodo               | `jerarquia.delete`  |
| Toggle activo modelo        | `modelos.toggle`    |

---

## 8. Diagrama de relaciones

```
MODELO_ESTRUCTURA (predefinido, 2 registros)
  ├── ID=1 (tradicional)
  │     └── PROCESO.modelo_estructura_id = 1
  │           └── estructura: DIMENSION → COMPONENTE → CRITERIO
  │
  └── ID=2 (jerarquia_flexible)
        ├── PROCESO.modelo_estructura_id = 2
        └── JERARQUIA WHERE modelo_estructura_id = 2
              ├── Nodo raíz (parent_id=NULL)
              │     └── Hijo nivel 1
              │           └── Nieto nivel 2
              └── Otro nodo raíz
```

---

## 9. Notas importantes

- **No crear nuevos tipos** en `MODELO_ESTRUCTURA.tipo` sin implementar la lógica correspondiente en el sistema.
- **Campos `categoria` y `orden`** en JERARQUIA aunque no se use árbol visual:
  - `categoria` diferencia pautas por nivel de importancia (A > B > C > D)
  - `orden` controla el orden de listado entre hermanos
- **Desactivar en cascada**: al desactivar un nodo de JERARQUIA, todos sus hijos se desactivan automáticamente.
- **Eliminar con hijos**: devuelve error 422. Hay que eliminar hijos primero, de abajo hacia arriba.
- Los SPs son llamados desde `JerarquiaService` usando `DB::select('CALL SP_...')`.
