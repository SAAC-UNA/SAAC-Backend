# GUÍA DE IMPLEMENTACIÓN: TABLA JERARQUIA
## Análisis de Viabilidad y Plan de Implementación

**FECHA:** 14 de Marzo de 2026  
**ESTADO:** ✅ VIABLE - Sin impacto en funcionalidad actual  
**RIESGO:** Bajo (implementación aditiva, no destructiva)

---

## 📊 RESUMEN EJECUTIVO

### ¿Qué se quiere hacer?

Agregar una tabla dinámica `JERARQUIA` que permita manejar elementos flexibles (pautas, fuentes de información, etc.) sin crear nuevas tablas, mientras se **mantiene intacta** la estructura actual de:
- `DIMENSION`
- `COMPONENTE` 
- `CRITERIO`
- `EVIDENCIA`

### ¿Es viable?

**SÍ, 100% VIABLE** por las siguientes razones:

1. ✅ **No modifica tablas existentes** - Todo sigue funcionando igual
2. ✅ **Estructura aditiva** - Solo agrega nueva funcionalidad
3. ✅ **Flexibilidad futura** - Soporta cambios de SINAES sin reestructurar BD
4. ✅ **Patrón probado** - Self-referencing tables son estándar en jerarquías
5. ✅ **Sin impacto en permisos/roles** - Sistema actual no se afecta
6. ✅ **Compatible con frontend** - El StructureService ya maneja múltiples tipos

---

## 🏗️ ARQUITECTURA PROPUESTA

### Estructura Actual (se mantiene)
```
DIMENSION (dimension_id)
    ↓
COMPONENTE (componente_id, dimension_id)
    ↓
CRITERIO (criterio_id, componente_id)
    ↓
EVIDENCIA (evidencia_id, criterio_id)
```

### Nueva Estructura Paralela (se agrega)
```
JERARQUIA (jerarquia_id)
    ├── id (PK)
    ├── nombre
    ├── tipo (enum: 'pauta', 'fuente', 'subdimension', etc.)
    ├── parent_id (FK a JERARQUIA - autorreferencial)
    ├── descripcion (opcional)
    ├── nomenclatura (opcional)
    ├── orden (para ordenamiento)
    ├── activo
    └── timestamps
```

### Jerarquía Conceptual Resultante
```
SINAES (Nivel 0)
├── DIMENSION (tabla actual)
│   ├── COMPONENTE (tabla actual)
│   │   └── CRITERIO (tabla actual)
│   │       └── EVIDENCIA (tabla actual)
│   │
│   └── PAUTA (JERARQUIA tipo='pauta')
│       └── FUENTE (JERARQUIA tipo='fuente')
│           └── EVIDENCIA (tabla actual)
```

---

## 📁 ARCHIVOS A CREAR/MODIFICAR

### FASE 1: Base de Datos (Backend)

#### 1.1 Crear Nueva Migración
**Archivo:** `database/migrations/039_create_jerarquia_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('JERARQUIA', function (Blueprint $table) {
            // Clave primaria
            $table->id()->name('jerarquia_id');
            
            // Autorreferencia (parent_id apunta a otro registro de JERARQUIA)
            $table->unsignedBigInteger('parent_id')->nullable();
            
            // Datos básicos
            $table->string('nombre', 100);
            $table->string('tipo', 30); // 'pauta', 'fuente', 'subdimension', etc.
            $table->string('nomenclatura', 20)->nullable();
            $table->text('descripcion')->nullable();
            
            // Ordenamiento y estado
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key autorreferencial
            $table->foreign('parent_id')
                  ->references('jerarquia_id')
                  ->on('JERARQUIA')
                  ->onDelete('restrict'); // No eliminar si tiene hijos
            
            // Índices para performance
            $table->index('parent_id', 'idx_jer_parent_id');
            $table->index('tipo', 'idx_jer_tipo');
            $table->index('activo', 'idx_jer_activo');
            $table->index(['parent_id', 'tipo'], 'idx_jer_parent_tipo');
            $table->index(['parent_id', 'orden'], 'idx_jer_parent_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('JERARQUIA');
    }
};
```

