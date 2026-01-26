# 📊 ANÁLISIS HU-012: Filtrado Avanzado de Evidencias

**Fecha**: 2026-01-09  
**Rama**: HU-012-Filtrado-avanzado-de-criterios-evidencias-y-componentes  
**Objetivo**: Implementar sistema de filtrado avanzado con múltiples criterios y exportación

---

## 1. ANÁLISIS DEL MODELO DE DATOS

### 1.1 Modelo Evidence (EVIDENCIA)

**Ubicación**: `app/Models/Evidence.php`

**Tabla**: `EVIDENCIA`

**Clave Primaria**: `evidencia_id`

**Campos Disponibles**:
```php
- evidencia_id (PK)
- criterio_id (FK → CRITERIO)
- estado_evidencia_id (FK → ESTADO_EVIDENCIA)
- descripcion (string)
- nomenclatura (string) - usado para ordenamiento
- activo (boolean)
- created_at (timestamp) - fecha de publicación
- updated_at (timestamp)
```

**Relaciones Existentes**:
1. `criterion()` - BelongsTo → Un criterio
2. `evidenceState()` - BelongsTo → Un estado
3. `assignments()` - HasMany → Muchas asignaciones
4. `activeAssignments()` - HasMany → Asignaciones activas solamente

**Herencia**: Extiende de `BaseCareer` - permite filtrado por carreras del usuario

---

### 1.2 Modelo EvidenceAssignment (EVIDENCIA_ASIGNACION)

**Ubicación**: `app/Models/EvidenceAssignment.php`

**Tabla**: `EVIDENCIA_ASIGNACION`

**Clave Primaria**: `evidencia_asignacion_id`

**Campos Relevantes**:
```php
- evidencia_asignacion_id (PK)
- proceso_id (FK → PROCESO)
- evidencia_id (FK → EVIDENCIA)
- usuario_id (FK → USUARIO) ← RESPONSABLE
- estado (enum: pendiente|en_progreso|completado|vencido)
- fecha_asignacion (datetime)
- fecha_limite (datetime)
- comentario (text, nullable)
```

**Relaciones**:
1. `process()` - BelongsTo → Proceso
2. `evidence()` - BelongsTo → Evidencia
3. `user()` - BelongsTo → Usuario (responsable)

⚠️ **IMPORTANTE**: NO tiene relación directa con `Role`, pero el usuario tiene roles mediante Spatie

---

### 1.3 Modelo Criterion (CRITERIO)

**Ubicación**: `app/Models/Criterion.php`

**Campos**:
```php
- criterio_id (PK)
- componente_id (FK)
- descripcion (string)
- nomenclatura (string)
- activo (boolean)
```

**Relaciones**:
- `evidences()` - HasMany → Muchas evidencias

---

### 1.4 Modelo EvidenceState (ESTADO_EVIDENCIA)

**Ubicación**: `app/Models/EvidenceState.php`

**Campos**:
```php
- estado_evidencia_id (PK)
- nombre (string) - ej: "Borrador", "Publicada", "Aprobada", "Rechazada"
```

**Relaciones**:
- `evidences()` - HasMany → Muchas evidencias

---

### 1.5 Modelo User (USUARIO)

**Ubicación**: `app/Models/User.php`

**Campos Relevantes**:
```php
- usuario_id (PK)
- cedula (string)
- nombre (string)
- email (string)
- status (enum: active|inactive)
```

**Traits**:
- `HasRoles` (Spatie) - Para gestión de roles

**Relaciones**:
- `careers()` - BelongsToMany → Carreras asignadas

**Constantes de Estado**:
```php
STATUS_ACTIVE = 'active'
STATUS_INACTIVE = 'inactive'
```

---

## 2. ANÁLISIS DE ENDPOINTS EXISTENTES

### 2.1 Rutas Actuales (routes/api.php)

```php
// CRUD básico de evidencias
Route::apiResource('estructura/evidencias', EvidenceController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);

// Activar/desactivar evidencia
Route::patch('estructura/evidencias/{id}/active', [EvidenceController::class, 'setActive']);

// Asignaciones de evidencias
Route::post('evidencias-asignaciones/validar-duplicados', ...);
Route::apiResource('evidencias-asignaciones', EvidenceAssignmentController::class);
Route::get('usuarios/{usuarioId}/evidencias-asignadas', ...);
Route::get('evidencias/{evidenciaId}/asignaciones', ...);
```

