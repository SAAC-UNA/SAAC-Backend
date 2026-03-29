# Estado Automático de CRITERIO basado en EVIDENCIA

## ¿Qué se implementó?

Se agregó la capacidad de que el **estado de un CRITERIO se calcule y actualice automáticamente** cada vez que el estado de alguna de sus evidencias cambia. Antes de este cambio, CRITERIO solo tenía un campo booleano `activo` y ningún estado propio.

---

## Regla de negocio

| Situación | Estado resultante del CRITERIO |
|---|---|
| **Todas** las evidencias activas están en `Completado` | `Completado` |
| **Algunas** están en `Completado`, otras no | `En Proceso` |
| **Ninguna** está en `Completado` | `Pendiente` |
| No tiene evidencias activas | `Pendiente` |

> **Importante:** Solo se consideran las evidencias con `activo = true`. Las evidencias inactivas no cuentan para el cálculo.

---

## Archivos creados / modificados

### 1. `database/migrations/040_add_estado_to_criterio_table.php` *(nuevo)*

Agrega la columna `estado` a la tabla `CRITERIO` en la base de datos.

```php
$table->enum('estado', ['Pendiente', 'En Proceso', 'Completado'])
      ->default('Pendiente')
      ->after('activo');
```

Para aplicarlo: `php artisan migrate`

---

### 2. `app/Models/Criterion.php` *(modificado)*

Se agregaron dos cosas:

**a) Constante con los estados válidos:**
```php
public const ESTADOS = ['Pendiente', 'En Proceso', 'Completado'];
```

**b) `estado` en el array `$fillable`** para que Eloquent permita asignarlo:
```php
protected $fillable = [
    'componente_id',
    'descripcion',
    'nomenclatura',
    'activo',
    'estado',   // ← nuevo
];
```

---

### 3. `app/Services/CriterionService.php` *(modificado)*

Se agregó el método `recalcularEstado(int $criterioId)`:

```php
public function recalcularEstado(int $criterioId): void
{
    $criterion = Criterion::find($criterioId);
    // ...

    $evidencias  = $criterion->evidences()->where('activo', true)->get();
    $completadas = $evidencias->where('estado', 'Completado')->count();
    $total       = $evidencias->count();

    // Aplica la regla de negocio
    if ($total === 0 || $completadas === 0)     $nuevoEstado = 'Pendiente';
    elseif ($completadas === $total)             $nuevoEstado = 'Completado';
    else                                         $nuevoEstado = 'En Proceso';

    if ($criterion->estado !== $nuevoEstado) {
        $criterion->estado = $nuevoEstado;
        $criterion->saveQuietly(); // no genera entrada en bitácora
        Cache::forget(self::CACHE_KEY);
    }
}
```

**¿Por qué `saveQuietly()`?**  
El proyecto usa `AuditObserver` para registrar en bitácora cada vez que un modelo cambia. Si usáramos `save()` normal, cada recálculo automático generaría una entrada de bitácora como si un usuario hubiera editado el criterio manualmente. `saveQuietly()` omite los observers y evita ese ruido.

---

### 4. `app/Observers/EvidenceObserver.php` *(nuevo)*

Este es el núcleo del automatismo. Es un **Observer de Laravel** que escucha eventos del modelo `Evidence` y reacciona:

```php
class EvidenceObserver
{
    public function updated(Evidence $evidence): void
    {
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }

    public function created(Evidence $evidence): void
    {
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }

    public function deleted(Evidence $evidence): void
    {
        $this->criterionService->recalcularEstado($evidence->criterio_id);
    }
}
```

**¿Qué hace cada evento?**

- `updated`: Se dispara cuando una evidencia se guarda con `save()`. Recalcula el estado del criterio padre. No usa `wasChanged('estado')` porque el proyecto registra múltiples observers sobre `Evidence` y el primero en ejecutarse consume el tracking de cambios de Eloquent, devolviendo `false` en los siguientes aunque el campo sí haya cambiado. La guarda interna de `recalcularEstado()` evita writes innecesarios.
- `created`: Cuando se agrega una nueva evidencia a un criterio, recalcula su estado.
- `deleted`: Cuando se borra una evidencia, recalcula porque el total cambió.

---

### 5. `app/Providers/AppServiceProvider.php` *(modificado)*

Se registra el nuevo observer junto al `AuditObserver` ya existente. Ambos conviven sin problema porque Laravel permite múltiples observers por modelo.

```php
Evidence::observe(new AuditObserver('Evidencia', 'descripcion')); // ya existía
Evidence::observe(EvidenceObserver::class);                       // ← nuevo
```

Se usa `EvidenceObserver::class` (nombre de clase) en lugar de `new EvidenceObserver(...)` para que el contenedor IoC de Laravel resuelva e inyecte `CriterionService` automáticamente.

---

### 6. `bootstrap/providers.php` *(modificado)*

En Laravel 11, los Service Providers ya no se declaran en `config/app.php` sino en `bootstrap/providers.php`. Se agregó `AppServiceProvider` que no estaba registrado:

```php
return [
    App\Providers\AppServiceProvider::class,  // ← nuevo
    App\Providers\TelescopeServiceProvider::class,
];
```

Sin este cambio, `AppServiceProvider::boot()` nunca se ejecuta y ningún observer se registra.

---

### 7. `database/migrations/041_fix_evidencia_estado_enum_to_pascal_case.php` *(nuevo)*

Migración correctiva: la tabla `EVIDENCIA` tenía el ENUM definido con valores en minúscula (`pendiente`, `completado`, etc.) y los datos existentes también estaban en minúscula. El código nuevo usa PascalCase (`Pendiente`, `Completado`, etc.), por lo que el conteo `where('estado', 'Completado')` devolvía 0.

Esta migración:
1. Actualiza todos los registros existentes de minúscula a PascalCase
2. Redefine el ENUM de la columna con los valores en PascalCase

---

## Flujo completo de ejecución

```
Usuario actualiza EVIDENCIA.estado = 'Completado' (con save() individual)
    ↓
Eloquent dispara evento "updated" en Evidence
    ↓
EvidenceObserver::updated() llama recalcularEstado() incondicionalmente
    ↓
Llama CriterionService::recalcularEstado($evidence->criterio_id)
    ↓
Consulta todas las evidencias activas del criterio
    ↓
Calcula el nuevo estado según la regla de negocio
    ↓
Si el estado cambió → CRITERIO.estado = nuevo valor (saveQuietly)
    ↓
Limpia el cache del criterio
```

---

## ¿Cómo verificar que funciona?

1. Ejecutar la migración: `php artisan migrate`
2. Tomar un criterio con 3 evidencias activas en `'Pendiente'`
3. Cambiar una de ellas a `'Completado'` → el criterio debe quedar en `'En Proceso'`
4. Cambiar todas a `'Completado'` → el criterio debe quedar en `'Completado'`
5. Cambiar una de vuelta a `'Pendiente'` → el criterio vuelve a `'En Proceso'`

---

## Lo que NO se automatizó

Todo lo demás sigue siendo **manual** como antes:

- `EVIDENCIA_ASIGNACION.estado` → se actualiza manualmente
- `EVIDENCIA.estado` → se actualiza manualmente
- El único cambio automático es **CRITERIO.estado**, que se deriva de `EVIDENCIA.estado`
