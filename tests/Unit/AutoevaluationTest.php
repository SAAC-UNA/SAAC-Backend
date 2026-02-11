<?php

use App\Models\Autoevaluation;

it('creates an autoevaluation', function () {
    // Prueba de creación de autoevaluación
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = \App\Models\AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = \App\Models\Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
    ]);
    $autoevaluation = \App\Models\Autoevaluation::factory()->create([
        'proceso_id' => $process->proceso_id,
    ]);
    $this->assertDatabaseHas('AUTOEVALUACION', [
        'autoevaluacion_id' => $autoevaluation->autoevaluacion_id,
    ]);
});

it('requires proceso_id field', function () {
    // Prueba de validación: campo proceso_id es obligatorio
    \App\Models\Autoevaluation::factory()->create(['proceso_id' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates an autoevaluation', function () {
    // Prueba de actualización de autoevaluación
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = \App\Models\AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = \App\Models\Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
    ]);
    $autoevaluation = \App\Models\Autoevaluation::factory()->create([
        'proceso_id' => $process->proceso_id,
        'fecha_inicio' => '2025-01-01',
    ]);
    $autoevaluation->update(['fecha_inicio' => '2025-09-28']);
    $this->assertDatabaseHas('AUTOEVALUACION', ['fecha_inicio' => '2025-09-28']);
});

it('deletes an autoevaluation', function () {
    // Prueba de eliminación de autoevaluación
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = \App\Models\AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = \App\Models\Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
    ]);
    $autoevaluation = \App\Models\Autoevaluation::factory()->create([
        'proceso_id' => $process->proceso_id,
    ]);
    $autoevaluation->delete();
    $this->assertDatabaseMissing('AUTOEVALUACION', ['autoevaluacion_id' => $autoevaluation->autoevaluacion_id]);
});

it('autoevaluation belongs to process', function () {
    // Prueba de relación belongsTo con Process
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = \App\Models\AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = \App\Models\Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
    ]);
    $autoevaluation = \App\Models\Autoevaluation::factory()->create([
        'proceso_id' => $process->proceso_id,
    ]);
    expect($autoevaluation->process->proceso_id)->toBe($process->proceso_id);
});

it('puede crear autoevaluación con fechas', function () {
    $process = \App\Models\Process::factory()->create();
    $autoevaluation = \App\Models\Autoevaluation::factory()->create([
        'proceso_id' => $process->proceso_id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
    ]);
    
    expect($autoevaluation->fecha_inicio)->toBe('2026-01-01');
    expect($autoevaluation->fecha_fin)->toBe('2026-12-31');
});

it('permite fechas nulas', function () {
    $process = \App\Models\Process::factory()->create();
    $autoevaluation = \App\Models\Autoevaluation::factory()->create([
        'proceso_id' => $process->proceso_id,
        'fecha_inicio' => null,
        'fecha_fin' => null,
    ]);
    
    expect($autoevaluation->fecha_inicio)->toBeNull();
    expect($autoevaluation->fecha_fin)->toBeNull();
});