**Observación**: No existe endpoint de filtrado avanzado

---

### 2.2 Controller Actual (EvidenceController)

**Ubicación**: `app/Http/Controllers/EvidenceController.php`

**Métodos Existentes**:

1. **`index()`** - GET `/api/estructura/evidencias`
   - Retorna TODAS las evidencias ordenadas por nomenclatura
   - Sin filtros
   - Sin paginación
   - Sin restricciones por rol

2. **`show($id)`** - GET `/api/estructura/evidencias/{id}`
   - Retorna una evidencia por ID

3. **`store()`** - POST `/api/estructura/evidencias`
   - Crea nueva evidencia
   - Registra en bitácora

4. **`update()`** - PUT/PATCH `/api/estructura/evidencias/{id}`
   - Actualiza evidencia
   - Registra en bitácora

5. **`destroy($id)`** - DELETE `/api/estructura/evidencias/{id}`
   - Elimina evidencia
   - Registra en bitácora

6. **`setActive()`** - PATCH `/api/estructura/evidencias/{id}/active`
   - Activa/desactiva evidencia

**Problema Identificado**: El método `index()` actual no aplica filtros ni restricciones por rol

---

### 2.3 Service Actual (EvidenceService)

**Ubicación**: `app/Services/EvidenceService.php`

**Métodos Existentes**:

```php
getAll()          // Retorna todas las evidencias sin filtros
findById(int $id) // Busca por ID
create(array $data)
update(Evidence $evidence, array $data)
delete(Evidence $evidence)
```

**Problema**: 
- No hay método de filtrado
- No usa `with()` para cargar relaciones (N+1 queries)
- No implementa restricciones por rol

---

### 2.4 Resource Actual (EvidenceResource)

**Ubicación**: `app/Http/Resources/EvidenceResource.php`

**Campos que Retorna**:
```php
- evidencia_id
- criterio_id
- estado_evidencia_id
- descripcion
- nomenclatura
- activo
- created_at (ISO 8601)
- updated_at (ISO 8601)
- criterion (cuando está cargado)
```

**Problema**: 
- NO incluye `evidenceState` (nombre del estado)
- NO incluye `responsables` (usuarios asignados)
- NO incluye información de asignaciones

---

## 3. ANÁLISIS DE FILTROS REQUERIDOS

Según la HU-012, necesitamos filtrar por:

### 3.1 Filtro por Criterio
**Campo**: `criterio_id`  
**Tipo**: Integer (FK)  
**Implementación**: WHERE directo en tabla EVIDENCIA  
**Complejidad**: ⭐ Baja

### 3.2 Filtro por Responsable
**Campo**: `usuario_id` en tabla EVIDENCIA_ASIGNACION  
**Tipo**: Integer (FK)  
**Implementación**: JOIN con EVIDENCIA_ASIGNACION  
**Complejidad**: ⭐⭐ Media

```sql
SELECT DISTINCT e.*
FROM EVIDENCIA e
INNER JOIN EVIDENCIA_ASIGNACION ea ON e.evidencia_id = ea.evidencia_id
WHERE ea.usuario_id = ?
```

### 3.3 Filtro por Fecha de Publicación
**Campo**: `created_at` en tabla EVIDENCIA  
**Tipo**: Timestamp (rango)  
**Implementación**: WHERE con BETWEEN  
**Complejidad**: ⭐ Baja

```sql
WHERE e.created_at BETWEEN ? AND ?
```

### 3.4 Filtro por Estado
**Campo**: `estado_evidencia_id`  
**Tipo**: Integer (FK)  
**Implementación**: WHERE directo  
**Complejidad**: ⭐ Baja

### 3.5 Filtro por Rol
**Campo**: Roles del usuario en tabla `model_has_roles` (Spatie)  
**Tipo**: Integer (FK)  
**Implementación**: JOIN complejo  
**Complejidad**: ⭐⭐⭐ Alta

```sql
SELECT DISTINCT e.*
FROM EVIDENCIA e
INNER JOIN EVIDENCIA_ASIGNACION ea ON e.evidencia_id = ea.evidencia_id
INNER JOIN USUARIO u ON ea.usuario_id = u.usuario_id
INNER JOIN model_has_roles mhr ON u.usuario_id = mhr.model_id
WHERE mhr.role_id = ? AND mhr.model_type = 'App\\Models\\User'
```

