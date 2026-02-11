<?php

use App\Models\Evidence;

it('creates evidence', function () {
    // Prueba de creación de evidencia
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => \App\Models\Component::factory()->create([
            'dimension_id' => \App\Models\Dimension::factory()->create()->dimension_id,
        ])->componente_id,
    ]);
    $evidenceState = \App\Models\EvidenceState::factory()->create();
    $evidence = \App\Models\Evidence::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
        'descripcion' => 'Evidencia 1',
    ]);
    $this->assertDatabaseHas('EVIDENCIA', [
        'descripcion' => 'Evidencia 1',
        'criterio_id' => $criterion->criterio_id,
        'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
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
    $evidenceState = \App\Models\EvidenceState::factory()->create();
    \App\Models\Evidence::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
        'descripcion' => null,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates evidence', function () {
    // Prueba de actualización de evidencia
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $evidenceState = \App\Models\EvidenceState::factory()->create();
    $evidence = \App\Models\Evidence::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
        'descripcion' => 'Original',
    ]);
    $evidence->update(['descripcion' => 'Actualizado']);
    $this->assertDatabaseHas('EVIDENCIA', ['descripcion' => 'Actualizado']);
});

it('deletes evidence', function () {
    // Prueba de eliminación de evidencia
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $evidenceState = \App\Models\EvidenceState::factory()->create();
    $evidence = \App\Models\Evidence::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
    ]);
    $evidence->delete();
    $this->assertDatabaseMissing('EVIDENCIA', ['evidencia_id' => $evidence->evidencia_id]);
});

it('evidence belongs to criterion', function () {
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
    expect($evidence->criterion->criterio_id)->toBe($criterion->criterio_id);
});
