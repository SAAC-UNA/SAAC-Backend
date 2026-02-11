<?php

use App\Models\Dimension;

it('creates a dimension', function () {
    $dimension = \App\Models\Dimension::factory()->create([
        'nombre' => 'Dimensión 1',
    ]);
    $this->assertDatabaseHas('DIMENSION', [
        'nombre' => 'Dimensión 1',
    ]);
});

it('requires nombre field', function () {
    \App\Models\Dimension::factory()->create([
        'nombre' => null,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a dimension', function () {
    $dimension = \App\Models\Dimension::factory()->create([
        'nombre' => 'Original',
    ]);
    $dimension->update(['nombre' => 'Actualizado']);
    $this->assertDatabaseHas('DIMENSION', ['nombre' => 'Actualizado']);
});

it('deletes a dimension', function () {
    $dimension = \App\Models\Dimension::factory()->create();
    $dimension->delete();
    $this->assertDatabaseMissing('DIMENSION', ['dimension_id' => $dimension->dimension_id]);
});

it('dimension has many components', function () {
    // Prueba de relación hasMany con Component
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
    expect($dimension->components->contains($component))->toBeTrue();
});
