<?php

use App\Models\Criterion;

it('creates a criterion', function () {
    // Prueba de creación de criterio
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => \App\Models\Dimension::factory()->create()->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
        'descripcion' => 'Criterio 1',
    ]);
    $this->assertDatabaseHas('CRITERIO', [
        'descripcion' => 'Criterio 1',
        'componente_id' => $component->componente_id,
    ]);
});

it('requires descripcion field', function () {
    // Prueba de validación: campo descripción es obligatorio
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
        'descripcion' => null,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a criterion', function () {
    // Prueba de actualización de criterio
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
        'descripcion' => 'Original',
    ]);
    $criterion->update(['descripcion' => 'Actualizado']);
    $this->assertDatabaseHas('CRITERIO', ['descripcion' => 'Actualizado']);
});

it('deletes a criterion', function () {
    // Prueba de eliminación de criterio
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $criterion->delete();
    $this->assertDatabaseMissing('CRITERIO', ['criterio_id' => $criterion->criterio_id]);
});

it('criterion belongs to component', function () {
    // Prueba de relación belongsTo con Component
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create(['componente_id' => $component->componente_id]);
    expect($criterion->component->componente_id)->toBe($component->componente_id);
});

it('criterion has many evidences', function () {
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $evidence = \App\Models\Evidence::factory()->create([
        'criterio_id' => $criterion->criterio_id,
    ]);
    $criterion->refresh();
    expect($criterion->evidences->contains($evidence))->toBeTrue();
});
