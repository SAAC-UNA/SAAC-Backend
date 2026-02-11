<?php

use App\Models\CriterionApproval;
use App\Models\Criterion;
use App\Models\Process;
use App\Models\User;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\Career;
use App\Models\Campus;

function unitCreateApprovalWithData(array $overrides = []): CriterionApproval
{
    $dimension = Dimension::factory()->create();
    $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
    $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);

    $career = Career::factory()->create();
    $campus = Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id
    ]);
    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id
    ]);

    $user = User::factory()->create();

    return CriterionApproval::create(array_merge([
        'criterio_id' => $criterion->criterio_id,
        'proceso_id' => $process->proceso_id,
        'usuario_id' => $user->usuario_id,
        'estado' => 'aprobado',
        'comentario' => 'Test approval'
    ], $overrides));
}

it('creates a criterion approval', function () {
    $approval = unitCreateApprovalWithData();

    $this->assertDatabaseHas('APROBACION_CRITERIO', [
        'aprobacion_criterio_id' => $approval->aprobacion_criterio_id,
        'estado' => 'aprobado'
    ]);
});

it('requires criterio_id field', function () {
    CriterionApproval::create([
        'criterio_id' => null,
        'proceso_id' => 1,
        'usuario_id' => 1,
        'estado' => 'aprobado'
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('requires proceso_id field', function () {
    CriterionApproval::create([
        'criterio_id' => 1,
        'proceso_id' => null,
        'usuario_id' => 1,
        'estado' => 'aprobado'
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('requires usuario_id field', function () {
    CriterionApproval::create([
        'criterio_id' => 1,
        'proceso_id' => 1,
        'usuario_id' => null,
        'estado' => 'aprobado'
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates approval status', function () {
    $approval = unitCreateApprovalWithData();

    $approval->update(['estado' => 'rechazado']);

    $this->assertDatabaseHas('APROBACION_CRITERIO', [
        'aprobacion_criterio_id' => $approval->aprobacion_criterio_id,
        'estado' => 'rechazado'
    ]);
});

it('deletes an approval', function () {
    $approval = unitCreateApprovalWithData();
    $approvalId = $approval->aprobacion_criterio_id;

    $approval->delete();

    $this->assertDatabaseMissing('APROBACION_CRITERIO', [
        'aprobacion_criterio_id' => $approvalId
    ]);
});

it('approval belongs to criterion', function () {
    $approval = unitCreateApprovalWithData();

    $this->assertInstanceOf(Criterion::class, $approval->criterion);
    expect($approval->criterion->criterio_id)->toBe($approval->criterio_id);
});

it('approval belongs to process', function () {
    $approval = unitCreateApprovalWithData();

    $this->assertInstanceOf(Process::class, $approval->process);
    expect($approval->process->proceso_id)->toBe($approval->proceso_id);
});

it('approval belongs to user', function () {
    $approval = unitCreateApprovalWithData();

    $this->assertInstanceOf(User::class, $approval->user);
    expect($approval->user->usuario_id)->toBe($approval->usuario_id);
});

it('can have commentary', function () {
    $approval = unitCreateApprovalWithData([
        'comentario' => 'Este es un comentario de prueba'
    ]);

    expect($approval->comentario)->toBe('Este es un comentario de prueba');
});

it('has approved state', function () {
    $approval = unitCreateApprovalWithData([
        'estado' => 'aprobado'
    ]);

    expect($approval->estado)->toBe('aprobado');
});

it('has rejected state', function () {
    $approval = unitCreateApprovalWithData([
        'estado' => 'rechazado'
    ]);

    expect($approval->estado)->toBe('rechazado');
});
