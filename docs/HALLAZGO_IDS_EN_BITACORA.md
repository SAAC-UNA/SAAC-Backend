# Hallazgo — IDs de BD expuestos en el campo `detalle` de la Bitácora

## Descripción

El campo `detalle` de los registros de `BITACORA` incluye identificadores técnicos de base de datos (IDs numéricos, claves primarias) en el texto visible. Estos valores se guardan desde los controllers al llamar `AuditLogService::log()` y se muestran tal cual en la vista de bitácora del frontend (`AuditLogDetailModal`).

**Observación recibida:** los IDs internos no deben mostrarse al usuario en la bitácora.

---

## Causa raíz

El patrón usado en la mayoría de los controllers embebe el ID directamente en el string descriptivo:

```php
// ❌ Patrón actual con ID expuesto
AuditLogService::log('eliminar', "Se eliminó el ciclo \"{$cycle->nombre}\" (ID: {$id}).", 'Ciclo Acreditación');
AuditLogService::log('activar', "Usuario activado: {$user->nombre} (ID: {$user->usuario_id})", 'Usuarios');
AuditLogService::log('crear', "Se creó el proceso ID {$process->proceso_id} (Tipo: {$process->tipo_proceso}).", 'Procesos');

// ✅ Patrón correcto sin ID expuesto
AuditLogService::log('eliminar', "Se eliminó el ciclo de acreditación \"{$cycle->nombre}\".", 'Ciclo Acreditación');
AuditLogService::log('activar', "Se activó el usuario \"{$user->nombre}\".", 'Usuarios');
AuditLogService::log('crear', "Se creó el proceso de tipo \"{$process->tipo_proceso}\".", 'Procesos');
```

---

## Alcance — Llamadas que requieren corrección

| Controller | Línea | Texto actual con ID | Fix sugerido |
|------------|-------|---------------------|--------------|
| `AccreditationCycleController` | 126 | `"...\"(ID: {$id})."` | Eliminar `(ID: {$id})` |
| `ElementExtensionTimeRequestController` | 105 | `"...(ID: {$solicitud->solicitud_ampliacion_elemento_id}) para elemento asignación {$solicitud->elemento_asignacion_id}"` | Usar nombre/descripción del elemento en lugar de IDs |
| `ElementExtensionTimeRequestController` | 139 | `"...cancelada (ID: {$id})"` | Eliminar `(ID: {$id})` |
| `CampusController` | 155 | `"...\"(ID: {$campus->campus_id})..."` | Eliminar `(ID: {$campus->campus_id})` |
| `CareerController` | 124 | `"...\"(ID: {$career->carrera_id})..."` | Eliminar `(ID: {$career->carrera_id})` |
| `UserController` | 113 | `"...({$user->nombre} (ID: {$user->usuario_id})"` | Eliminar `(ID: {$user->usuario_id})` |
| `UserController` | 134 | `"...({$user->nombre} (ID: {$user->usuario_id})"` | Eliminar `(ID: {$user->usuario_id})` |
| `UserController` | 152 | `"...{$user->nombre} (ID: {$user->usuario_id})"` | Eliminar `(ID: {$user->usuario_id})` |
| `UserController` | 172 | `"...{$user->nombre} (ID: {$user->usuario_id})..."` | Eliminar `(ID: {$user->usuario_id})` |
| `UserController` | 191 | `"...{$updatedUser->nombre} (ID: {$updatedUser->usuario_id})"` | Eliminar `(ID: {$updatedUser->usuario_id})` |
| `CriterionApprovalController` | 160 | `"...{$criterion->nomenclatura} (ID: {$criterioId})"` | Eliminar `(ID: {$criterioId})` |
| `CriterionApprovalController` | 198 | `"...{$criterion->nomenclatura} (ID: {$criterioId})"` | Eliminar `(ID: {$criterioId})` |
| `CriterionApprovalController` | 302 | `"Evidencia {$evidenceId} aprobada...criterio {$criterion->nomenclatura} (ID: {$criterionId})"` | Eliminar `$evidenceId` e `(ID: {$criterionId})` |
| `CriterionApprovalController` | 344 | `"Evidencia {$evidenceId} rechazada...criterio {$criterion->nomenclatura} (ID: {$criterionId})"` | Eliminar `$evidenceId` e `(ID: {$criterionId})` |
| `ExtensionTimeRequestController` | 158 | `"...(ID: {$extensionRequest->solicitud_ampliacion_id}) para {$tipoAsignacion}"` | Eliminar `(ID: {...})`, describir la asignación por nombre |
| `ExtensionTimeRequestController` | 196 | `"...cancelada (ID: {$id})"` | Eliminar `(ID: {$id})` |
| `RoleController` | 71 | `"Rol creado: {$role->name} (ID: {$role->id})"` | Eliminar `(ID: {$role->id})` |
| `RoleController` | 119 | `"Rol actualizado: {$updatedRole->name} (ID: {$updatedRole->id})"` | Eliminar `(ID: {$updatedRole->id})` |
| `RoleController` | 172 | `"Rol eliminado: {$role->name} (ID: {$role->id})"` | Eliminar `(ID: {$role->id})` |
| `RoleController` | 228 | `"Rol {$state}: {$result['role']->name} (ID: {$result['role']->id})"` | Eliminar `(ID: {...})` |
| `EvidenceController` | 157 | `"...\"(ID: {$evidence->evidencia_id})..."` | Eliminar `(ID: {$evidence->evidencia_id})` |
| `DimensionController` | 147 | `"...\"(ID: {$dimension->dimension_id})..."` | Eliminar `(ID: {$dimension->dimension_id})` |
| `CriterionController` | 133 | `"...\"(ID: {$criterion->criterio_id})..."` | Eliminar `(ID: {$criterion->criterio_id})` |
| `ProcessController` | 36 | `"Se creó el proceso ID {$process->proceso_id} (Tipo: ...)"` | `"Se creó el proceso de tipo \"{$process->tipo_proceso}\"."` |
| `ProcessController` | 56 | `"Se actualizó el proceso ID {$updated->proceso_id} (Tipo: ...)"` | `"Se actualizó el proceso de tipo \"{$updated->tipo_proceso}\"."` |
| `ProcessController` | 83 | `"Se {$statusText} el proceso ID {$process->proceso_id} (Tipo: ...)"` | `"Se {$statusText} el proceso de tipo \"{$process->tipo_proceso}\"."` |
| `ProcessController` | 126 | `"Se eliminó el proceso ID {$procesoId} (Tipo: {$tipoProceso})."` | `"Se eliminó el proceso de tipo \"{$tipoProceso}\"."` |
| `StandardController` | 112 | `"...\"(ID: {$standar->estandar_id})..."` | Eliminar `(ID: {$standar->estandar_id})` |

**Total: 27 llamadas en 13 controllers.**

---

## Regla a seguir

> El campo `detalle` de `AuditLogService::log()` debe describir **qué ocurrió** usando nombres, nomenclaturas o títulos legibles. Nunca debe incluir IDs numéricos, claves primarias ni cualquier identificador técnico de la base de datos.

Los mensajes deben responder a: *"¿Qué acción se realizó sobre qué recurso?"* usando el nombre del recurso, no su ID.

---

## Nota

Los logs del **AccreditationReportController (HU-027)** fueron escritos directamente con el patrón correcto — no incluyen IDs:
```php
"Se publicó el informe de acreditación del ciclo \"{$cycle->nombre}\" (resolución: {$report->numero_resolucion})."
"Se despublicó el informe de acreditación del ciclo \"{$updated->accreditationCycle->nombre}\" (resolución: {$updated->numero_resolucion})."
```