---

## 4. RESTRICCIONES POR ROL DEL USUARIO

### 4.1 SuperUsuario
**Permisos**: Ve TODAS las evidencias del sistema  
**Restricción**: Ninguna  
**Implementación**: Sin WHERE adicional

### 4.2 Administrador / Coordinador
**Permisos**: Ve evidencias de SUS carreras asignadas  
**Restricción**: Por `careers()` del usuario  
**Implementación**: 
```php
// BaseCareer ya implementa esto
$query->whereHas('criterion.component.dimension.comment.careers', function($q) use ($user) {
    $q->whereIn('carrera_id', $user->careers->pluck('carrera_id'));
});
```

### 4.3 Evaluador / Profesor
**Permisos**: Ve solo evidencias ASIGNADAS a él  
**Restricción**: Por `evidencia_asignacion.usuario_id`  
**Implementación**:
```php
$query->whereHas('assignments', function($q) use ($user) {
    $q->where('usuario_id', $user->usuario_id);
});
```

---

## 5. ORDENAMIENTO Y PAGINACIÓN

### 5.1 Ordenamiento
**Campos Permitidos**:
- `created_at` (fecha) - por defecto descendente
- `nomenclatura` - alfanumérico
- `descripcion` - alfabético
- `estado_evidencia_id` - por estado

**Dirección**:
- `asc` - Ascendente
- `desc` - Descendente (por defecto)

### 5.2 Paginación
**Parámetro**: `per_page`  
**Rango**: 5 - 100 resultados  
**Por defecto**: 15 resultados  
**Metadata**:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 67
  }
}
```

---

## 6. PROBLEMA DEL N+1 QUERIES

### 6.1 Problema Actual
El `EvidenceService::getAll()` no carga relaciones:
```php
Evidence::orderBy('nomenclatura')->get(); // N+1 queries
```

Si tenemos 100 evidencias y queremos mostrar criterio + estado:
- 1 query para evidencias
- 100 queries para criterios
- 100 queries para estados
**Total: 201 queries** 🔴

### 6.2 Solución con Eager Loading
```php
Evidence::with(['criterion', 'evidenceState', 'assignments.user'])
    ->orderBy('nomenclatura')
    ->paginate(15);
```
**Total: 4 queries** ✅

---

## 7. CAMPOS FALTANTES EN EvidenceResource

Actualmente NO incluye:
1. ❌ `estado_evidencia` (nombre del estado)
2. ❌ `responsables` (array de usuarios asignados)
3. ❌ `fecha_publicacion` (alias de created_at más legible)
4. ❌ `count_asignaciones` (número de asignaciones)

**Solución**: Expandir el Resource

---

## 8. EXPORTACIÓN PDF Y EXCEL

### 8.1 Laravel Excel (Maatwebsite)
**Ventajas**:
- Popular y mantenido
- Fácil implementación
- Soporte para estilos
- Exportación rápida

**Instalación**:
```bash
composer require maatwebsite/excel
```

### 8.2 DomPDF
**Ventajas**:
- Genera PDFs desde HTML/Blade
- Compatible con Laravel
- No requiere extensiones PHP

**Instalación**:
```bash
composer require barryvdh/laravel-dompdf
```

---

## 9. RESUMEN DE CAMBIOS NECESARIOS

### ✅ Crear Nuevos
1. `FilterEvidenceRequest` - Validación de filtros
2. `EvidenceExportService` - Lógica de exportación
3. `EvidencesExport` class - Excel export
4. Vista Blade `evidences-pdf.blade.php`
5. Tests unitarios y de feature

### ✏️ Modificar Existentes
1. **EvidenceService**:
   - Agregar `filterEvidences(array $filters, User $user)`
   - Implementar eager loading
   - Implementar restricciones por rol

2. **EvidenceController**:
   - Agregar `filter(FilterEvidenceRequest $request)`
   - Agregar `exportExcel(FilterEvidenceRequest $request)`
   - Agregar `exportPDF(FilterEvidenceRequest $request)`

3. **EvidenceResource**:
   - Agregar campo `estado_evidencia`
   - Agregar campo `responsables`
   - Agregar campo `fecha_publicacion`

4. **routes/api.php**:
   - POST `/api/estructura/evidencias/filter`
   - POST `/api/estructura/evidencias/export/excel`
   - POST `/api/estructura/evidencias/export/pdf`

---

## 10. QUERY COMPLETA FINAL (Ejemplo)

```php
$query = Evidence::query()
    ->with([
        'criterion:criterio_id,nomenclatura,descripcion',
        'evidenceState:estado_evidencia_id,nombre',
        'assignments' => function($q) {
            $q->with('user:usuario_id,nombre,email')
              ->where('activo', true);
        }
    ])
    ->when($filters['criterio_id'], fn($q, $val) => 
        $q->where('criterio_id', $val)
    )
    ->when($filters['estado_evidencia_id'], fn($q, $val) => 
        $q->where('estado_evidencia_id', $val)
    )
    ->when($filters['fecha_desde'] && $filters['fecha_hasta'], fn($q) => 
        $q->whereBetween('created_at', [$filters['fecha_desde'], $filters['fecha_hasta']])
    )
    ->when($filters['responsable_id'], fn($q, $val) => 
        $q->whereHas('assignments', fn($sq) => 
            $sq->where('usuario_id', $val)
        )
    )
    ->when($filters['rol_id'], fn($q, $val) => 
        $q->whereHas('assignments.user.roles', fn($sq) => 
            $sq->where('id', $val)
        )
    );