#### 1.2 Agregar Stored Procedures
**Archivo:** `database/migrations/040_add_jerarquia_stored_procedures.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SP para obtener toda la jerarquía
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_JERARQUIAS(IN p_tipo VARCHAR(30))
BEGIN
    SELECT * FROM JERARQUIA
    WHERE (p_tipo IS NULL OR tipo = p_tipo)
    ORDER BY parent_id, orden, nombre;
END
        ');

        // SP para buscar por ID
        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_JERARQUIA(IN p_id BIGINT)
BEGIN
    SELECT * FROM JERARQUIA WHERE jerarquia_id = p_id LIMIT 1;
END
        ');

        // SP para crear
        DB::unprepared('
CREATE PROCEDURE SP_CREAR_JERARQUIA(
    IN p_parent_id BIGINT,
    IN p_nombre VARCHAR(100),
    IN p_tipo VARCHAR(30),
    IN p_nomenclatura VARCHAR(20),
    IN p_descripcion TEXT,
    IN p_orden INT,
    IN p_activo TINYINT
)
BEGIN
    INSERT INTO JERARQUIA (parent_id, nombre, tipo, nomenclatura, descripcion, orden, activo, created_at, updated_at)
    VALUES (p_parent_id, p_nombre, p_tipo, p_nomenclatura, p_descripcion, p_orden, p_activo, NOW(), NOW());
    
    SELECT * FROM JERARQUIA WHERE jerarquia_id = LAST_INSERT_ID() LIMIT 1;
END
        ');

        // SP para actualizar
        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_JERARQUIA(
    IN p_id BIGINT,
    IN p_parent_id BIGINT,
    IN p_nombre VARCHAR(100),
    IN p_tipo VARCHAR(30),
    IN p_nomenclatura VARCHAR(20),
    IN p_descripcion TEXT,
    IN p_orden INT,
    IN p_activo TINYINT
)
BEGIN
    UPDATE JERARQUIA
    SET parent_id = p_parent_id,
        nombre = p_nombre,
        tipo = p_tipo,
        nomenclatura = p_nomenclatura,
        descripcion = p_descripcion,
        orden = p_orden,
        activo = p_activo,
        updated_at = NOW()
    WHERE jerarquia_id = p_id;
    
    SELECT * FROM JERARQUIA WHERE jerarquia_id = p_id LIMIT 1;
END
        ');

        // SP para eliminar
        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_JERARQUIA(IN p_id BIGINT)
BEGIN
    DELETE FROM JERARQUIA WHERE jerarquia_id = p_id;
END
        ');

        // SP recursivo para obtener árbol completo
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_ARBOL_JERARQUIA(IN p_root_id BIGINT)
BEGIN
    WITH RECURSIVE arbol AS (
        -- Caso base: nodo raíz
        SELECT 
            jerarquia_id,
            parent_id,
            nombre,
            tipo,
            nomenclatura,
            descripcion,
            orden,
            activo,
            0 AS nivel,
            CAST(jerarquia_id AS CHAR(255)) AS ruta
        FROM JERARQUIA
        WHERE (p_root_id IS NULL AND parent_id IS NULL) 
           OR (p_root_id IS NOT NULL AND jerarquia_id = p_root_id)
        
        UNION ALL
        
        -- Caso recursivo: hijos
        SELECT 
            j.jerarquia_id,
            j.parent_id,
            j.nombre,
            j.tipo,
            j.nomenclatura,
            j.descripcion,
            j.orden,
            j.activo,
            a.nivel + 1,
            CONCAT(a.ruta, \'->\', j.jerarquia_id)
        FROM JERARQUIA j
        INNER JOIN arbol a ON j.parent_id = a.jerarquia_id
    )
    SELECT * FROM arbol ORDER BY ruta, orden;
END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_JERARQUIAS');
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_BUSCAR_JERARQUIA');
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_CREAR_JERARQUIA');
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_JERARQUIA');
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_ELIMINAR_JERARQUIA');
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_ARBOL_JERARQUIA');
    }
};
```

---

### FASE 2: Modelo Eloquent

