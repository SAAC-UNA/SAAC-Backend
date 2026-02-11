<?php

use App\Models\Standard;

it('creates a standard', function () {
    // Prueba de creación de estándar
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => \App\Models\Component::factory()->create([
            'dimension_id' => \App\Models\Dimension::factory()->create()->dimension_id,
        ])->componente_id,
    ]);
    $standard = \App\Models\Standard::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'descripcion' => 'Estándar 1',
    ]);
    $this->assertDatabaseHas('ESTANDAR', [
        'descripcion' => 'Estándar 1',
        'criterio_id' => $criterion->criterio_id,
    ]);
});

it('requires descripcion field', function () {
    // Prueba de validación: campo descripción es obligatorio
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    \App\Models\Standard::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'descripcion' => null,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a standard', function () {
    // Prueba de actualización de estándar
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $standard = \App\Models\Standard::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'descripcion' => 'Original',
    ]);
    $standard->update(['descripcion' => 'Actualizado']);
    $this->assertDatabaseHas('ESTANDAR', ['descripcion' => 'Actualizado']);
});

it('deletes a standard', function () {
    // Prueba de eliminación de estándar
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $standard = \App\Models\Standard::factory()->create([
        'criterio_id' => $criterion->criterio_id,
    ]);
    $standard->delete();
    $this->assertDatabaseMissing('ESTANDAR', ['estandar_id' => $standard->estandar_id]);
});

it('standard belongs to criterion', function () {
    // Prueba de relación belongsTo con Criterion
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $standard = \App\Models\Standard::factory()->create(['criterio_id' => $criterion->criterio_id]);
    $standard->refresh();
    expect($standard->criterion->criterio_id)->toBe($criterion->criterio_id);
});
