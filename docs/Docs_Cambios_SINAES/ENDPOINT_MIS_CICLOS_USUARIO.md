# Endpoint Mis Ciclos — GET /api/usuarios/{id}/mis-ciclos

## Justificación

Los módulos del frontend que soportan el modelo dual (tradicional / flexible) necesitan conocer los ciclos de acreditación donde el usuario tiene asignaciones y el **tipo de modelo** de cada ciclo (`tradicional` o `elemento_flexible`).

### Problema anterior

El frontend llamaba a `GET /api/estructura/procesos` (vía `EvidenceAssignmentService.getAllProcesses()`) para resolver los nombres de ciclo y el tipo de modelo. Este endpoint retorna **todos los procesos del sistema**, lo cual:

1. **Sobre-obtiene datos**: un usuario con asignaciones en 1–2 ciclos recibía la lista completa de todos los procesos activos.
2. **Expone información innecesaria**: usuarios con rol de profesor veían procesos de carreras a las que no están vinculados.
3. **Requería cálculo en frontend**: el flag `isFlexible` se infería cruzando las asignaciones del usuario con la lista de procesos, usando `ciclo_acreditacion_id` como llave — una operación que el backend puede resolver directamente.

### Solución

Nuevo endpoint dedicado que retorna únicamente los ciclos relevantes para el usuario autenticado, con el tipo de modelo ya resuelto:

```
GET /api/usuarios/{usuarioId}/mis-ciclos
Authorization: Bearer {token}
Permiso: asignaciones.view
```

**Respuesta:**

```json
{
  "data": [
    {
      "ciclo_acreditacion_id": 3,
      "nombre": "Proceso de Acreditación Informática 2024",
      "tipo_modelo": "tradicional"
    },
    {
      "ciclo_acreditacion_id": 7,
      "nombre": "Proceso de Acreditación Computación 2026",
      "tipo_modelo": "elemento_flexible"
    }
  ]
}
```

---

## Cambios realizados

### `app/Http/Controllers/EvidenceAssignmentController.php`

- Importado `App\Models\ElementAssignment`
- Nuevo método `getUserCycles(string $usuarioId): JsonResponse`

**Lógica:**

```php
// Ciclos desde asignaciones tradicionales (EVIDENCIA_ASIGNACION)
$traditionalCycles = EvidenceAssignment::where('usuario_id', $userId)
    ->with('process.accreditationCycle.modeloEstructura')
    ->get()
    ->pluck('process.accreditationCycle')
    ->filter()
    ->unique('ciclo_acreditacion_id');

// Ciclos desde asignaciones flexibles (ELEMENTO_ASIGNACION)
$flexibleCycles = ElementAssignment::where('usuario_id', $userId)
    ->with('process.accreditationCycle.modeloEstructura')
    ->get()
    ->pluck('process.accreditationCycle')
    ->filter()
    ->unique('ciclo_acreditacion_id');

// Merge, deduplicar, mapear
$cycles = $traditionalCycles->merge($flexibleCycles)
    ->unique('ciclo_acreditacion_id')
    ->map(fn ($cycle) => [
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        'nombre'               => $cycle->nombre,
        'tipo_modelo'          => $cycle->modeloEstructura?->tipo ?? 'tradicional',
    ])
    ->values();
```

**Cadena de relaciones:**
```
EvidenceAssignment → process() → accreditationCycle() → modeloEstructura()
ElementAssignment  → process() → accreditationCycle() → modeloEstructura()
```

El campo `tipo` de `MODELO_ESTRUCTURA` distingue `'tradicional'` vs `'elemento_flexible'` (constantes definidas en `StructureModel.php`).

### `routes/api.php`

```php
Route::get('usuarios/{usuarioId}/mis-ciclos', [EvidenceAssignmentController::class, 'getUserCycles']);
```

Registrado dentro del grupo con middleware `auth:sanctum`, `refresh.session` y permiso `asignaciones.view`, junto a las rutas existentes `usuarios/{usuarioId}/evidencias-asignadas` y `usuarios/{usuarioId}/elementos-asignados`.

---

## Consumidores en frontend

| Módulo | Antes | Después |
|---|---|---|
| Mis Entregas (`MyEvidenceAssignmentsPage`) | `getAllProcesses()` → inferir ciclos | `getUserCycles(userId)` → datos directos, con fallback por inferencia |
| Gestionar Solicitudes (`ManageExtensionRequestsPage`) | `getAllProcesses()` → buscar nombre | Eliminó la dependencia; usa `process.nombre` que ya viene en las solicitudes vía `WITH_BASE` |

---

## Rama y Commit

- **Rama:** `development`
- **Commit:** `ENDPOINT_MIS_CICLOS_USUARIO`