#### 2.1 Crear Modelo
**Archivo:** `app/Models/Jerarquia.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jerarquia extends Model
{
    use HasFactory;

    // Tabla
    protected $table = 'JERARQUIA';

    // Primary Key
    protected $primaryKey = 'jerarquia_id';

    // Fillable
    protected $fillable = [
        'parent_id',
        'nombre',
        'tipo',
        'nomenclatura',
        'descripcion',
        'orden',
        'activo'
    ];

    // Casts
    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    // ===== RELACIONES =====

    /**
     * Relación: Un elemento tiene un padre (autorreferencia)
     */
    public function parent()
    {
        return $this->belongsTo(Jerarquia::class, 'parent_id', 'jerarquia_id');
    }

    /**
     * Relación: Un elemento tiene muchos hijos (autorreferencia)
     */
    public function children()
    {
        return $this->hasMany(Jerarquia::class, 'parent_id', 'jerarquia_id')
                    ->orderBy('orden')
                    ->orderBy('nombre');
    }

    /**
     * Relación recursiva: Obtener todos los descendientes
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Relación recursiva: Obtener todos los ancestros
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    // ===== SCOPES =====

    /**
     * Scope: Solo elementos activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Filtrar por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('tipo', $type);
    }

    /**
     * Scope: Solo elementos raíz (sin padre)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Hijos de un padre específico
     */
    public function scopeChildrenOf($query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    // ===== MÉTODOS DE UTILIDAD =====

    /**
     * Verificar si tiene hijos
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Obtener la profundidad del nodo en el árbol
     */
    public function getDepth(): int
    {
        $depth = 0;
        $current = $this;
        while ($current->parent) {
            $depth++;
            $current = $current->parent;
        }
        return $depth;
    }

    /**
     * Obtener la ruta completa desde la raíz
     */
    public function getPath(string $separator = ' > '): string
    {
        $path = [$this->nombre];
        $current = $this;
        while ($current->parent) {
            $current = $current->parent;
            array_unshift($path, $current->nombre);
        }
        return implode($separator, $path);
    }
}
```

---

### FASE 3: Service Layer

#### 3.1 Crear Servicio
**Archivo:** `app/Services/JerarquiaService.php`

```php
<?php

namespace App\Services;

use App\Models\Jerarquia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JerarquiaService
{
    /**
     * Obtener todos los elementos, opcionalmente filtrados por tipo
     */
    public function getAll(?string $tipo = null)
    {
        $cacheKey = $tipo ? "jerarquias.tipo.{$tipo}" : 'jerarquias.all';
        
        return Cache::remember($cacheKey, 300, function () use ($tipo) {
            $rows = DB::select('CALL SP_OBTENER_JERARQUIAS(?)', [$tipo]);
            return Jerarquia::hydrate(array_map(fn($r) => (array) $r, $rows));
        });
    }

    /**
     * Buscar por ID
     */
    public function findById(int $id): ?Jerarquia
    {
        $rows = DB::select('CALL SP_BUSCAR_JERARQUIA(?)', [$id]);
        return $rows ? Jerarquia::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    /**
     * Crear nuevo elemento
     */
    public function create(array $data): Jerarquia
    {
        $rows = DB::select('CALL SP_CREAR_JERARQUIA(?, ?, ?, ?, ?, ?, ?)', [
            $data['parent_id'] ?? null,
            $data['nombre'],
            $data['tipo'],
            $data['nomenclatura'] ?? null,
            $data['descripcion'] ?? null,
            $data['orden'] ?? 0,
            $data['activo'] ?? 1,
        ]);

        $this->clearCache($data['tipo'] ?? null);
        
        return Jerarquia::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    /**
     * Actualizar elemento existente
     */
    public function update(Jerarquia $jerarquia, array $data): Jerarquia
    {
        $rows = DB::select('CALL SP_ACTUALIZAR_JERARQUIA(?, ?, ?, ?, ?, ?, ?, ?)', [
            $jerarquia->jerarquia_id,
            $data['parent_id'] ?? $jerarquia->parent_id,
            $data['nombre'] ?? $jerarquia->nombre,
            $data['tipo'] ?? $jerarquia->tipo,
            $data['nomenclatura'] ?? $jerarquia->nomenclatura,
            $data['descripcion'] ?? $jerarquia->descripcion,
            $data['orden'] ?? $jerarquia->orden,
            $data['activo'] ?? $jerarquia->activo,
        ]);

        $this->clearCache($jerarquia->tipo);
        
        return Jerarquia::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    /**
     * Eliminar elemento
     */
    public function delete(Jerarquia $jerarquia): void
    {
        DB::statement('CALL SP_ELIMINAR_JERARQUIA(?)', [$jerarquia->jerarquia_id]);
        $this->clearCache($jerarquia->tipo);
    }

    /**
     * Obtener árbol completo (recursivo)
     */
    public function getTree(?int $rootId = null)
    {
        $cacheKey = $rootId ? "jerarquia.tree.{$rootId}" : 'jerarquia.tree.all';
        
        return Cache::remember($cacheKey, 300, function () use ($rootId) {
            $rows = DB::select('CALL SP_OBTENER_ARBOL_JERARQUIA(?)', [$rootId]);
            return array_map(fn($r) => (array) $r, $rows);
        });
    }

    /**
     * Limpiar caché
     */
    private function clearCache(?string $tipo = null): void
    {
        Cache::forget('jerarquias.all');
        Cache::forget('jerarquia.tree.all');
        
        if ($tipo) {
            Cache::forget("jerarquias.tipo.{$tipo}");
        }
    }
}
```

