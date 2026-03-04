# 📋 Análisis Preliminar: Implementación de GUIDs en SAAC

## 🎯 Objetivo
Implementar GUIDs (Globally Unique Identifiers) para reemplazar los IDs autoincrementales expuestos actualmente, mejorando la seguridad evitando la enumeración de recursos y exposición de información sensible sobre el tamaño de la base de datos.

---

## 📊 Situación Actual

### Estructura de Base de Datos
Actualmente, **TODAS** las tablas del sistema utilizan:
- **Primary Key**: `BIGINT UNSIGNED AUTO_INCREMENT`
- **Naming Convention**: `{tabla}_id` (e.g., `usuario_id`, `evidencia_id`, `carrera_id`)
- **Exposed in API**: Los IDs autoincrementales se envían directamente en JSON responses
- **Exposed in URLs**: Rutas como `/api/estructura/carreras/{id}` usan el ID numérico

### Tablas Principales Identificadas (38 migraciones)
```
1.  USUARIO (usuario_id)
2.  CARRERA (carrera_id)
3.  SEDE (sede_id)
4.  UNIVERSIDAD (universidad_id)
5.  EVIDENCIA (evidencia_id)
6.  CRITERIO (criterio_id)
7.  COMPONENTE (componente_id)
8.  DIMENSION (dimension_id)
9.  ESTANDAR (estandar_id)
10. PROCESO (proceso_id)
11. CICLO_ACREDITACION (ciclo_acreditacion_id)
12. AUTOEVALUACION (autoevaluacion_id)
13. COMPROMISO_MEJORA (compromiso_mejora_id)
14. EVIDENCIA_ASIGNACION (evidencia_asignacion_id)
15. ESTADO_EVIDENCIA (estado_evidencia_id)
16. ARCHIVO (archivo_id)
17. COMENTARIO (comentario_id)
18. BITACORA (bitacora_id)
19. TIPO_ACCION (tipo_accion_id)
20. SOLICITUD_AMPLIACION (solicitud_ampliacion_id)
21. APROBACION_CRITERIO (aprobacion_id)
22. APROBACION_EVIDENCIA (aprobacion_id)
23. NOTIFICACION (notificacion_id)
24. CARRERA_USUARIO (carrera_usuario_id) - Tabla pivot
25. CARRERA_SEDE (carrera_sede_id) - Tabla pivot
26. COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION - Tabla pivot
27. COMPROMISO_MEJORA_EVIDENCIA - Tabla pivot
```

---

## 🏗️ Estrategia de Implementación

### Enfoque Recomendado: **Dual Key Pattern**

En lugar de reemplazar completamente los IDs autoincrementales, implementamos un patrón de "dual key":
- **ID autoincremental interno**: Se mantiene como PRIMARY KEY para optimización de índices y relaciones internas
- **UUID público**: Se agrega como campo adicional, único, para uso en APIs y URLs

#### Ventajas del Dual Key Pattern
✅ No rompe relaciones existentes (foreign keys)  
✅ Mejor rendimiento en JOINs (INT vs UUID)  
✅ Migración gradual sin downtime  
✅ Rollback sencillo en caso de problemas  
✅ Los índices existentes siguen siendo óptimos  

---

## 🔧 Cambios Requeridos

### 1. Migraciones de Base de Datos

#### 1.1 Agregar columna UUID a TODAS las tablas principales
```php
// Ejemplo: database/migrations/2026_02_18_create_add_uuid_to_usuario_table.php
Schema::table('USUARIO', function (Blueprint $table) {
    $table->uuid('uuid')->unique()->after('usuario_id');
    $table->index('uuid'); // Para búsquedas rápidas
});

// Generar UUIDs para registros existentes
DB::table('USUARIO')->orderBy('usuario_id')->chunk(100, function ($users) {
    foreach ($users as $user) {
        DB::table('USUARIO')
            ->where('usuario_id', $user->usuario_id)
            ->update(['uuid' => Str::uuid()]);
    }
});

// Hacer el campo NOT NULL una vez poblado
Schema::table('USUARIO', function (Blueprint $table) {
    $table->uuid('uuid')->nullable(false)->change();
});
```

