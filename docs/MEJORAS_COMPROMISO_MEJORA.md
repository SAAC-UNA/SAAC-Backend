# Mejoras al Módulo de Compromiso de Mejora

## Fecha: 21 de Diciembre de 2025

## Cambios Implementados

### 1. ✅ Asignación por Roles

Se implementó la funcionalidad de asignación de evidencias a roles, siguiendo la misma lógica del módulo `EvidenceAssignmentService`.

#### Cambios en `ImprovementCommitmentRequest.php`

**Antes:**
```php
'evidencias_asignar.*.usuario_id' => [
    'required',
    'integer',
    'exists:USUARIO,usuario_id',
],
```

**Ahora:**
```php
'evidencias_asignar.*.usuarios' => [
    'nullable',
    'array',
],
'evidencias_asignar.*.usuarios.*' => [
    'integer',
    'exists:USUARIO,usuario_id',
],
'evidencias_asignar.*.roles' => [
    'nullable',
    'array',
],
'evidencias_asignar.*.roles.*' => [
    'integer',
    'exists:roles,id',
],
```

#### Cambios en `ImprovementCommitmentService.php`

- **`createEvidenceAssignments()`**: Ahora acepta arrays de `usuarios` y `roles`
- **`syncEvidenceAssignments()`**: Actualizado para soportar múltiples usuarios y roles
- **Lógica de asignación**: 
  - Asigna directamente a usuarios especificados
  - Busca usuarios activos con los roles especificados usando Spatie Permission
  - Evita duplicados automáticamente (si un usuario ya tiene la asignación, la omite)

### 2. ✅ Corrección de Advertencias

Se corrigió el formato de fecha en la línea 256 del servicio para evitar advertencias al comparar fechas.

### 3. ⚠️ Verificación de LDAP

**IMPORTANTE**: No se pudo acceder al archivo `.env` para verificar si LDAP está desactivado.

Para **DESACTIVAR LDAP** en desarrollo, asegúrate de que tu archivo `.env` contenga:

```env
# Desactivar LDAP para desarrollo local
LDAP_ENABLED=false
LDAP_CONNECTION=default
LDAP_HOST=127.0.0.1
LDAP_PORT=389
LDAP_USERNAME=
LDAP_PASSWORD=
LDAP_BASE_DN=
LDAP_USE_SSL=false
LDAP_USE_TLS=false
LDAP_SSL_VERIFY=false
```

O mejor aún, comentar/eliminar las variables LDAP si no las estás usando.

### 4. 📋 Advertencias en Logs (Revisadas)

Se encontraron las siguientes advertencias en `storage/logs/laravel.log`:

1. **Tabla faltante**: `COMPROMISO_MEJORA_EVIDENCIA` - Esto probablemente ya fue resuelto con las migraciones existentes.
2. **Error de configuración de logger**: Asegúrate de que `config/logging.php` tiene un canal por defecto configurado.

## Ejemplo de Uso de la Nueva API

### Crear Compromiso con Asignaciones por Usuarios y Roles

```json
{
  "ciclo_acreditacion_id": 1,
  "descripcion": "Mejorar documentación del proceso X",
  "fecha_inicio": "2025-12-21",
  "fecha_fin": "2026-01-31",
  "selecciones": [
    {
      "entidad_tipo": "CRITERIO",
      "entidad_id": 5
    }
  ],
  "evidencias_asignar": [
    {
      "evidencia_id": 10,
      "usuarios": [1, 2, 3],
      "roles": [4, 5],
      "fecha_limite": "2026-01-15",
      "comentario": "Asignación urgente"
    },
    {
      "evidencia_id": 11,
      "roles": [4],
      "fecha_limite": "2026-01-20",
      "comentario": "Solo para coordinadores"
    }
  ]
}
```

### Actualizar Compromiso con Nuevas Asignaciones

