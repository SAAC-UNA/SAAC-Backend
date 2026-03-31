# HU-007 — Asignación de Evidencias: Adaptación al Modelo Flexible

## Contexto

Cuando un encargado asigna una evidencia a usuarios/roles a través de
`POST /api/evidencias-asignaciones`, el sistema relaciona tres entidades:

```
EVIDENCIA_ASIGNACION
  ├── evidencia_id  →  EVIDENCIA  (criterio_id XOR elemento_id)
  └── proceso_id    →  PROCESO    →  CICLO_ACREDITACION  →  MODELO_ESTRUCTURA.tipo
```

Sin el guard de compatibilidad, era posible asignar una evidencia anclada al árbol
tradicional (`criterio_id`) a un proceso que pertenece a un ciclo de modelo flexible
(`elemento_flexible`), y viceversa. Esto dejaba la base de datos en estado inconsistente:
las asignaciones no podían ser navegadas coherentemente por el front-end.

---

## Problema que se resuelve

**Antes:** `assignEvidence()` solo verificaba que el proceso y la evidencia existieran
(`Process::find()` / `Evidence::find()`). No validaba la compatibilidad del modelo de
acreditación del ciclo con el tipo de anclaje de la evidencia.

**Después:** Se agregó un guard que lee `PROCESO → CICLO_ACREDITACION → MODELO_ESTRUCTURA.tipo`
y compara contra el anclaje de la evidencia (`criterio_id` vs `elemento_id`).

---

## Checklist de cambios

### ✅ `app/Services/EvidenceAssignmentService.php`

- **Import agregado:** `use App\Models\StructureModel;`
- **`assignEvidence()`:** reemplaza `Process::find($procesoId)` por
  `Process::with('accreditationCycle.modeloEstructura')->find($procesoId)` —
  carga el ciclo y su modelo en un solo query (sin N+1).
- **`assignEvidence()`:** reemplaza `Evidence::find($evidenciaId)` por variable
  `$evidencia` para poder leer sus campos en el guard.
- **Guard nuevo:** dos bloques `\InvalidArgumentException` al inicio de la transacción:

```php
$tipoModelo = $proceso->accreditationCycle?->modeloEstructura?->tipo;
$esFlexible = $tipoModelo === StructureModel::TIPO_ELEMENTO_FLEXIBLE;

if ($esFlexible && $evidencia->criterio_id !== null) {
    throw new \InvalidArgumentException(
        'El ciclo usa modelo elemento_flexible pero la evidencia está anclada '
        . 'a un criterio tradicional. Use una evidencia con elemento_id.'
    );
}

if (!$esFlexible && $evidencia->elemento_id !== null) {
    throw new \InvalidArgumentException(
        'El ciclo usa modelo tradicional pero la evidencia está anclada '
        . 'a un elemento flexible. Use una evidencia con criterio_id.'
    );
}
```

---

### ✅ `app/Http/Controllers/EvidenceAssignmentController.php`

- **`store()`:** se agregó `catch (\InvalidArgumentException $e)` **antes** del
  catch genérico `\Exception`, devolviendo 422 con el mensaje descriptivo.

```php
} catch (\InvalidArgumentException $e) {
    // MODELO FLEXIBLE (HU-007): incompatibilidad de modelo entre proceso y evidencia
    return response()->json([
        'message' => $e->getMessage(),
    ], 422);
}
```

---

### ✅ `app/Http/Resources/EvidenceAssignmentResource.php`

- **Fallback manual del bloque `evidencia`:** se agregó `'elemento_id'` junto a
  `'criterio_id'` para que el front-end pueda identificar el tipo de evidencia
  incluso cuando la relación no está cargada con eager-load.

```php
'criterio_id'   => $this->criterio_id  ?? null,
'elemento_id'   => $this->elemento_id  ?? null,  // MODELO FLEXIBLE (HU-007)
```

> Cuando la relación `evidence` sí está cargada, `EvidenceResource` ya expone
> `elemento_id` directamente — este cambio solo afecta el path sin eager-load.

---

## Comportamiento resultante

| Situación | Respuesta |
|---|---|
| Proceso de ciclo tradicional + evidencia con `criterio_id` | 201 — asignación creada ✅ |
| Proceso de ciclo flexible + evidencia con `elemento_id` | 201 — asignación creada ✅ |
| Proceso de ciclo flexible + evidencia con `criterio_id` | 422 — incompatibilidad de modelo |
| Proceso de ciclo tradicional + evidencia con `elemento_id` | 422 — incompatibilidad de modelo |
| Proceso inexistente | 500 → `\Exception` (comportamiento sin cambios) |
| Evidencia inexistente | 500 → `\Exception` (comportamiento sin cambios) |