#### 1.2 Tablas que REQUIEREN agregar UUID (Prioridad)

**Alta Prioridad** (Expuestas en API públicas):
1. ✅ USUARIO
2. ✅ EVIDENCIA
3. ✅ EVIDENCIA_ASIGNACION
4. ✅ COMPROMISO_MEJORA
5. ✅ ARCHIVO
6. ✅ SOLICITUD_AMPLIACION
7. ✅ PROCESO
8. ✅ CARRERA
9. ✅ CRITERIO
10. ✅ COMPONENTE
11. ✅ DIMENSION
12. ✅ ESTANDAR

**Media Prioridad** (Uso interno/admin):
- UNIVERSIDAD, SEDE, CICLO_ACREDITACION
- APROBACION_CRITERIO, APROBACION_EVIDENCIA
- NOTIFICACION, BITACORA

**Baja Prioridad** (Catálogos/Referencias):
- ESTADO_EVIDENCIA, TIPO_ACCION
- Tablas pivot sin exposición directa

---

### 2. Modelos Eloquent

#### 2.1 Trait para UUID (crear nuevo archivo)
```php
// app/Traits/HasUuid.php
namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     * Esto hace que Laravel use UUID en vez de ID en route model binding
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Scope para buscar por UUID
     */
    public function scopeByUuid($query, string $uuid)
    {
        return $query->where('uuid', $uuid);
    }
}
```

#### 2.2 Modificar TODOS los modelos principales
```php
// Ejemplo: app/Models/User.php
use App\Traits\HasUuid;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, HasUuid; // ← Agregar trait

    protected $table = 'USUARIO';
    protected $primaryKey = 'usuario_id'; // Se mantiene para relaciones internas
    public $timestamps = true;

    // Agregar uuid a fillable y casts
    protected $fillable = [
        'uuid',         // ← Nuevo
        'cedula',
        'nombre',
        'email',
        'status'
    ];

    protected $casts = [
        'uuid' => 'string', // ← Nuevo
    ];

    // Ocultar el ID autoincremental en respuestas JSON
    protected $hidden = [
        'usuario_id', // ← Ocultar ID interno
    ];

    // Exponer UUID en JSON
    protected $appends = ['uuid'];
}
```

#### 2.3 Lista de Modelos a Modificar (26 modelos)
```
✅ User.php
✅ Evidence.php
✅ EvidenceAssignment.php
✅ ImprovementCommitment.php
✅ File.php
✅ ExtensionRequest.php
✅ Process.php
✅ Career.php
✅ Criterion.php
✅ Component.php
✅ Dimension.php
✅ Standard.php
✅ Campus.php
✅ University.php
✅ AccreditationCycle.php
✅ Autoevaluation.php
✅ EvidenceState.php
✅ Comment.php
✅ AuditLog.php
✅ ActionType.php
✅ CriterionApproval.php
✅ EvidenceApproval.php
✅ Notification.php
⚠️  Role.php (Spatie - requiere cuidado especial)
⚠️  Permission.php (Spatie - requiere cuidado especial)
```

---

### 3. Resources (API Responses)

#### 3.1 Modificar TODOS los Resources para exponer UUID
```php
// Ejemplo: app/Http/Resources/EvidenceResource.php
class EvidenceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,              // ← Usar UUID en vez de ID
            // 'evidencia_id' => $this->evidencia_id, // ← ELIMINAR o mover a hidden
            'criterio_uuid' => $this->criterion->uuid ?? null, // ← UUIDs en relaciones
            'estado_uuid' => $this->evidenceState->uuid ?? null,
            'descripcion' => $this->descripcion,
            'nomenclatura' => $this->nomenclatura,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relaciones anidadas también deben usar UUID
            'criterion' => new CriterionResource($this->whenLoaded('criterion')),
            'assignments' => EvidenceAssignmentResource::collection($this->whenLoaded('assignments')),
        ];
    }
}
```

