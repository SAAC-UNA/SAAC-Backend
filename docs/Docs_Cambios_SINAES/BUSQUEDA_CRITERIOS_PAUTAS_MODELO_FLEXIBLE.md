# BUSQUEDA_CRITERIOS_PAUTAS_MODELO_FLEXIBLE — Backend

## Contexto y motivación

El módulo de búsqueda avanzada de evidencias (HU-012) fue diseñado originalmente para el modelo
**tradicional** de SINAES: la jerarquía fija `DIMENSION → COMPONENTE → CRITERIO → EVIDENCIA`.

Con la incorporación del modelo **elemento_flexible** (SINAES 2026), los procesos de acreditación
pueden operar sobre una jerarquía dinámica almacenada en la tabla `ELEMENTO`, con asignaciones en
`ELEMENTO_ASIGNACION` en lugar de `EVIDENCIA_ASIGNACION`.

El explorador de criterios/pautas del frontend necesitaba poder:
1. Filtrar evidencias por `proceso_id` (modelo tradicional).
2. Explorar asignaciones de elementos (modelo flexible) mediante un endpoint equivalente al
   filtrado de evidencias.

Ambas necesidades requerían cambios puntuales en el backend sin romper el comportamiento existente.

---

## Decisión de diseño: dos endpoints, un contrato de respuesta

Se analizó si `elemento_id` podía agregarse como filtro al endpoint existente
`GET /api/estructura/evidencias/filter`. La conclusión fue **no**, por razones estructurales:

- `EVIDENCIA` y `ELEMENTO_ASIGNACION` son tablas completamente independientes.
- `EVIDENCIA` no tiene columna `elemento_id`; las asignaciones de elementos usan
  `ELEMENTO_ASIGNACION.elemento_id`.
- Mezclar ambas entidades en un mismo endpoint produciría una consulta sin coherencia semántica.

La solución fue activar el endpoint `GET /api/elementos-asignaciones/filtrar`, que ya tenía su
**ruta definida en `api.php`** pero sin implementación en el controlador. El frontend distingue
qué endpoint usar mediante el flag `is_flexible` en los filtros, y mapea la respuesta al mismo
shape para que la tabla de resultados funcione sin modificaciones adicionales.

---

## Archivos modificados

### 1. `app/Http/Requests/FilterEvidenceRequest.php`

**Cambio:** Se agregó la regla de validación para `proceso_id`.

```php
'proceso_id' => [
    'nullable',
    'integer',
    'exists:PROCESO,proceso_id',
],
```

**Justificación:** `FilterEvidenceRequest` ya tenía el mensaje de error y el `attribute` para
`proceso_id` (señal de que fue anticipado), pero faltaba la regla. Sin ella, Laravel descarta el
parámetro en `$request->validated()` antes de que llegue al service.

> **Nota:** El Request también tenía mensajes para `elemento_id` pero sin regla. No se agregó
> la regla porque `elemento_id` no está en la tabla `EVIDENCIA` y la validación
> `exists:EVIDENCIA,...` no tendría sentido. El filtro de elemento se maneja exclusivamente
> en el endpoint flexible.

---

### 2. `app/Services/EvidenceService.php` — `filterEvidences()`

**Cambio:** Extracción del filtro `proceso_id` y aplicación en la query.

```php
// Extracción (junto a cicloId y modeloEstructuraId)
$procesoId = $filters['proceso_id'] ?? null;

// Aplicación en la query
if ($procesoId) {
    $query->whereHas('assignments', fn ($q) =>
        $q->where('proceso_id', $procesoId)
    );
}
```

**Justificación:** La relación ya existía: `EVIDENCIA → EVIDENCIA_ASIGNACION.proceso_id → PROCESO`.
El filtro es un `whereHas` sobre la relación `assignments` del modelo `Evidence`, consistente con
los demás filtros contextuales (`cicloId`, `modeloEstructuraId`) que usan el mismo patrón.

---

### 3. `app/Services/ElementAssignmentService.php` — método `filter()` (nuevo)

**Cambio:** Se implementó el método `filter()` para el explorador de pautas.