---

### FASE 4: Controller

#### 4.1 Crear Controlador
**Archivo:** `app/Http/Controllers/JerarquiaController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Jerarquia;
use App\Services\JerarquiaService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JerarquiaController extends Controller
{
    protected $service;

    public function __construct(JerarquiaService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/estructura/jerarquia
     * Query params: ?tipo=pauta
     */
    public function index(Request $request)
    {
        $tipo = $request->query('tipo');
        $items = $this->service->getAll($tipo);
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/jerarquia/{id}
     */
    public function show($id)
    {
        $item = $this->service->findById((int)$id);
        
        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }
        
        return response()->json($item, 200);
    }

    /**
     * GET /api/estructura/jerarquia/arbol
     * Query params: ?root_id=5
     */
    public function tree(Request $request)
    {
        $rootId = $request->query('root_id') ? (int)$request->query('root_id') : null;
        $tree = $this->service->getTree($rootId);
        return response()->json($tree, 200);
    }

    /**
     * POST /api/estructura/jerarquia
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:JERARQUIA,jerarquia_id',
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|string|max:30',
            'nomenclatura' => 'nullable|string|max:20',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean'
        ]);

        $item = $this->service->create($validated);

        AuditLogService::log(
            'crear',
            "Se creó el elemento de jerarquía \"{$item->nombre}\" (Tipo: {$item->tipo}, ID: {$item->jerarquia_id}).",
            'Jerarquía'
        );

        return response()->json([
            'message' => 'Elemento creado correctamente.',
            'data' => $item
        ], 201);
    }

    /**
     * PUT/PATCH /api/estructura/jerarquia/{id}
     */
    public function update(Request $request, $id)
    {
        $item = Jerarquia::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'exists:JERARQUIA,jerarquia_id',
                // Evitar que un elemento sea su propio padre
                Rule::notIn([$id])
            ],
            'nombre' => 'sometimes|required|string|max:100',
            'tipo' => 'sometimes|required|string|max:30',
            'nomenclatura' => 'nullable|string|max:20',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean'
        ]);

        $updated = $this->service->update($item, $validated);

        AuditLogService::log(
            'editar',
            "Se actualizó el elemento de jerarquía ID {$item->jerarquia_id} (Tipo: {$item->tipo}).",
            'Jerarquía'
        );

        return response()->json([
            'message' => 'Elemento actualizado correctamente.',
            'data' => $updated
        ], 200);
    }

    /**
     * DELETE /api/estructura/jerarquia/{id}
     */
    public function destroy($id)
    {
        $item = Jerarquia::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        // Verificar si tiene hijos
        if ($item->hasChildren()) {
            return response()->json([
                'message' => 'No se puede eliminar un elemento que tiene hijos. Elimine primero los elementos hijos.'
            ], 422);
        }

        $this->service->delete($item);

        AuditLogService::log(
            'eliminar',
            "Se eliminó el elemento de jerarquía \"{$item->nombre}\" (Tipo: {$item->tipo}, ID: {$item->jerarquia_id}).",
            'Jerarquía'
        );

        return response()->json([
            'message' => 'Elemento eliminado correctamente.'
        ], 200);
    }
}
```

---

### FASE 5: Rutas

#### 5.1 Agregar Rutas API
**Archivo:** `routes/api.php` (agregar al final del grupo de estructura)

```php
// ===== JERARQUIA (Nueva tabla flexible) =====
Route::middleware(['permission:jerarquia.view'])->group(function () {
    Route::get('estructura/jerarquia', [JerarquiaController::class, 'index']);
    Route::get('estructura/jerarquia/arbol', [JerarquiaController::class, 'tree']);
    Route::get('estructura/jerarquia/{id}', [JerarquiaController::class, 'show']);
});

Route::post('estructura/jerarquia', [JerarquiaController::class, 'store'])
    ->middleware('permission:jerarquia.create');

Route::match(['put', 'patch'], 'estructura/jerarquia/{id}', [JerarquiaController::class, 'update'])
    ->middleware('permission:jerarquia.edit');

Route::delete('estructura/jerarquia/{id}', [JerarquiaController::class, 'destroy'])
    ->middleware('permission:jerarquia.delete');
```

