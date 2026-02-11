<?php

use App\Models\Process;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\AccreditationCycle;
use App\Models\Autoevaluation;
use App\Models\ImprovementCommitment;

it('belongs to accreditation cycle', function () {
    $career = Career::factory()->create();
    $campus = Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
    
    expect($process->accreditationCycle->ciclo_acreditacion_id)->toBe($cycle->ciclo_acreditacion_id);
});

it('has one autoevaluation', function () {
    $career = Career::factory()->create();
    $campus = Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
    ]);
    $autoevaluation = Autoevaluation::factory()->create(['proceso_id' => $process->proceso_id]);
    
    expect($process->autoevaluation->autoevaluacion_id)->toBe($autoevaluation->autoevaluacion_id);
});

it('has one improvement commitment', function () {
    $career = Career::factory()->create();
    $campus = Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
    ]);
    $commitment = ImprovementCommitment::factory()->create(['proceso_id' => $process->proceso_id]);
    
    expect($process->improvementCommitment->compromiso_mejora_id)->toBe($commitment->compromiso_mejora_id);
});

it('creates a process', function () {
    $career = Career::factory()->create();
    $campus = Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $accreditationCycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
    ]);
    
    $this->assertDatabaseHas('PROCESO', [
        'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
        'proceso_id' => $process->proceso_id,
    ]);
});

it('requires ciclo_acreditacion_id field', function () {
    Process::factory()->create(['ciclo_acreditacion_id' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a process', function () {
    $career = Career::factory()->create();
    $campus = Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $accreditationCycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
    ]);
    $newCareer = Career::factory()->create();
    $newCampus = Campus::factory()->create();
    $newCareerCampus = CareerCampus::factory()->create([
        'carrera_id' => $newCareer->carrera_id,
        'sede_id' => $newCampus->sede_id,
    ]);
    $newCycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $newCareerCampus->carrera_sede_id,
    ]);
    $process->update(['ciclo_acreditacion_id' => $newCycle->ciclo_acreditacion_id]);
    
    $this->assertDatabaseHas('PROCESO', ['ciclo_acreditacion_id' => $newCycle->ciclo_acreditacion_id]);
});

it('deletes a process', function () {
    $career = Career::factory()->create();
    $campus = Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $accreditationCycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
    ]);
    $processId = $process->proceso_id;
    $process->delete();
    
    $this->assertDatabaseMissing('PROCESO', ['proceso_id' => $processId]);
});