```php
public function filter(array $filters, User $user): array
{
    $query = ElementAssignment::with(['element', 'user', 'process']);

    if (!empty($filters['proceso_id'])) {
        $query->where('proceso_id', (int) $filters['proceso_id']);
    }
    if (!empty($filters['elemento_id'])) {
        $query->where('elemento_id', (int) $filters['elemento_id']);
    }
    if (!empty($filters['estado'])) {
        $query->where('estado', $filters['estado']);
    }
    if (!empty($filters['usuario_id'])) {
        $query->where('usuario_id', (int) $filters['usuario_id']);
    }

    // Restricción de visibilidad por rol
    if (!$user->hasRole(['Superusuario', 'Administrador', 'Encargado de Acreditación'])) {
        $query->where('usuario_id', $user->usuario_id);
    }

    $paginated = $query->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    return [
        'data' => $paginated->items(),
        'meta' => [ ... ],
    ];
}
```

**Justificación:**
- El formato de respuesta (`data` + `meta`) es intencional: el frontend mapea esta respuesta
  al mismo shape que `PaginatedResponse<BackendEvidenceResult>`, por lo que la tabla de
  resultados reutiliza el mismo componente sin modificaciones.
- La restricción de visibilidad replica el patrón del `filterEvidences()` tradicional:
  usuarios con rol `Profesor` o `Evaluador` solo ven sus propias asignaciones.
- Los filtros disponibles (`proceso_id`, `elemento_id`, `estado`, `usuario_id`) cubren
  los casos de uso del explorador de pautas del frontend.

---

### 4. `app/Http/Controllers/ElementAssignmentController.php` — método `filtrar()` (nuevo)

**Cambio:** Se implementó el handler para la ruta que ya existía.

```php
/**
 * GET /api/elementos-asignaciones/filtrar
 */
public function filtrar(Request $request): JsonResponse
{
    $filters = $request->validate([
        'proceso_id'  => 'nullable|integer|exists:PROCESO,proceso_id',
        'elemento_id' => 'nullable|integer|exists:ELEMENTO,elemento_id',
        'estado'      => 'nullable|string|in:Pendiente,En Progreso,Completado,Vencido,Observada,Validada',
        'usuario_id'  => 'nullable|integer|exists:USUARIO,usuario_id',
        'per_page'    => 'nullable|integer|min:5|max:100',
        'page'        => 'nullable|integer|min:1',
    ]);

    $result = $this->service->filter($filters, $request->user());

    return response()->json($result, 200);
}
```

**Justificación:**
- La ruta `GET /api/elementos-asignaciones/filtrar` ya estaba declarada en `api.php` (línea 290)
  apuntando a `[ElementAssignmentController::class, 'filtrar']` pero el método no existía.
  Este cambio completa esa intención original.
- La validación se hace directamente en el controlador (en lugar de un FormRequest separado)
  porque los filtros son simples y no requieren mensajes de error personalizados en esta etapa.
- Los estados válidos incluyen `Observada` y `Validada` (retroalimentación) además de los
  estados de progreso, coherente con `ElementAssignment::ESTADO_*` constants.

---

## Rutas involucradas

| Método | Endpoint | Modelo | Necesita autenticación |
|--------|----------|--------|------------------------|
| `GET` | `/api/estructura/evidencias/filter` | Tradicional | ✅ `auth:sanctum` + `permission:evidencias.view` |
| `GET` | `/api/elementos-asignaciones/filtrar` | Flexible | ✅ `auth:sanctum` + `permission:asignaciones.view` |

---

## Lo que NO se modificó y por qué

| Cosa no modificada | Razón |
|---|---|
| Ruta `GET /api/elementos-asignaciones/filtrar` en `api.php` | Ya existía, solo faltaba el handler |
| `FilterEvidenceRequest` — regla `elemento_id` | No aplica: `EVIDENCIA` no tiene esa columna |
| Endpoint de exportación PDF/Excel | Reutilizan el mismo `filterEvidences()` que ya recibe `proceso_id`; el modo flexible no exporta por ahora |
| Modelos `Evidence` y `ElementAssignment` | Sin cambios; las relaciones existentes fueron suficientes |

---

## Verificación recomendada (Postman)

**Modo tradicional con proceso_id:**
```
GET /api/estructura/evidencias/filter?proceso_id=1
Authorization: Bearer {token}
```

**Modo flexible — listar asignaciones de un proceso:**
```
GET /api/elementos-asignaciones/filtrar?proceso_id=2
Authorization: Bearer {token}
```

**Modo flexible — filtrar por pauta específica:**
```
GET /api/elementos-asignaciones/filtrar?proceso_id=2&elemento_id=5
Authorization: Bearer {token}
```