#### 3.2 Lista de Resources a Modificar (15+ resources)
```
✅ UserResource.php
✅ EvidenceResource.php
✅ EvidenceAssignmentResource.php
✅ ImprovementCommitmentResource.php
✅ FileResource.php
✅ ExtensionRequestResource.php
✅ ProcessResource.php
✅ CareerResource.php
✅ CriterionResource.php
✅ ComponentResource.php
✅ DimensionResource.php
✅ StandardResource.php
✅ AuditLogResource.php
✅ NotificationResource.php
(y otros...)
```

---

### 4. Controllers

#### 4.1 Cambios en Route Model Binding
```php
// ANTES
public function show($id)
{
    $evidence = Evidence::find($id);
    // ...
}

// DESPUÉS (con trait HasUuid automáticamente usa uuid)
public function show(Evidence $evidence)
{
    // Laravel automáticamente busca por UUID gracias al trait
    return EvidenceResource::make($evidence);
}
```

#### 4.2 Actualizar validaciones de Request
```php
// ANTES: app/Http/Requests/EvidenceAssignmentRequest.php
'evidencia_id' => 'required|integer|exists:EVIDENCIA,evidencia_id'

// DESPUÉS
'evidencia_uuid' => 'required|uuid|exists:EVIDENCIA,uuid'
```

#### 4.3 Controllers a Modificar (25+ controllers)
```
Todos los controllers que manejan IDs en:
- Parámetros de ruta ({id})
- Request validation
- Búsquedas por ID
- Relaciones
```

---

### 5. Routes (API)

#### 5.1 Las rutas NO necesitan cambiar
```php
// Estas rutas siguen igual, pero Laravel buscará por UUID internamente
Route::get('estructura/evidencias/{evidence}', [EvidenceController::class, 'show']);
Route::put('estructura/evidencias/{evidence}', [EvidenceController::class, 'update']);
Route::delete('estructura/evidencias/{evidence}', [EvidenceController::class, 'destroy']);
```

#### 5.2 Considerar agregar rutas legacy (opcional)
```php
// Soporte temporal para IDs numéricos durante migración
Route::get('estructura/evidencias/by-id/{id}', [EvidenceController::class, 'showById'])
    ->where('id', '[0-9]+');
```

---

### 6. Services

#### 6.1 Actualizar métodos de búsqueda
```php
// ANTES: app/Services/EvidenceService.php
public function findById(int $id)
{
    return Evidence::find($id);
}

// DESPUÉS
public function findByUuid(string $uuid)
{
    return Evidence::byUuid($uuid)->first();
}

// O simplemente usar el route model binding
```

#### 6.2 Actualizar lógica de negocio
```php
// Cambiar todas las referencias de ID a UUID en:
- Creación de registros
- Actualizaciones
- Logs de auditoría
- Eventos y notificaciones
```

---

### 7. Frontend (TypeScript/React)

#### 7.1 Actualizar Types
```typescript
// ANTES: frontend/src/Types/EvidenceTypes.ts
export interface Evidence {
  evidencia_id: number;
  criterio_id: number;
  estado_evidencia_id: number;
  // ...
}

// DESPUÉS
export interface Evidence {
  uuid: string;
  criterio_uuid: string;
  estado_uuid: string;
  // ...
}
```

#### 7.2 Actualizar Services
```typescript
// ANTES: frontend/src/Services/StructureService.ts
async getElement(type: ElementType, id: number): Promise<StructureElement> {
  const response = await axiosInstance.get(`/estructura/${endpoint}/${id}`);
  return response.data;
}

// DESPUÉS
async getElement(type: ElementType, uuid: string): Promise<StructureElement> {
  const response = await axiosInstance.get(`/estructura/${endpoint}/${uuid}`);
  return response.data;
}
```

