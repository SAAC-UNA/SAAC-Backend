<?php

use App\Models\EvidenceState;

it('evidence state has many evidences', function () {
    $state = \App\Models\EvidenceState::factory()->create();
    $dimension = \App\Models\Dimension::factory()->create();
    $component = \App\Models\Component::factory()->create([
        'dimension_id' => $dimension->dimension_id,
    ]);
    $criterion = \App\Models\Criterion::factory()->create([
        'componente_id' => $component->componente_id,
    ]);
    $evidence = \App\Models\Evidence::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'estado_evidencia_id' => $state->estado_evidencia_id,
    ]);
    $state->refresh();
    expect($state->evidences->contains($evidence))->toBeTrue();
});

it('creates an evidence state', function () {
    $state = EvidenceState::factory()->create([
        'nombre' => 'Estado 1',
    ]);

    $this->assertDatabaseHas('ESTADO_EVIDENCIA', [
        'nombre' => 'Estado 1',
    ]);
});

it('requires nombre field', function () {
    EvidenceState::factory()->create(['nombre' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates an evidence state', function () {
    $state = EvidenceState::factory()->create(['nombre' => 'Original']);
    $state->update(['nombre' => 'Actualizado']);

    $this->assertDatabaseHas('ESTADO_EVIDENCIA', ['nombre' => 'Actualizado']);
});

it('deletes an evidence state', function () {
    $state = EvidenceState::factory()->create();
    $state->delete();

    $this->assertDatabaseMissing('ESTADO_EVIDENCIA', ['estado_evidencia_id' => $state->estado_evidencia_id]);
});