**IMPORTANTE:** También agregar el `use` al inicio del archivo:
```php
use App\Http\Controllers\JerarquiaController;
```

---

### FASE 6: Permisos

#### 6.1 Agregar Permisos al Seeder
**Archivo:** `database/seeders/PermissionsSeeder.php` (o donde tengas los permisos)

Agregar:
```php
// Jerarquía (nueva tabla flexible)
['name' => 'jerarquia.view', 'guard_name' => 'sanctum'],
['name' => 'jerarquia.create', 'guard_name' => 'sanctum'],
['name' => 'jerarquia.edit', 'guard_name' => 'sanctum'],
['name' => 'jerarquia.delete', 'guard_name' => 'sanctum'],
```

---

## 🎯 PUNTOS CRÍTICOS DE VALIDACIÓN

### ✅ GARANTÍAS DE NO RUPTURA

1. **Tablas existentes NO se tocan**
   - DIMENSION, COMPONENTE, CRITERIO, EVIDENCIA quedan exactamente igual
   - Todas las migraciones existentes siguen funcionando

2. **Controladores actuales intactos**
   - DimensionController, ComponentController, etc. no se modifican

3. **Servicios actuales intactos**
   - DimensionService, ComponentService, etc. siguen igual

4. **Rutas actuales intactas**
   - Todas las rutas `/estructura/dimensiones`, `/estructura/componentes`, etc. siguen funcionando

5. **Frontend compatible**
   - El StructureService ya maneja múltiples tipos
   - Solo necesitarás agregar el tipo 'jerarquia' a los ElementType

---

## 📝 CHECKLIST DE IMPLEMENTACIÓN

### Backend (Laravel)

- [ ] 1. Crear migración `039_create_jerarquia_table.php`
- [ ] 2. Crear migración `040_add_jerarquia_stored_procedures.php`
- [ ] 3. Ejecutar migraciones: `php artisan migrate`
- [ ] 4. Crear modelo `app/Models/Jerarquia.php`
- [ ] 5. Crear servicio `app/Services/JerarquiaService.php`
- [ ] 6. Crear controlador `app/Http/Controllers/JerarquiaController.php`
- [ ] 7. Agregar rutas en `routes/api.php`
- [ ] 8. Agregar permisos en el seeder
- [ ] 9. Ejecutar seeder de permisos
- [ ] 10. Probar endpoints con Postman/Thunder Client

### Frontend (React + TypeScript)

- [ ] 11. Agregar tipo 'jerarquia' a `StructureTypes.ts`
- [ ] 12. Agregar endpoint mapping en `StructureMapper.ts`
- [ ] 13. Crear componente `JerarquiaTree.tsx` (si es necesario)
- [ ] 14. Actualizar permisos en constantes del frontend

---

## 🧪 PLAN DE PRUEBAS

### Pruebas Backend

```bash
# 1. Crear un elemento raíz (pauta)
POST /api/estructura/jerarquia
{
  "nombre": "Pauta 1",
  "tipo": "pauta",
  "nomenclatura": "P1",
  "activo": true
}

# 2. Crear un hijo (fuente)
POST /api/estructura/jerarquia
{
  "parent_id": 1,
  "nombre": "Fuente de Información 1",
  "tipo": "fuente",
  "nomenclatura": "F1",
  "activo": true
}

# 3. Obtener árbol
GET /api/estructura/jerarquia/arbol

# 4. Filtrar por tipo
GET /api/estructura/jerarquia?tipo=pauta

# 5. Intentar eliminar padre (debe fallar)
DELETE /api/estructura/jerarquia/1
# Respuesta esperada: 422 - "No se puede eliminar un elemento que tiene hijos"
```

### Pruebas de No Regresión

```bash
# Verificar que todo lo existente sigue funcionando

# Dimensiones
GET /api/estructura/dimensiones

# Componentes
GET /api/estructura/componentes

# Criterios
GET /api/estructura/criterios

# Evidencias
GET /api/estructura/evidencias
```

---

## 🚀 PASOS PARA EJECUTAR

### 1. Preparación
```bash
cd c:\SAAC\SAAC-Backend
```