#### 7.3 Actualizar Componentes
```typescript
// Cambiar todas las props de number a string
interface EvidenceCardProps {
  evidenceUuid: string; // antes: evidenceId: number
  onEdit: (uuid: string) => void; // antes: (id: number) => void
}
```

#### 7.4 Archivos Frontend a Modificar (estimado: 50+ archivos)
```
Types:
- StructureTypes.ts
- EvidenceTypes.ts
- EvidenceAssignmentTypes.ts
- ImprovementCommitmentTypes.ts
- ExtensionRequestTypes.ts
- UserTypes.ts
- FileTypes.ts
- AuditLogTypes.ts
(y todos los demás Types/*.ts)

Services:
- StructureService.ts
- EvidenceAssignmentService.ts
- FileService.ts
- UserService.ts
- ExtensionRequestService.ts
- AuditLogService.ts
(y todos los Services/*.ts)

Components/Pages:
- Todos los componentes que manejan IDs
- Modales de edición/creación
- Listas con acciones (editar/eliminar)
- Formularios
```

---

## 🔄 Plan de Migración Sugerido

### Fase 1: Preparación (1 semana)
1. ✅ Crear trait `HasUuid`
2. ✅ Crear migraciones para agregar columna `uuid` a todas las tablas
3. ✅ Ejecutar migraciones en entorno de desarrollo
4. ✅ Generar UUIDs para datos existentes
5. ✅ Crear tests de integración

### Fase 2: Backend Core (2 semanas)
1. ✅ Agregar trait a todos los modelos
2. ✅ Modificar todos los Resources
3. ✅ Actualizar Controllers (uno por uno, con tests)
4. ✅ Actualizar Services
5. ✅ Actualizar Form Requests (validaciones)
6. ✅ Actualizar Policies (si usan IDs)
7. ✅ Tests end-to-end de endpoints

### Fase 3: Frontend (2-3 semanas)
1. ✅ Actualizar Types (definiciones TypeScript)
2. ✅ Actualizar Services (API calls)
3. ✅ Actualizar Components (props e interfaces)
4. ✅ Actualizar Pages (lógica de negocio)
5. ✅ Tests unitarios y de integración
6. ✅ Testing manual exhaustivo

### Fase 4: Testing y Refinamiento (1 semana)
1. ✅ Tests de regresión completos
2. ✅ Verificar logs de auditoría
3. ✅ Performance testing (comparar UUID vs INT)
4. ✅ Ajustes de índices si es necesario
5. ✅ Documentación actualizada

### Fase 5: Deploy Gradual (1 semana)
1. ✅ Deploy a staging
2. ✅ Testing con datos reales
3. ✅ Deploy a producción (con feature flag?)
4. ✅ Monitoreo intensivo
5. ✅ Rollback plan preparado

---

## ⚠️ Consideraciones Importantes

### Impacto en Performance
- **UUIDs (36 chars)** vs **BIGINT (8 bytes)**
  - Mayor uso de espacio en disco
  - Mayor uso de memoria para índices
  - JOINs potencialmente más lentos

**Mitigación**:
- Usar `uuid` como BINARY(16) en lugar de CHAR(36) para mejor performance
- Mantener índices en PRIMARY KEY autoincremental para JOINs internos
- Índices compuestos optimizados

### Backward Compatibility
Durante el periodo de transición:
- ✅ Mantener IDs internos funcionando
- ✅ Proveer endpoints legacy (opcional)
- ✅ Logs de auditoría claros sobre qué se está usando

### Relaciones Complejas
Tablas con múltiples foreign keys:
```sql
-- Ejemplo: EVIDENCIA_ASIGNACION
evidencia_asignacion_id (PK)
proceso_id (FK)
evidencia_id (FK)
usuario_id (FK)
```
**Solución**: Mantener FKs con IDs internos, solo exponer UUIDs en API

### Testing
- ✅ Tests de migración con datos de producción (anonimizados)
- ✅ Tests de performance (antes/después)
- ✅ Tests de seguridad (verificar que IDs no se expongan)
- ✅ Tests de rollback

---

