<?php

use App\Models\Component;

it('creates a component', function () {
    // Prueba de creación de componente
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
        'nombre' => 'Componente 1',
    ]);
    $this->assertDatabaseHas('COMPONENTE', [
        'nombre' => 'Componente 1',
        'dimension_id' => $dimension->dimension_id,
    ]);
});

it('requires nombre field', function () {
    // Prueba de validación: campo nombre es obligatorio
    $dimension = \App\Models\Dimension::factory()->create();
    \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
        'nombre' => null,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a component', function () {
    // Prueba de actualización de componente
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
        'nombre' => 'Original',
    ]);
    $component->update(['nombre' => 'Actualizado']);
    $this->assertDatabaseHas('COMPONENTE', ['nombre' => 'Actualizado']);
});

it('deletes a component', function () {
    // Prueba de eliminación de componente
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $component->delete();
    $this->assertDatabaseMissing('COMPONENTE', ['componente_id' => $component->componente_id]);
});

it('component belongs to dimension', function () {
    // Prueba de relación belongsTo con Dimension
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
    expect($component->dimension->dimension_id)->toBe($dimension->dimension_id);
});

it('component has many criteria', function () {
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $component->refresh();
    expect($component->criteria->contains($criterion))->toBeTrue();
});