// Aplicar restricción por rol del usuario autenticado
if (!$user->hasRole('SuperUsuario')) {
    if ($user->hasRole(['Administrador', 'Coordinador'])) {
        $query->whereHas('criterion.component.dimension.comment.careers', fn($q) => 
            $q->whereIn('carrera_id', $user->careers->pluck('carrera_id'))
        );
    } else {
        $query->whereHas('assignments', fn($q) => 
            $q->where('usuario_id', $user->usuario_id)
        );
    }
}

// Ordenamiento
$query->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc');

// Paginación
return $query->paginate($filters['per_page'] ?? 15);
```

---

## 11. COMPLEJIDAD Y ESTIMACIÓN

### Complejidad Técnica
- **Filtrado básico**: Baja ⭐
- **Filtrado por responsable/rol**: Media ⭐⭐
- **Restricciones por rol**: Media ⭐⭐
- **Exportación PDF/Excel**: Media ⭐⭐
- **Testing completo**: Media ⭐⭐

**Total**: Media-Alta ⭐⭐⭐

### Estimación de Tiempo
- Análisis: ✅ 1 hora (completado)
- Implementación backend: 4-6 horas
- Tests: 2-3 horas
- Exportación: 2-3 horas
- Documentación: 1 hora

**Total estimado**: 10-14 horas

---

## 12. RIESGOS IDENTIFICADOS

### 🔴 Alto Riesgo
1. **Performance con datasets grandes**: Si hay >10,000 evidencias, los JOINs múltiples pueden ser lentos
   - **Mitigación**: Agregar índices en BD, usar query optimization

2. **Memoria en exportación Excel**: Exportar >5,000 registros puede causar timeout
   - **Mitigación**: Usar chunk() para procesar en lotes, implementar queue jobs

### 🟡 Medio Riesgo
1. **Complejidad del filtro por rol**: Query con múltiples JOINs puede ser difícil de depurar
   - **Mitigación**: Tests exhaustivos, logging de queries

2. **Restricciones por rol inconsistentes**: Si BaseCareer no está bien implementado
   - **Mitigación**: Verificar implementación de BaseCareer primero

### 🟢 Bajo Riesgo
1. **Validación de fechas**: Fechas inválidas pueden causar errores
   - **Mitigación**: Validación robusta en Request

---

## 13. PRÓXIMOS PASOS

### Paso 1: Verificar BaseCareer
Antes de implementar, verificar que `BaseCareer` implementa correctamente el filtrado por carreras.

### Paso 2: Implementar FilterEvidenceRequest
Crear validación robusta para todos los filtros.

### Paso 3: Implementar EvidenceService::filterEvidences()
Método central con toda la lógica de filtrado y restricciones.

### Paso 4: Crear Tests
Tests unitarios y de feature ANTES de implementar controller.

### Paso 5: Implementar Exportación
Excel y PDF después de validar que el filtrado funciona.

---

**Análisis completado** ✅  
**Siguiente acción**: Revisión del checklist con el equipo y confirmación de alcance