## 📝 Checklist de Implementación

### Base de Datos
- [ ] Crear trait `HasUuid`
- [ ] Migración para USUARIO
- [ ] Migración para EVIDENCIA
- [ ] Migración para EVIDENCIA_ASIGNACION
- [ ] Migración para COMPROMISO_MEJORA
- [ ] Migración para ARCHIVO
- [ ] Migración para SOLICITUD_AMPLIACION
- [ ] Migración para PROCESO
- [ ] Migración para CARRERA
- [ ] Migración para CRITERIO
- [ ] Migración para COMPONENTE
- [ ] Migración para DIMENSION
- [ ] Migración para ESTANDAR
- [ ] (Y así para cada tabla...)

### Modelos
- [ ] User.php
- [ ] Evidence.php
- [ ] EvidenceAssignment.php
- [ ] ImprovementCommitment.php
- [ ] File.php
- [ ] ExtensionRequest.php
- [ ] (Y así para cada modelo...)

### Resources
- [ ] UserResource.php
- [ ] EvidenceResource.php
- [ ] (Y así para cada resource...)

### Controllers
- [ ] UserController.php
- [ ] EvidenceController.php
- [ ] (Y así para cada controller...)

### Frontend Types
- [ ] StructureTypes.ts
- [ ] EvidenceTypes.ts
- [ ] (Y así para cada type...)

### Frontend Services
- [ ] StructureService.ts
- [ ] EvidenceAssignmentService.ts
- [ ] (Y así para cada service...)

### Frontend Components/Pages
- [ ] Lista de evidencias
- [ ] Formulario de evidencia
- [ ] Detalle de evidencia
- [ ] (Y así para cada componente...)

---

## 🎯 Resumen Ejecutivo

### Impacto Total Estimado
- **Migraciones**: ~25-30 archivos nuevos
- **Modelos Backend**: ~26 archivos modificados
- **Resources Backend**: ~15 archivos modificados
- **Controllers Backend**: ~25 archivos modificados
- **Services Backend**: ~15 archivos modificados
- **Request Validators**: ~20 archivos modificados
- **Types Frontend**: ~15 archivos modificados
- **Services Frontend**: ~10 archivos modificados
- **Components/Pages**: ~50+ archivos modificados

### Tiempo Estimado
- **Desarrollo**: 6-8 semanas
- **Testing**: 2-3 semanas
- **Deploy**: 1 semana
- **Total**: 9-12 semanas

### Riesgos
🔴 **Alto**: Romper relaciones existentes si no se usa Dual Key Pattern  
🟡 **Medio**: Impacto en performance si no se optimizan índices  
🟡 **Medio**: Complejidad en testing de migración con datos existentes  
🟢 **Bajo**: Exposición de IDs internos (si se implementa correctamente)  

### Beneficios
✅ Mayor seguridad (no enumeración de recursos)  
✅ Mejor escalabilidad (IDs globalmente únicos)  
✅ Mejor para arquitectura distribuida  
✅ Cumplimiento con mejores prácticas de seguridad  

---

## 📚 Referencias y Recursos

### Laravel UUID
- [Laravel UUID Documentation](https://laravel.com/docs/11.x/eloquent#uuid-and-ulid-keys)
- [Spatie Laravel UUID Package](https://github.com/spatie/laravel-route-attributes)

### Best Practices
- [OWASP - Insecure Direct Object References](https://owasp.org/www-community/attacks/Insecure_Direct_Object_References)
- [UUID vs Auto-increment IDs](https://tomharrisonjr.com/uuid-or-guid-as-primary-keys-be-careful-7b2aa3dcb439)

### Performance
- [UUID Performance in MySQL](https://www.percona.com/blog/store-uuid-optimized-way/)
- [Binary UUID vs String UUID](https://mysqlserverteam.com/storing-uuid-values-in-mysql-tables/)

---

**Documento creado**: 2026-02-18  
**Autor**: Análisis preliminar para implementación de GUIDs  
**Versión**: 1.0  