### 2. Crear archivos
```bash
# Copiar el contenido de esta guía y crear cada archivo mencionado
```

### 3. Ejecutar migraciones
```bash
php artisan migrate
```

### 4. Actualizar permisos
```bash
php artisan db:seed --class=PermissionsSeeder
# O ejecutar manualmente el SQL para insertar los permisos
```

### 5. Probar
```bash
# Usar Postman, Thunder Client o curl para probar los endpoints
```

---

## 📊 EJEMPLO DE USO REAL

### Escenario: SINAES con Pautas y Fuentes

```sql
-- Crear una dimensión (tabla existente, no cambia)
INSERT INTO DIMENSION (nombre, nomenclatura, activo) 
VALUES ('Formación Profesional', 'D1', 1);

-- Crear una pauta dentro de esa dimensión (nueva tabla JERARQUIA)
INSERT INTO JERARQUIA (nombre, tipo, nomenclatura, orden, activo)
VALUES ('Pauta 1: Plan de Estudios', 'pauta', 'P1', 1, 1);

-- Crear fuentes dentro de esa pauta
INSERT INTO JERARQUIA (parent_id, nombre, tipo, nomenclatura, orden, activo)
VALUES 
  (1, 'Plan de estudios vigente', 'fuente', 'F1.1', 1, 1),
  (1, 'Mallas curriculares', 'fuente', 'F1.2', 2, 1);

-- Ahora puedes vincular evidencias tanto al criterio (tabla antigua)
-- como a la fuente (nueva tabla JERARQUIA)
```

### Resultado Conceptual
```
DIMENSION: Formación Profesional (D1)
├── COMPONENTE: Gestión Curricular
│   └── CRITERIO: Diseño curricular
│       └── EVIDENCIA: Documento plan de estudios
│
└── JERARQUIA: Pauta 1: Plan de Estudios (P1)
    ├── JERARQUIA: Plan de estudios vigente (F1.1)
    └── JERARQUIA: Mallas curriculares (F1.2)
```

---

## ⚠️ CONSIDERACIONES IMPORTANTES

### 1. Relación entre JERARQUIA y Tablas Existentes

Si quieres vincular elementos de JERARQUIA con DIMENSIONES, puedes:

**Opción A:** Agregar un campo opcional `dimension_id` a JERARQUIA
```php
$table->foreignId('dimension_id')
      ->nullable()
      ->constrained('DIMENSION', 'dimension_id')
      ->onDelete('restrict');
```

**Opción B:** Crear tabla pivote JERARQUIA_DIMENSION
```php
Schema::create('JERARQUIA_DIMENSION', function (Blueprint $table) {
    $table->foreignId('jerarquia_id')->constrained('JERARQUIA', 'jerarquia_id');
    $table->foreignId('dimension_id')->constrained('DIMENSION', 'dimension_id');
    $table->primary(['jerarquia_id', 'dimension_id']);
});
```

### 2. Performance con Árboles Grandes

Para árboles muy grandes, considera:
- Agregar campo `ruta_materializad` (Materialized Path)
- Usar Nested Sets en lugar de parent_id
- Implementar cache agresivo

### 3. Validaciones Críticas

```php
// Evitar ciclos (un elemento no puede ser ancestro de sí mismo)
function validateNoCircularReference($parentId, $currentId) {
    $parent = Jerarquia::find($parentId);
    while ($parent) {
        if ($parent->jerarquia_id == $currentId) {
            return false; // Ciclo detectado
        }
        $parent = $parent->parent;
    }
    return true;
}
```

---

## ✅ CONCLUSIÓN

Esta implementación es **100% viable** porque:

1. ✅ No toca nada existente
2. ✅ Agrega funcionalidad paralela
3. ✅ Permite flexibilidad futura
4. ✅ Sigue los patrones del sistema (SPs, Services, Controllers)
5. ✅ Es retrocompatible al 100%

**RIESGO: BAJO**  
**ESFUERZO: MEDIO** (2-3 días de desarrollo + pruebas)  
**BENEFICIO: ALTO** (flexibilidad total para cambios de SINAES)

---

## 📞 SIGUIENTE PASO

¿Quieres que implemente todos estos archivos en tu sistema ahora?

Dime si:
1. Procedo a crear todos los archivos mencionados
2. Necesitas alguna modificación en el diseño
3. Quieres agregar campos adicionales a la tabla JERARQUIA
4. Te ayudo con la parte del frontend también