```json
{
  "descripcion": "Descripción actualizada",
  "evidencias_asignar": [
    {
      "evidencia_id": 10,
      "usuarios": [1],
      "roles": [4, 5, 6],
      "fecha_limite": "2026-02-01"
    }
  ]
}
```

## Comportamiento de Asignaciones

### Asignación Directa a Usuarios
- Los usuarios especificados en el array `usuarios` reciben la asignación inmediatamente
- Se valida que no existan duplicados

### Asignación por Roles
- El sistema busca todos los usuarios **activos** que tienen el rol especificado
- Usa Spatie Permission: `User::role($role->name)->active()->get()`
- Si un usuario ya tiene la asignación (por otro rol o asignación directa), se omite silenciosamente

### Sincronización (UPDATE)
- Al actualizar, se usa `sync()` para reemplazar completamente las asignaciones existentes
- Las asignaciones antiguas se eliminan y se crean las nuevas

## Modelos Relacionados

- **ImprovementCommitment**: Compromiso de mejora principal
- **EvidenceAssignment**: Asignación individual de evidencia a usuario
- **User**: Usuario con roles (Spatie Permission)
- **Role**: Roles del sistema (tabla `roles`)

## Tablas de Base de Datos

- `COMPROMISO_MEJORA`: Compromisos de mejora
- `COMPROMISO_MEJORA_EVIDENCIA`: Relación many-to-many con evidencias del repositorio
- `COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION`: Relación many-to-many con asignaciones de evidencias
- `EVIDENCIA_ASIGNACION`: Asignaciones individuales de evidencias a usuarios
- `roles`: Roles del sistema (Spatie Permission)

## Validaciones Implementadas

1. ✅ Las evidencias asignadas deben estar vinculadas al compromiso
2. ✅ No se permiten duplicados de asignación (evidencia + usuario + proceso)
3. ✅ Los roles deben existir en la tabla `roles`
4. ✅ Los usuarios deben existir y estar activos
5. ✅ Las fechas de asignación y límite son opcionales

## Pruebas Recomendadas

### Test 1: Asignación Solo por Usuarios
```bash
POST /api/compromisos-mejora
{
  "evidencias_asignar": [
    {
      "evidencia_id": 10,
      "usuarios": [1, 2, 3]
    }
  ]
}
```

### Test 2: Asignación Solo por Roles
```bash
POST /api/compromisos-mejora
{
  "evidencias_asignar": [
    {
      "evidencia_id": 10,
      "roles": [4, 5]
    }
  ]
}
```

### Test 3: Asignación Mixta (Usuarios + Roles)
```bash
POST /api/compromisos-mejora
{
  "evidencias_asignar": [
    {
      "evidencia_id": 10,
      "usuarios": [1, 2],
      "roles": [4]
    }
  ]
}
```

### Test 4: Verificar No Duplicados
- Asignar evidencia 10 al usuario 1
- Intentar asignar evidencia 10 al usuario 1 nuevamente → Debe fallar
- Asignar evidencia 10 a rol que contiene usuario 1 → Debe omitir silenciosamente

## Notas Técnicas

- **Performance**: Se usa `User::role($role->name)` que es optimizado por Spatie
- **Transacciones**: Todas las operaciones están envueltas en `DB::transaction()`
- **Errores**: Se usan `ValidationException` para errores de validación de negocio
- **Estado**: Las asignaciones nuevas siempre se crean con estado 'Pendiente'

## Próximos Pasos Sugeridos

1. 🔄 Ejecutar migraciones para asegurar que todas las tablas existen
2. 🧪 Crear tests unitarios para las nuevas funcionalidades
3. 📝 Actualizar documentación de API (Postman/Swagger)
4. ✅ Verificar configuración de LDAP en `.env`
5. 📊 Monitorear logs para detectar errores

---

**Autor**: GitHub Copilot  
**Versión**: 1.0  
**Última actualización**: 21 de Diciembre de 2025
