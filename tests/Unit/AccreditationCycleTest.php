<?php

use App\Models\AccreditationCycle;

it('creates an accreditation cycle', function () {
    // Prueba de creación de ciclo de acreditación
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'nombre' => 'Ciclo 2025',
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $this->assertDatabaseHas('CICLO_ACREDITACION', [
        'nombre' => 'Ciclo 2025',
    ]);
});

it('requires nombre field', function () {
    // Prueba de validación: campo nombre es obligatorio
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    AccreditationCycle::factory()->create([
        'nombre' => null,
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates an accreditation cycle', function () {
    // Prueba de actualización de ciclo de acreditación
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'nombre' => 'Original',
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $cycle->update(['nombre' => 'Actualizado']);
    $this->assertDatabaseHas('CICLO_ACREDITACION', ['nombre' => 'Actualizado']);
});

it('deletes an accreditation cycle', function () {
    // Prueba de eliminación de ciclo de acreditación
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $cycle->delete();
    $this->assertDatabaseMissing('CICLO_ACREDITACION', ['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
});

it('accreditation cycle belongs to career campus', function () {
    // Prueba de relación belongsTo con CareerCampus
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    expect($cycle->careerCampus->carrera_sede_id)->toBe($careerCampus->carrera_sede_id);
});

it('accreditation cycle has many processes', function () {
    // Prueba de relación hasMany con Process
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = \App\Models\Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
    ]);
    expect($cycle->processes->contains($process))->toBeTrue();
});
