<?php

use App\Models\Autoevaluation;
use App\Models\Process;

it('puede crear y recuperar una autoevaluación', function () {
    $autoevaluation = Autoevaluation::factory()->create();

    $found = Autoevaluation::find($autoevaluation->autoevaluacion_id);
    expect($found)->not->toBeNull();
    expect($found->autoevaluacion_id)->toBe($autoevaluation->autoevaluacion_id);
});

it('puede filtrar autoevaluaciones por proceso', function () {
    $process1 = Process::factory()->create();
    $process2 = Process::factory()->create();
    
    Autoevaluation::factory()->count(3)->create(['proceso_id' => $process1->proceso_id]);
    Autoevaluation::factory()->count(2)->create(['proceso_id' => $process2->proceso_id]);
    
    $process1Autoevals = Autoevaluation::where('proceso_id', $process1->proceso_id)->get();
    $process2Autoevals = Autoevaluation::where('proceso_id', $process2->proceso_id)->get();
    
    expect($process1Autoevals)->toHaveCount(3);
    expect($process2Autoevals)->toHaveCount(2);
});

it('puede actualizar fechas de autoevaluación', function () {
    $autoevaluation = Autoevaluation::factory()->create([
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-06-30',
    ]);
    
    $autoevaluation->update([
        'fecha_inicio' => '2026-02-01',
        'fecha_fin' => '2026-07-31',
    ]);
    
    $updated = Autoevaluation::find($autoevaluation->autoevaluacion_id);
    expect($updated->fecha_inicio)->toBe('2026-02-01');
    expect($updated->fecha_fin)->toBe('2026-07-31');
});

it('puede cargar relación con proceso', function () {
    $autoevaluation = Autoevaluation::factory()->create();
    
    $loaded = Autoevaluation::with('process')->find($autoevaluation->autoevaluacion_id);
    
    expect($loaded->relationLoaded('process'))->toBeTrue();
    expect($loaded->process)->toBeInstanceOf(Process::class);
});

