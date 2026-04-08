<?php

use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;

function makeEvidenceCriterion(): Criterion
{
    $dimension  = Dimension::factory()->create();
    $component  = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
    return Criterion::factory()->create(['componente_id' => $component->componente_id]);
}

it('evidence estado defaults to Pendiente', function () {
    $criterion = makeEvidenceCriterion();
    $evidence  = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

    expect($evidence->estado)->toBe('Pendiente');
});

it('evidence acepta todos los estados validos', function () {
    $criterion = makeEvidenceCriterion();

    foreach (Evidence::ESTADOS as $estado) {
        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado'       => $estado,
        ]);
        expect($evidence->estado)->toBe($estado);
    }
});

it('scopeByState filtra correctamente por estado', function () {
    $criterion = makeEvidenceCriterion();
    Evidence::factory()->create(['criterio_id' => $criterion->criterio_id, 'estado' => 'Pendiente']);
    Evidence::factory()->create(['criterio_id' => $criterion->criterio_id, 'estado' => 'Aprobado']);
    Evidence::factory()->create(['criterio_id' => $criterion->criterio_id, 'estado' => 'Aprobado']);

    $aprobadas = Evidence::byState('Aprobado')->get();

    expect($aprobadas->count())->toBeGreaterThanOrEqual(2);
    $aprobadas->each(fn ($e) => expect($e->estado)->toBe('Aprobado'));
});

it('evidence puede actualizar su estado', function () {
    $criterion = makeEvidenceCriterion();
    $evidence  = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id, 'estado' => 'Pendiente']);

    $evidence->update(['estado' => 'Completado']);

    expect($evidence->fresh()->estado)->toBe('Completado');
});

it('evidence con estado invalido lanza excepcion de base de datos', function () {
    $criterion = makeEvidenceCriterion();
    Evidence::factory()->create([
        'criterio_id' => $criterion->criterio_id,
        'estado'       => 'EstadoInexistente',
    ]);
})->throws(\Illuminate\Database\QueryException::class);
