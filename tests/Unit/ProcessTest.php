<?php

use App\Models\AccreditationCycle;
use App\Models\Autoevaluation;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\ImprovementCommitment;
use App\Models\Process;

function makeCycle(): AccreditationCycle
{
    $cc = CareerCampus::factory()->create([
        'carrera_id' => Career::factory()->create()->carrera_id,
        'sede_id'    => Campus::factory()->create()->sede_id,
    ]);
    return AccreditationCycle::factory()->create(['carrera_sede_id' => $cc->carrera_sede_id]);
}

function makeProcess(): Process
{
    return Process::factory()->create(['ciclo_acreditacion_id' => makeCycle()->ciclo_acreditacion_id]);
}

it('belongs to accreditation cycle', function () {
    $cycle   = makeCycle();
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);

    expect($process->accreditationCycle->ciclo_acreditacion_id)->toBe($cycle->ciclo_acreditacion_id);
});

it('has one autoevaluation', function () {
    $process      = makeProcess();
    $autoevaluation = Autoevaluation::factory()->create(['proceso_id' => $process->proceso_id]);

    expect($process->autoevaluation->autoevaluacion_id)->toBe($autoevaluation->autoevaluacion_id);
});

it('has one improvement commitment', function () {
    $process    = makeProcess();
    $commitment = ImprovementCommitment::factory()->create(['proceso_id' => $process->proceso_id]);

    expect($process->improvementCommitment->compromiso_mejora_id)->toBe($commitment->compromiso_mejora_id);
});

it('creates a process', function () {
    $cycle   = makeCycle();
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);

    expect(Process::find($process->proceso_id))->not->toBeNull()
        ->and($process->ciclo_acreditacion_id)->toBe($cycle->ciclo_acreditacion_id);
});

it('requires ciclo_acreditacion_id field', function () {
    Process::factory()->create(['ciclo_acreditacion_id' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a process', function () {
    $process  = makeProcess();
    $newCycle = makeCycle();
    $process->update(['ciclo_acreditacion_id' => $newCycle->ciclo_acreditacion_id]);

    expect(Process::find($process->proceso_id)->ciclo_acreditacion_id)->toBe($newCycle->ciclo_acreditacion_id);
});

it('deletes a process', function () {
    $process   = makeProcess();
    $processId = $process->proceso_id;
    $process->delete();

    expect(Process::find($processId))->toBeNull();
});
